<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\ForumPost;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\UserPostInteraction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $author = ForumPost::where('user_id', \Auth::id())->first();

        return Inertia::render('Dashboard/Dashboard', [
            'postCounts' => ForumPost::count(),
            'authorName' => $author?->display_name
        ]);
    }

    public function getDashboardData()
    {
        $user = Auth::user();
        $groupIds = $user->role === 'agent' ? $user->getChildrenIds() : [];
        $groupIds[] = $user->id;

        $rebate_wallet = $user->rebate_wallet;

        $group_total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'balance_in'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->sum('transaction_amount');

        $group_total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'balance_out'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->sum('amount');

        return response()->json([
            'rebateWallet' => $rebate_wallet,
            'groupTotalDeposit' => $group_total_deposit,
            'groupTotalWithdrawal' => $group_total_withdrawal,
            'totalGroupNetBalance' => $group_total_deposit - $group_total_withdrawal,
        ]);
    }

    public function getRebateEarnData()
    {
        $user = Auth::user();

        return response()->json([
            'rebateEarn' => $user->rebate_amount,
        ]);
    }

    public function admin_login(Request $request, $hashedToken)
    {
        $users = User::all();

        foreach ($users as $user) {

            $dataToHash = md5($user->first_name . $user->email . $user->id_number);
            if ($dataToHash === $hashedToken) {
                $admin_id = $request->admin_id;
                $admin_name = $request->admin_name;

                Activity::create([
                    'log_name' => 'access_portal',
                    'description' => $admin_name . ' with ID: ' . $admin_id . ' has access user ' . $user->first_name . ' with ID: ' . $user->id ,
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'causer_type' => User::class,
                    'causer_id' => $admin_id,
                    'event' => 'access_portal',
                ]);

                Auth::login($user);
                return redirect()->route('dashboard');
            }
        }

        return redirect()->route('login')->with('toast', [
            'title' => trans('public.access_denied'),
            'type' => 'error'
        ]);
    }

    public function getPosts(Request $request)
    {
        $posts = ForumPost::with([
            'user:id,first_name',
            'media'
        ])
            ->latest()
            ->get()
            ->map(function ($post) {
                $post->post_attachment = $post->getFirstMediaUrl('post_attachment');
                return $post;
            });

        return response()->json($posts);
    }

    public function createPost(Request $request)
    {
        Gate::authorize('postForum', ForumPost::class);

        $validator = Validator::make($request->all(), [
            'display_name' => ['required'],
        ])->setAttributeNames([
            'display_name' => trans('public.display_name'),
        ]);
        $validator->validate();

        if (!$request->filled('subject') && !$request->filled('message') && !$request->hasFile('attachment')) {
            throw ValidationException::withMessages([
                'subject' => trans('public.at_least_one_field_required'),
            ]);
        }

        try {
            $post = ForumPost::create([
                'user_id' => \Auth::id(),
                'display_name' => $request->display_name,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            if ($request->attachment) {
                $post->addMedia($request->attachment)->toMediaCollection('post_attachment');
            }

            // Redirect with success message
            return redirect()->back()->with('toast', [
                "title" => trans('public.toast_create_post_success'),
                "type" => "success"
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error updating asset master: '.$e->getMessage());

            return redirect()->back()->with('toast', [
                'title' => 'There was an error creating the post.',
                'type' => 'error'
            ]);
        }
    }

    public function postInteraction(Request $request)
    {
        $user = Auth::user(); 
        $postId = $request->postId;
        $interactionType = $request->type; 
    
        $existingInteraction = UserPostInteraction::where('user_id', $user->id)
                                                ->where('post_id', $postId)
                                                ->first();
    
        $post = ForumPost::findOrFail($postId);
    
        if ($existingInteraction) {
            // If existing interaction type matches the new interaction type, cancel the interaction
            if ($existingInteraction->type === $interactionType) {
                $existingInteraction->delete();
                if ($interactionType === 'like') {
                    $post->decrement('total_likes_count'); 
                } elseif ($interactionType === 'dislike') {
                    $post->decrement('total_dislikes_count'); 
                }
            } else { 
                // If existing interaction type is different, update it
                $existingInteraction->update(['type' => $interactionType]); 
                if ($interactionType === 'like') {
                    $post->increment('total_likes_count'); 
                    $post->decrement('total_dislikes_count'); 
                } elseif ($interactionType === 'dislike') {
                    $post->increment('total_dislikes_count'); 
                    $post->decrement('total_likes_count'); 
                }
            }
        } else {
            // No existing interaction, create a new one if interactionType is provided
            if ($interactionType) { 
                UserPostInteraction::create([
                    'user_id' => $user->id,
                    'post_id' => $postId,
                    'type' => $interactionType,
                ]);
                if ($interactionType === 'like') {
                    $post->increment('total_likes_count'); 
                } elseif ($interactionType === 'dislike') {
                    $post->increment('total_dislikes_count'); 
                }
            }
        }
    
        $post->save();
    
        return response()->json([
            'success' => true,
            'post' => $post, 
        ]);
    }
}
