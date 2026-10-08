<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventReminder extends Model
{
    use Auditable;

    protected $fillable = ['announcement_id', 'sent_by', 'kind', 'message', 'recipients_count'];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

//new file 08/17/2026