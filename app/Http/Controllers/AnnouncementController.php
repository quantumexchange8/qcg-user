<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\UserAnnouncementVisibility;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('Highlights/Announcement');
    }

    public function getAnnouncement(Request $request)
    {
        $announcements = Announcement::with([
            'media'
        ])
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function ($announcement) {
                $announcement->thumbnail = $announcement->getFirstMediaUrl('thumbnail');

                if ($announcement->recipient === 'selected_members') {

                    $userHasVisibility = UserAnnouncementVisibility::where('announcement_id', $announcement->id)
                        ->where('user_id', Auth::id())
                        ->exists();

                    if (!$userHasVisibility) {
                        return null;
                    }
                }
                return $announcement;
            })
            ->filter()
            ->values();

        $pinned_announcements = Announcement::with([
            'media'
        ])
            ->where('pinned', true)
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function ($announcement) {
                $announcement->thumbnail = $announcement->getFirstMediaUrl('thumbnail');

                if ($announcement->recipient === 'selected_members') {

                    $userHasVisibility = UserAnnouncementVisibility::where('announcement_id', $announcement->id)
                        ->where('user_id', Auth::id())
                        ->exists();

                    if (!$userHasVisibility) {
                        return null;
                    }
                }
                return $announcement;
            })
            ->filter()
            ->values();

        return response()->json([
            'announcements' => $announcements,
            'pinnedAnnouncements' => $pinned_announcements,
        ]);
    }
}
