<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    public function userAnnouncementVisibilities()
    {
        return $this->hasMany(UserAnnouncementVisibility::class);
    }

    public function read()
    {
        return $this->hasMany(AnnouncementLog::class, 'announcementId', 'id');
    }
}
