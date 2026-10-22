<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    public $timestamps = false;

    protected $fillable = ['survey_id', 'user_id', 'answers', 'suggestion', 'research_profile', 'research_consent_at', 'submitted_at'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'research_profile' => 'array', 'research_consent_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
