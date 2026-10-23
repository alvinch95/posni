<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebLink;
use App\Models\WebSetting;
use App\Services\Website\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class WebContactController extends Controller
{
    /** Mirrors the frontend's built-in template, used while no order template is set; shown as the input placeholder. */
    public const DEFAULT_ORDER_TEMPLATE = 'Halo Kayuni! Aku mau pesan {product} ({option}) — {price}. {url}';

    private const INVALID_NUMBER = 'Nomor WhatsApp tidak valid. Contoh: 6281234567890';

    /** Form field base name => [web_settings key, max length]. Each posts as {field}_id and {field}_en. */
    private const TEXT_FIELDS = [
        'whatsapp_order_template' => ['contact.whatsapp_order_template', 500],
        'whatsapp_general_message' => ['contact.whatsapp_general_message', 500],
        'address' => ['contact.address', 255],
    ];

    private const NUMBER_KEY = 'contact.whatsapp_number';

    public function edit()
    {
        $keys = [self::NUMBER_KEY];
        foreach (self::TEXT_FIELDS as [$key]) {
            $keys[] = $key;
        }
        $settings = WebSetting::whereIn('key', $keys)->get()->keyBy('key');

        // The number has no EN variant (value_id only); the other fields are bilingual.
        $values = ['whatsapp_number' => optional($settings->get(self::NUMBER_KEY))->value_id];
        foreach (self::TEXT_FIELDS as $field => [$key]) {
            $values[$field . '_id'] = optional($settings->get($key))->value_id;
            $values[$field . '_en'] = optional($settings->get($key))->value_en;
        }

        return view('dashboard.website.contact.edit', [
            'values' => $values,
            'defaultOrderTemplate' => self::DEFAULT_ORDER_TEMPLATE,
            'linksByGroup' => WebLink::orderBy('sort_order')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        // put() deletes the row when the value is blank, so an empty field goes back to "not set".
        DB::transaction(function () use ($data) {
            WebSetting::put(self::NUMBER_KEY, PhoneNumber::normalizeWa($data['whatsapp_number'] ?? null));

            foreach (self::TEXT_FIELDS as $field => [$key]) {
                WebSetting::put($key, $data[$field . '_id'] ?? null, $data[$field . '_en'] ?? null);
            }
        });

        Alert::success('Success', 'Kontak berhasil disimpan');

        return redirect()->route('website.contact.edit');
    }

    private function rules(): array
    {
        $rules = [
            // Blank passes (it clears the setting); anything else must normalize to a valid number.
            'whatsapp_number' => ['bail', 'nullable', 'string', function ($attribute, $value, $fail) {
                if (PhoneNumber::normalizeWa($value) === null) {
                    $fail(self::INVALID_NUMBER);
                }
            }],
        ];

        foreach (self::TEXT_FIELDS as $field => [, $max]) {
            $rules[$field . '_id'] = 'nullable|string|max:' . $max;
            $rules[$field . '_en'] = 'nullable|string|max:' . $max;
        }

        return $rules;
    }

    private function messages(): array
    {
        $messages = ['whatsapp_number.string' => self::INVALID_NUMBER];

        $labels = [
            'whatsapp_order_template' => 'Template pesan pesanan',
            'whatsapp_general_message' => 'Pesan WhatsApp umum',
            'address' => 'Alamat',
        ];
        foreach (array_keys(self::TEXT_FIELDS) as $field) {
            foreach (['id' => 'ID', 'en' => 'EN'] as $suffix => $caption) {
                $messages["{$field}_{$suffix}.max"] = "{$labels[$field]} ({$caption}) maksimal :max karakter.";
                $messages["{$field}_{$suffix}.string"] = "{$labels[$field]} ({$caption}) tidak valid.";
            }
        }

        return $messages;
    }
}
