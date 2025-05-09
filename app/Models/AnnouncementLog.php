<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementLog extends Model
{
    protected $fillable = [
        'user_id',
        'announcementId', 
        'date_read'
    ];

    // Example relationship with AccountType
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcementId', 'id');
    }

    // Example relationship with User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
