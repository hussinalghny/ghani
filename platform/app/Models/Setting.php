<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('academy.settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? config("academy.defaults.$key", $default);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('academy.settings');
    }
}
