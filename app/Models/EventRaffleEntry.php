<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventRaffleEntry extends Model
{
    use Auditable;

    protected $fillable = [
        'announcement_id', 'user_id', 'is_early', 'points_snapshot', 'weight', 'snapshot_at', 'selected_at',
    ];

    protected function casts(): array
    {
        return [
            'is_early' => 'boolean',
            'points_snapshot' => 'integer',
            'weight' => 'float',
            'snapshot_at' => 'datetime',
            'selected_at' => 'datetime',
        ];
    }

    public function announcement() { return $this->belongsTo(Announcement::class); }
    public function user() { return $this->belongsTo(User::class); }
}
