<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventSubstitution extends Model
{
    use Auditable;

    protected $fillable = ['announcement_id', 'family_head_id', 'substitute_user_id'];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function familyHead()
    {
        return $this->belongsTo(User::class, 'family_head_id');
    }

    public function substitute()
    {
        return $this->belongsTo(User::class, 'substitute_user_id');
    }
}
