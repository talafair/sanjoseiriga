<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventRafflePrize extends Model
{
    use Auditable;

    protected $fillable = ['announcement_id', 'name', 'type', 'prize_type', 'points_amount', 'description', 'quantity', 'sort_order', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'points_amount' => 'integer', 'sort_order' => 'integer'];
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function winners()
    {
        return $this->hasMany(EventRaffleWinner::class, 'event_raffle_prize_id')->orderBy('draw_sequence');
    }

    public function remainingSlots(): int
    {
        return max(0, $this->quantity - $this->winners()->count());
    }

    public function getPrizeTypeLabelAttribute(): string
    {
        return \App\Models\Prize::TYPES[$this->prize_type]['label'] ?? ucfirst((string) $this->prize_type);
    }

    public function isPointsPrize(): bool
    {
        return $this->prize_type === 'points';
    }
}