<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\CompetitionReward;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompetitionController extends Controller
{
    public function index()
    {
        $competitions = Competition::with('rewards')->get();

        $competitionsData = $competitions->map(function ($competition) {
            $name = json_decode($competition->name, true);

            return [
                'id' => $competition->id,
                'category' => $competition->category,
                'name' => $name,
                'status' => $competition->statusByDate,
                'start_datetime' =>  Carbon::parse($competition->start_date, 'UTC')->toIso8601String(),
                'end_datetime' => Carbon::parse($competition->end_date, 'UTC')->toIso8601String(),
                'minimum_amount' => $competition->minimum_amount,
                'rewards' => $competition->rewards,
            ];
        });

        return Inertia::render('Competition/Competition', [
            'competitions' => $competitionsData,
        ]);
    }

    public function newCompetition()
    {
        return Inertia::render('Competition/Partials/NewCompetition');

    }

    public function getCurrentCompetitions()
    {
        $competitions = Competition::whereNot('status', 'completed')
            ->with('rewards')
            ->orderBy('start_date')
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                $totalPointsDistributed = $competition->rewards->sum(function ($reward) {
                    $numberOfRanksInTier = ($reward->max_rank - $reward->min_rank + 1);
        
                    return $numberOfRanksInTier * $reward->points_rewarded;
                });

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'status' => $competition->status,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
                    'total_points' => $totalPointsDistributed,
                ];
            })
            ->values();

        return response()->json([
            'competitions' => $competitions,
        ]);
    }

    public function getCompetitionHistory(Request $request)
    {
        $category = $request->query('category');
        $query = Competition::where('status', 'completed');

        if ($category) {
            $query->where('category', $category);
        }

        $competitions = $query->orderBy('end_date')
            ->with('rewards')
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                $totalPointsDistributed = $competition->rewards->sum(function ($reward) {
                    $numberOfRanksInTier = ($reward->max_rank - $reward->min_rank + 1);
        
                    return $numberOfRanksInTier * $reward->points_rewarded;
                });

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
                    'total_points' => $totalPointsDistributed,
                ];
            })
            ->values();

        return response()->json([
            'competitions' => $competitions,
        ]);
    }

    public function viewCompetition(Request $request, string $id)
    {
        $competition = Competition::with('rewards')->findOrFail($request->id);

        $name = json_decode($competition->name, true); 

        $competitionData = [
            'competition_id' => $competition->id,
            'category' => $competition->category,
            'name' => $name,
            'start_datetime' =>  Carbon::parse($competition->start_date, 'UTC')->toIso8601String(),
            'end_datetime' => Carbon::parse($competition->end_date, 'UTC')->toIso8601String(),
            'minimum_amount' => $competition->minimum_amount,
        ];

        return Inertia::render('Competition/Partials/ViewCompetition', [
            'competition' => $competitionData,
        ]);
    }


    public function getParticipants(Request $request)
    {
        $competition = Competition::with('rewards')->find($request->competition_id);
        $maxRanks = $competition->rewards->max('max_rank');

        $currentUserId = Auth::id();
        
        // Step 1: Fetch ALL participants of the competition and sort them correctly.
        // This is done once to establish the global ranking for all participants.
        $allCompetitionParticipants = Participant::where('competition_id', $competition->id)
        ->orderByDesc('score')
        ->orderBy('updated_at')
        ->get();

        // Step 2: Create a rank map by iterating through the sorted full list.
        // This handles ties correctly by assigning the same rank to participants with the same score.
        $rankMap = [];
        $currentRank = 1;

        foreach ($allCompetitionParticipants as $index => $participant) {
            // If this is not the first participant, check for ties.
            if ($index > 0) {
                $previousParticipant = $allCompetitionParticipants[$index - 1];

                // If the current participant's score and updated_at are the same as the previous one,
                // they share the same rank.
                if ($participant->score === $previousParticipant->score && $participant->updated_at == $previousParticipant->updated_at) {
                    // Keep the same rank as the previous participant.
                } else {
                    // Otherwise, their rank is their position in the sorted list.
                    $currentRank = $index + 1;
                }
            }

            $rankMap[$participant->id] = $currentRank;
        }

        // Step 3: Fetch the top participants and all user's participants.
        $topParticipants = Participant::with(['tradingAccount.ofUser', 'competition.rewards'])
            ->whereIn('id', $allCompetitionParticipants->take($maxRanks)->pluck('id'))
            ->get();

        $userParticipants = Participant::with(['tradingAccount.ofUser', 'competition.rewards'])
            ->where('competition_id', $competition->id)
            ->whereHas('tradingAccount.ofUser', function ($query) use ($currentUserId) {
                $query->where('id', $currentUserId);
            })
            ->get();

        // Step 4: Merge, deduplicate, and sort the final collection.
        $finalParticipants = $topParticipants->merge($userParticipants)
            ->unique('id')
            ->sortBy([
                ['score', 'desc'],
                ['updated_at', 'asc'],
            ]);

        // Combine the two collections and remove duplicates based on participant ID
        $allParticipants = $topParticipants->merge($userParticipants)->unique('id');

        // Sort the combined collection to ensure correct global ranking
        $finalParticipants = $allParticipants->sortBy([
            ['score', 'desc'],
            ['updated_at', 'asc'],
        ]);

        $formattedParticipants = $finalParticipants->values()->map(function ($participant) use ($rankMap, $currentUserId, $competition) {
            $participantRank = $rankMap[$participant->id] ?? null;
            
            $rankTitle = null;
            $points = null;
            $rank_badge = null;
            $name = null;
        
            if ($participant->user_type == 'standard') {
                $name = optional(optional($participant->tradingAccount)->ofUser)->chinese_name ?? optional(optional($participant->tradingAccount)->ofUser)->first_name;
            } else {
                $name = $participant->user_name;
            }
        
            if ($participant->score >= $competition->minimum_amount) {
                $reward = $competition->rewards->first(function ($reward) use ($participantRank) {
                    return $participantRank >= $reward->min_rank && $participantRank <= $reward->max_rank;
                });
        
                if ($reward) {
                    $rankTitle = json_decode($reward->title, true);
                    $points = $reward->points_rewarded;
                    $rank_badge = $reward->getFirstMediaUrl('rank_badge');
                } else {
                    $rank_badge = $competition->getFirstMediaUrl('unranked_badge');
                }    
            } else {
                $rank_badge = $competition->getFirstMediaUrl('unranked_badge');
            }
        
            return [
                'id' => $participant->id,
                'user_type' => $participant->user_type,
                'user_id' => optional(optional($participant->tradingAccount)->ofUser)->id,
                'name' => $name,
                'score' => $participant->score,
                'rank' => $participantRank,
                'title' => $rankTitle,
                'points_rewarded' => $points,
                'rank_badge' => $rank_badge,
            ];
        });

        // $participants = Participant::with(['tradingAccount.ofUser', 'competition.rewards'])
        //     ->where('competition_id', $request->competition_id)
        //     ->orderByDesc('score')
        //     ->orderBy('updated_at')
        //     ->get()
        //     ->map(function ($participant, $key) {
        //     $participantRank = $key + 1;

        //     $rankTitle = null;
        //     $rank_badge = null;
        //     $points = null;
        //     $user_id = null;
            
        //     $name = null;
        //     if ($participant->user_type == 'standard') {
        //         $user_id = $participant->tradingAccount?->ofUser->id;
        //         $name = $participant->tradingAccount?->ofUser->chinese_name ?? $participant->tradingAccount?->ofUser->first_name;
        //     } else {
        //         $name = $participant->user_name;
        //     }

        //     if ($participant->score >= $participant->competition->minimum_amount) {
        //         $reward = $participant->competition->rewards->first(function ($reward) use ($participantRank) {
        //             return $participantRank >= $reward->min_rank && $participantRank <= $reward->max_rank;
        //         });
    
        //         if ($reward) {
        //             // Log::info($reward);
        //             $rankTitle = json_decode($reward->title, true);
        //             $points = $reward->points_rewarded;
        //             $rank_badge = $reward->getFirstMediaUrl('rank_badge');
        //         }
        //         else {
        //             $rank_badge = $participant->competition->getFirstMediaUrl('unranked_badge');
        //         }    
        //     } else {
        //         $rank_badge = $participant->competition->getFirstMediaUrl('unranked_badge');
        //     }

        //     return [
        //         'id' => $participant->id,
        //         'user_type' => $participant->user_type,
        //         'user_id' => $user_id,
        //         'name' => $name,
        //         'score' => $participant->score,
        //         'rank' => $participantRank,
        //         'title' => $rankTitle,
        //         'points_rewarded' => $points,
        //         'rank_badge' => $rank_badge,
        //     ];
        // });

        // Log::info($participants);

        return response()->json([
            'participants' => $formattedParticipants,
        ]);
    }

    public function addVirtual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'virtual_name' => ['required'],
            'amount' => ['required'],
        ])->setAttributeNames([
            'virtual_name' => trans('public.name'),
            'amount' => trans('public.amount'),
        ]);
        $validator->validate();

        try {
            Participant::create([
                'competition_id' => $request->competition_id,
                'user_type' => 'virtual',
                'user_name' => $request->virtual_name,
                'score' => $request->amount,
            ]);

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_add_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error adding participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error adding the participant.',
                'type' => 'error'
            ]);
        }
    }

    public function editVirtual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'virtual_name' => ['required'],
            'amount' => ['required'],
        ])->setAttributeNames([
            'virtual_name' => trans('public.name'),
            'amount' => trans('public.amount'),
        ]);
        $validator->validate();

        try {
            $participant = Participant::findOrFail($request->participant_id);

            $participant->update([
                'user_name' => $request->virtual_name,
                'score' => $request->amount,
            ]);

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_edit_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error editing participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error editing the participant.',
                'type' => 'error'
            ]);
        }
    }

    public function deleteVirtual(Request $request)
    {
        try {
            $participant = Participant::findOrFail($request->participant_id);
            
            $participant->delete();

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_delete_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error deleting participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error deleting the participant.',
                'type' => 'error'
            ]);
        }
    }
}