<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\SuperadminNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records WHO changed WHAT on every create / update / delete.
 * Also stamps created_by / updated_by when the model has those columns.
 *
 * @mixin Model
 * Use it on any model:  use App\Traits\Auditable;
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        call_user_func([static::class, 'creating'], function (Model $model) {
            if (Auth::check()) {
                if ($model->isFillable('created_by') || in_array('created_by', $model->getFillable(), true)) {
                    $model->created_by = $model->created_by ?: Auth::id();
                }
                if (in_array('updated_by', $model->getFillable(), true)) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        call_user_func([static::class, 'updating'], function (Model $model) {
            if (Auth::check() && in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = Auth::id();
            }
        });

        call_user_func([static::class, 'created'], fn (Model $model) => $model->writeAuditLog('created', [], $model->getAttributes()));

        call_user_func([static::class, 'updated'], function (Model $model) {
            $new = $model->getChanges();
            unset($new['updated_at']);
            if (empty($new)) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $new);
            $model->writeAuditLog('updated', $old, $new);
        });

        call_user_func([static::class, 'deleted'], fn (Model $model) => $model->writeAuditLog('deleted', $model->getOriginal(), []));
    }

    public function writeAuditLog(string $action, array $old, array $new): void
    {
        $hidden = ['password', 'remember_token'];
        $old = array_diff_key($old, array_flip($hidden));
        $new = array_diff_key($new, array_flip($hidden));

        AuditLog::create([
            'user_id'         => Auth::id(),
            'actor_unique_id' => Auth::user()?->unique_id,
            'action'          => $action,
            'auditable_type'  => static::class,
            'auditable_id'    => $this->getKey(),
            'old_values'      => $old ?: null,
            'new_values'      => $new ?: null,
            'ip_address'      => request()?->ip(),
            'duration_ms'     => request()?->attributes->get('started_at')
                ? (int) round((microtime(true) - request()->attributes->get('started_at')) * 1000)
                : null,
        ]);

        SuperadminNotificationService::notifyDataChange($this, $action);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
