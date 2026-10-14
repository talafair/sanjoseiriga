<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use Auditable;

    protected $fillable = ['title', 'description', 'due_at', 'questions', 'sections', 'suggestion_enabled', 'points', 'audience', 'event_id', 'created_by'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'sections' => 'array', 'suggestion_enabled' => 'boolean', 'due_at' => 'datetime', 'points' => 'integer'];
    }

    public function event()
    {
        return $this->belongsTo(Announcement::class, 'event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function audienceQuery(): Builder
    {
        if ($this->audience === 'event_attendees') {
            return User::whereHas('attendances', fn (Builder $query) => $query->where('announcement_id', $this->event_id));
        }

        return (new User)->audienceQueryFor([$this->audience]);
    }
}
