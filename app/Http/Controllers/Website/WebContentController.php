<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebSetting;
use App\Services\Website\ContentKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * "Konten Homepage": the bilingual copy overrides behind the keys in config/website_content.php.
 * A value is stored as a web_settings row "copy.<Namespace.key>"; an empty value means "no row", and
 * the frontend then shows its built-in text.
 */
class WebContentController extends Controller
{
    private const PREFIX = 'copy.';

    /** next-intl parses messages as ICU, so { } and tag characters would break rendering. */
    private const VALUE_RULES = 'nullable|string|max:1000|not_regex:/[{}<>]/';

    private const BAD_CHARS = 'Karakter { } < > tidak didukung.';

    /**
     * "Hero.eyebrow" => "Hero__eyebrow". The dot is swapped out so Laravel does not read the key as a
     * nested input path (copy[Hero][eyebrow]) in the form and in the validation rules.
     */
    public static function fieldName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function edit()
    {
        $definitions = ContentKeys::all();

        $rows = WebSetting::whereIn('key', array_map(fn (string $key) => self::PREFIX . $key, array_keys($definitions)))
            ->get()
            ->keyBy('key');

        $sections = [];
        foreach (ContentKeys::sections() as $section => $definition) {
            $fields = [];
            foreach (array_keys($definition['keys'] ?? []) as $key) {
                $field = $definitions[$key];
                $row = $rows->get(self::PREFIX . $key);

                $fields[] = [
                    'name' => self::fieldName($key),
                    'key' => $key,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'defaultId' => $field['default_id'],
                    'defaultEn' => $field['default_en'],
                    'valueId' => $row ? $row->value_id : null,
                    'valueEn' => $row ? $row->value_en : null,
                ];
            }

            $sections[] = [
                'key' => (string) $section,
                'label' => (string) ($definition['label'] ?? $section),
                'fields' => $fields,
            ];
        }

        return view('dashboard.website.content.edit', ['sections' => $sections]);
    }

    public function update(Request $request)
    {
        $definitions = ContentKeys::all();

        // Rules only exist for the configured keys, so anything else in the request is neither
        // validated nor returned by validate(): unknown keys are ignored.
        $rules = [];
        $messages = [];
        foreach ($definitions as $key => $field) {
            $name = self::fieldName($key);

            foreach (['id' => 'ID', 'en' => 'EN'] as $suffix => $caption) {
                $attribute = "copy.{$name}.{$suffix}";
                $rules[$attribute] = self::VALUE_RULES;

                $messages["{$attribute}.not_regex"] = self::BAD_CHARS;
                $messages["{$attribute}.max"] = "{$field['label']} ({$caption}) maksimal :max karakter.";
                $messages["{$attribute}.string"] = "{$field['label']} ({$caption}) tidak valid.";
            }
        }

        $data = $request->validate($rules, $messages);

        // put() deletes the row when both values are blank, so clearing a field puts it back to the built-in
        // text. Only keys that were actually submitted are touched; the form always posts all of them.
        DB::transaction(function () use ($definitions, $data) {
            foreach (array_keys($definitions) as $key) {
                $submitted = $data['copy'][self::fieldName($key)] ?? null;

                if ($submitted === null) {
                    continue;
                }

                WebSetting::put(self::PREFIX . $key, $submitted['id'] ?? null, $submitted['en'] ?? null);
            }
        });

        Alert::success('Success', 'Konten homepage berhasil disimpan');

        return redirect()->route('website.content.edit');
    }
}
