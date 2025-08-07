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
                'start_datetime' => Carbon::parse($competition->start_date)->format('Y-m-d H:i'),
                'end_datetime' => Carbon::parse($competition->end_date)->format('Y-m-d H:i'),
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
            'start_datetime' =>  Carbon::parse($competition->start_date)->format('Y-m-d H:i'),
            'end_datetime' => Carbon::parse($competition->end_date)->format('Y-m-d H:i'),
            'minimum_amount' => $competition->minimum_amount,
        ];

        return Inertia::render('Competition/Partials/ViewCompetition', [
            'competition' => $competitionData,
        ]);
    }


    public function getParticipants(Request $request)
    {
        $participants = Participant::with(['user', 'competition.rewards'])
            ->where('competition_id', $request->competition_id)
            ->orderByDesc('score')
            ->get()
            ->map(function ($participant, $key) {
            $participantRank = $key + 1;

            $rankTitle = null;
            $rank_badge = null;
            $points = null;

            $name = null;
            if ($participant->user_type == 'standard') {
                $name = $participant->user?->chinese_name ?? $participant->user?->first_name;
            } else {
                $name = $participant->user_name;
            }

            if ($participant->score >= $participant->competition->minimum_amount) {
                $reward = $participant->competition->rewards->first(function ($reward) use ($participantRank) {
                    return $participantRank >= $reward->min_rank && $participantRank <= $reward->max_rank;
                });
    
                if ($reward) {
                    // Log::info($reward);
                    $rankTitle = json_decode($reward->title, true);
                    $points = $reward->points_rewarded;
                    $rank_badge = $reward->getFirstMediaUrl('rank_badge');
                }
                else {
                    $rank_badge = $participant->competition->getFirstMediaUrl('unranked_badge');
                }    
            } else {
                $rank_badge = $participant->competition->getFirstMediaUrl('unranked_badge');
            }

            return [
                'id' => $participant->id,
                'user_type' => $participant->user_type,
                'name' => $name,
                'score' => $participant->score,
                'rank' => $participantRank,
                'title' => $rankTitle,
                'points_rewarded' => $points,
                'rank_badge' => $rank_badge,
            ];
        });

        Log::info($participants);

        return response()->json([
            'participants' => $participants,
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