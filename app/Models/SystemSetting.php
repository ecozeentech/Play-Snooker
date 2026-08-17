<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(fn (SystemSetting $setting) => Cache::forget("system_setting:{$setting->key}"));
        static::deleted(fn (SystemSetting $setting) => Cache::forget("system_setting:{$setting->key}"));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("system_setting:{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'float' => (float) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
    {
        $stored = $type === 'json' ? json_encode($value) : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type, 'group' => $group],
        );

        Cache::forget("system_setting:{$key}");

        return $setting;
    }
}
