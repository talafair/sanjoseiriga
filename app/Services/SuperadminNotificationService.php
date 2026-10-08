<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SuperadminNotificationService
{
    public static function notifyDataChange(Model $model, string $action): void
    {
        $verb = match ($action) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            default => throw new \InvalidArgumentException("Unsupported data change action: {$action}"),
        };
        $subject = match (true) {
            $model instanceof Announcement => 'Announcement "' . $model->title . '"',
            $model instanceof Survey => 'Survey "' . $model->title . '"',
            $model instanceof SurveyResponse => 'Survey response for "' . $model->survey?->title . '"',
            $model instanceof User => 'Account "' . $model->full_name . '"',
            default => class_basename($model) . ' #' . $model->getKey(),
        };
        $superadminIds = User::query()
            ->where('role', User::SUPERADMIN_ROLE)
            ->pluck('id');

        if ($superadminIds->isEmpty()) {
            return;
        }

        $actor = auth()->user()?->full_name ?? 'the system';
        $now = now();

        DB::table('user_notifications')->insert($superadminIds->map(fn (int $userId) => [
            'user_id' => $userId,
            'title' => ucfirst($action) . ' ' . class_basename($model),
            'body' => "{$subject} was {$verb} by {$actor}.",
            'created_by' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }
}
