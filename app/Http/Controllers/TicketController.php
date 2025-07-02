<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index()
    {
        return Inertia::render('Tickets/TicketCenter');
    }

    public function pending()
    {
        $categories = TicketCategory::orderBy('order')
            ->get()
            ->map(function($ticket_category) {
                $category = json_decode($ticket_category->category, true);

                return [
                    'category_id' => $ticket_category->id,
                    'category' => $category,
                ];

            })
            ->values();

        return Inertia::render('Tickets/Pending', [
            'categories' => $categories,
        ]);

    }

    public function history()
    {
        $categories = TicketCategory::orderBy('order')
            ->get()
            ->map(function($ticket_category) {
                $category = json_decode($ticket_category->category, true);

                return [
                    'category_id' => $ticket_category->id,
                    'category' => $category,
                ];

            })
            ->values();

        return Inertia::render('Tickets/History', [
            'categories' => $categories,
        ]);
    }

    public function getTickets(Request $request)
    {
        $status = $request->query('ticket_status');

        $ticket_status = match($status) {
            'ongoing' => ['new', 'in_progress'],
            'resolved' => ['resolved'],
            default => []
        };

        $query = Ticket::with(['category', 'user', 'replies'])->where('user_id', Auth::id())->whereIn('status', $ticket_status);

        $tickets = $query->get()
            ->map(function($ticket) {
                $category = json_decode($ticket->category->category, true);
                $ticket_attachments = $ticket->getMedia('ticket_attachment');

                $lastReply = $ticket->replies->sortByDesc('created_at')->first();

                $isLastReplyFromSubmitter = $lastReply ? $lastReply->user_id === $ticket->user_id : true;

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'name' => $ticket->user->chinese_name ?? $ticket->user->first_name,
                    'email' => $ticket->user->email,
                    'created_at' => $ticket->created_at,
                    'category' => $category,
                    'status' => $ticket->status,
                    'last_reply_from_submitter' => $isLastReplyFromSubmitter,
                    'ticket_attachments' => $ticket_attachments,
                ];

            })
            ->values();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicketHistory(Request $request)
    {
        $category_id = $request->query('category_id');

        $query = Ticket::with(['category', 'user'])->where('status', 'resolved');

        if ($category_id) {
            $query->whereHas('category', function ($query) use ($category_id) {
                $query->where('id', $category_id);
            });
        }

        $tickets = $query->get()
            ->map(function($ticket) {
                $category = json_decode($ticket->category->category, true);
                $ticket_attachments = $ticket->getMedia('ticket_attachment');

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'name' => $ticket->user->chinese_name ?? $ticket->user->first_name,
                    'email' => $ticket->user->email,
                    'created_at' => $ticket->created_at,
                    'closed_at' => $ticket->closed_at,
                    'category' => $category,
                    'status' => $ticket->status,
                    'ticket_attachments' => $ticket_attachments,
                ];

            })
            ->values();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicketReplies(Request $request)
    {
        // $ticket_id = $request->query('ticket_id');
        $ticket = Ticket::with(['replies.user:id,first_name,chinese_name,email', 'user:id,first_name,chinese_name,email'])->where('id', $request->ticket_id)->get()
                    ->map(function($ticket_details) {
                        $ticket_attachments = $ticket_details->getMedia('ticket_attachment');

                        $replies = $ticket_details->replies->map(function ($reply) {
                            return [
                                'reply_id' => $reply->id,
                                'user_id' => $reply->user_id,
                                'name' => $reply->user->chinese_name ?? $reply->user->first_name,
                                'message' => $reply->message,
                                'sent_at' => $reply->created_at,
                                'reply_attachments' => $reply->getMedia('ticket_attachment'),
                            ];
                        });

                        return [
                            'ticket_id' => $ticket_details->id,
                            'user_id' => $ticket_details->user_id,
                            'subject' => $ticket_details->subject,
                            'description' => $ticket_details->description,
                            'ticket_attachments' => $ticket_attachments,
                            'replies' => $replies,
                            'created_at' => $ticket_details->created_at,
                            'ticket_details' => $ticket_details->status,
                        ];

                    })
                    ->first();

        return response()->json([
            'ticket' => $ticket,
        ]);
    }

    public function sendReply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_attachment' => ['array'],
            'ticket_attachment.*' => ['mimes:jpg,png', 'max:10000'],
        ])->setAttributeNames([
            'ticket_attachment' => trans('public.ticket_attachment'),
            'ticket_attachment.*' => trans('public.ticket_attachment_file'),
        ]);

        $validator->after(function ($validator) {
            $errors = $validator->errors();
        
            foreach ($errors->getMessages() as $key => $messages) {
                if (Str::startsWith($key, 'ticket_attachment.')) {
                    $errors->add('ticket_attachment', $messages[0]);
                    break; // only add one error
                }
            }
        });

        $validator->validate();

        $reply = TicketReply::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        if ($request->file('ticket_attachment')) {
            foreach ($request->file('ticket_attachment') as $file) {
                $reply->addMedia($file)->toMediaCollection('ticket_attachment'); 
            }
        }

        Ticket::where('id', $request->ticket_id)->update([
            'last_replied_at' => now(),
        ]);
        // $ticket_id = $request->query('ticket_id');
        // $ticket = Ticket::with(['replies.user', 'user'])->where('id', $request->ticket_id)->get()
        //             ->map(function($ticket_details) {
        //                 $ticket_attachments = $ticket_details->getMedia('ticket_attachments');

        //                 $replies = [
        //                     'reply_id' => $ticket_details->replies->id,
        //                     'name' => $ticket_details->replies->user->chinese_name ?? $ticket_details->replies->user->first_name,
        //                     'subject' => $ticket_details->replies->message,
        //                     'sent_at' => $ticket_details->replies->created_at,
        //                     'reply_attachments' => $ticket_details->replies->getMedia('reply_attachments'),
        //                 ];

        //                 return [
        //                     'ticket_id' => $ticket_details->id,
        //                     'subject' => $ticket_details->subject,
        //                     'description' => $ticket_details->description,
        //                     'ticket_attachments' => $ticket_attachments,
        //                     'replies' => $replies,
        //                     'created_at' => $ticket_details->created_at,
        //                     'ticket_details' => $ticket->status,
        //                 ];

        //             })
        //             ->values();
        
        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_send_reply_success'),
            'type' => 'success'
        ]);

        // return redirect()->back()->with('toast', [
        //     'title' => trans('public.toast_send_reply_error'),
        //     'type' => 'error'
        // ]);
    }

    public function createTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required'],
            'subject' => ['required'],
            'description' => ['required'],
            'ticket_attachment' => ['array'],
            'ticket_attachment.*' => ['mimes:jpg,png', 'max:10000'],
        ])->setAttributeNames([
            'category' => trans('public.category'),
            'subject' => trans('public.subject'),
            'description' => trans('public.description'),
            'ticket_attachment' => trans('public.ticket_attachment'),
            'ticket_attachment.*' => trans('public.ticket_attachment_file'),
        ]);

        $validator->after(function ($validator) {
            $errors = $validator->errors();
        
            foreach ($errors->getMessages() as $key => $messages) {
                if (Str::startsWith($key, 'ticket_attachment.')) {
                    $errors->add('ticket_attachment', $messages[0]);
                    break; // only add one error
                }
            }
        });

        $validator->validate();

        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'new',
        ]);

        if ($request->file('ticket_attachment')) {
            foreach ($request->file('ticket_attachment') as $file) {
                $reply->addMedia($file)->toMediaCollection('ticket_attachment'); 
            }
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_create_ticket_success'),
            'type' => 'success'
        ]);
    }
}
