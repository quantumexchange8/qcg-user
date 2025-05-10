<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnnouncementVisibility extends Model
{

    // Example relationship with AccountType
    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    // Example relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
