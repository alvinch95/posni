<?php

namespace App\Models;

use App\Services\Website\Localized;
use Illuminate\Database\Eloquent\Model;

class WebSetting extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Localized value for a setting key, or null when the row is missing or both values are empty.
     * Falls back to the ID text when the EN value is blank.
     */
    public static function valueFor(string $key, string $locale): ?string
    {
        $row = static::where('key', $key)->first();

        return $row === null ? null : $row->localized($locale);
    }

    /**
     * This row's value for a locale (EN falls back to ID when blank), or null when it resolves to
     * empty. The single definition of "empty = unset", shared by valueFor() and the public API.
     */
    public function localized(string $locale): ?string
    {
        $value = Localized::pick($this->value_id, $this->value_en, $locale);

        return $value === '' ? null : $value;
    }

    /**
     * Upsert a setting. When both values are blank the row is deleted, so "empty" and
     * "never set" are the same state (the frontend then uses its built-in default).
     */
    public static function put(string $key, ?string $id, ?string $en = null): void
    {
        $id = trim((string) $id) === '' ? null : $id;
        $en = trim((string) $en) === '' ? null : $en;

        if ($id === null && $en === null) {
            static::where('key', $key)->delete();

            return;
        }

        static::updateOrCreate(['key' => $key], ['value_id' => $id, 'value_en' => $en]);
    }
}
