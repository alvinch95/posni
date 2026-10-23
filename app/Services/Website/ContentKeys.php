<?php

namespace App\Services\Website;

/**
 * The editable homepage-copy keys, read from config/website_content.php. This is the allow-list
 * for what may appear in the public "copy" map: a web_settings row is only exposed when its
 * "copy.<key>" is defined here.
 */
class ContentKeys
{
    /**
     * Flat map of next-intl key => definition, e.g.
     * 'Hero.eyebrow' => ['section' => 'hero', 'label' => '…', 'type' => 'text', 'default_id' => '…', 'default_en' => '…'].
     *
     * @return array<string, array{section:string, label:string, type:string, default_id:string, default_en:string}>
     */
    public static function all(): array
    {
        $keys = [];

        foreach (self::sections() as $section => $definition) {
            foreach (($definition['keys'] ?? []) as $key => $field) {
                $keys[(string) $key] = [
                    'section' => (string) $section,
                    'label' => (string) ($field['label'] ?? $key),
                    'type' => ($field['type'] ?? 'text') === 'textarea' ? 'textarea' : 'text',
                    'default_id' => (string) ($field['default_id'] ?? ''),
                    'default_en' => (string) ($field['default_en'] ?? ''),
                ];
            }
        }

        return $keys;
    }

    /**
     * The config as-is: section => ['label' => string, 'keys' => [key => field definition]].
     *
     * @return array<string, array<string, mixed>>
     */
    public static function sections(): array
    {
        return (array) config('website_content.sections', []);
    }
}
