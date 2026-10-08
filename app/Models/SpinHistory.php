<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class SpinHistory extends Model
{
    use Auditable;

    protected $table = 'spin_history';

    protected $fillable = [
        'user_id',
        'prize_label',
        'prize_type',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
