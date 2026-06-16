<?php

namespace App\Chen\Support;

class ModuleRegistry
{
    /**
     * Discover module manifests under app/Chen/Modules/<Module>/module.php.
     * Each manifest returns an array with at least: key, label, icon, order, enabled, path.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $base = app_path('Chen/Modules');
        if (! is_dir($base)) {
            return [];
        }

        $modules = [];
        foreach (glob($base . '/*/module.php') as $manifestFile) {
            $manifest = require $manifestFile;
            $manifest['path'] = dirname($manifestFile);
            $modules[] = $manifest;
        }

        $modules = array_values(array_filter($modules, fn ($m) => ($m['enabled'] ?? false) === true));
        usort($modules, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $modules;
    }
}
