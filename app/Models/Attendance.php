<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use Auditable;

    protected $fillable = [
        'announcement_id', 'user_id', 'user_category', 'attendance_method', 'scanned_at', 'is_early', 'pre_registered',
        'latitude', 'longitude', 'distance_m', 'points_awarded', 'confirmed_name', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at'     => 'datetime',
            'is_early'       => 'boolean',
            'pre_registered' => 'boolean',
            'user_category'  => 'string',
            'attendance_method' => 'string',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOfficialAttendance(): bool
    {
        return $this->user_category === 'official';
    }

    public function isResidentAttendance(): bool
    {
        return $this->user_category === 'resident';
    }
}

//new file 08/17/2026