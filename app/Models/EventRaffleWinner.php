<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventRaffleWinner extends Model
{
    use Auditable;

    protected $fillable = [
        'announcement_id', 'event_raffle_prize_id', 'user_id', 'winner_name_snapshot', 'draw_sequence', 'drawn_at',
    ];

    protected function casts(): array
    {
        return ['draw_sequence' => 'integer', 'drawn_at' => 'datetime'];
    }

    public function getPublicNameAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->winner_name_snapshot));

        return count($parts) > 1
            ? $parts[0] . ' ' . substr($parts[array_key_last($parts)], 0, 1) . '.'
            : (string) ($parts[0] ?? 'Winner');
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function prize()
    {
        return $this->belongsTo(EventRafflePrize::class, 'event_raffle_prize_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}