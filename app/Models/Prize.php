<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use Auditable;

    /** Display name and icon per prize type. */
    public const TYPES = [
        'points' => ['label' => 'Points', 'icon' => 'bi-star-fill'],
        'foods' => ['label' => 'Foods', 'icon' => 'bi-basket2-fill'],
        'electronics' => ['label' => 'Electronics', 'icon' => 'bi-phone-fill'],
        'cash' => ['label' => 'Cash', 'icon' => 'bi-cash-coin'],
        'essentials' => ['label' => 'Essentials', 'icon' => 'bi-bag-heart-fill'],
        'none' => ['label' => 'No Prize (Try Again)', 'icon' => 'bi-emoji-smile'],
    ];

    protected $fillable = [
        'label',
        'prize_type',
        'amount',
        'color',
    ];

    public function getIconAttribute(): string
    {
        return self::TYPES[$this->prize_type]['icon'] ?? 'bi-gift';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->prize_type]['label'] ?? ucfirst($this->prize_type);
    }

    /** Whether wheel text on this color needs to be light. */
    public function getLightTextAttribute(): bool
    {
        $hex = ltrim($this->color, '#');
        if (strlen($hex) !== 6) {
            return false;
        }

        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 140;
    }
}
