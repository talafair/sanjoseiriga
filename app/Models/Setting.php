<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\SuperadminNotificationService;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['key', 'value', 'updated_by'];

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", fn () => static::find($key)?->value) ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => Auth::id()]);
        SuperadminNotificationService::notifyDataChange($setting, $setting->wasRecentlyCreated ? 'created' : 'updated');
        Cache::forget("setting:{$key}");
    }

        /** URL of an uploaded setting (background image, etc). */
    public static function url(string $key, ?string $default = null): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : $default;
    }
}

//new file 08/17/2026