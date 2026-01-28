<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return $setting->typed_value ?? $default;
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        // Encode value based on type
        $encodedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            'encrypted' => $value ? Crypt::encryptString($value) : null,
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encodedValue,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("setting:{$key}");
        
        auditLog("Setting updated: {$key}", $setting);
    }

    /**
     * Get typed value based on type
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->type) {
                    'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $this->value,
                    'float' => (float) $this->value,
                    'json' => json_decode($this->value, true),
                    'encrypted' => $this->value ? Crypt::decryptString($this->value) : null,
                    default => $this->value,
                };
            }
        );
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::rememberForever("settings:group:{$group}", function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->mapWithKeys(fn($setting) => [$setting->key => $setting->typed_value])
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        
        foreach ($settings as $setting) {
            Cache::forget("setting:{$setting->key}");
        }
        
        $groups = static::distinct('group')->pluck('group');
        
        foreach ($groups as $group) {
            Cache::forget("settings:group:{$group}");
        }
    }
}
