<?php

return [
    'key' => 'finance',
    'label' => 'Finance',
    'icon' => '💰',
    'order' => 10,
    'enabled' => true,

    // Sidebar sub-navigation for this module. Rendered by resources/views/chen/partials/nav.blade.php.
    'links' => [
        ['label' => 'Dashboard', 'route' => 'chen.finance.dashboard', 'icon' => '📊'],
        ['label' => 'Transaksi', 'route' => 'chen.finance.transactions.index', 'icon' => '💸'],
        ['label' => 'Berulang', 'route' => 'chen.finance.recurring.index', 'icon' => '🔁'],
        ['label' => 'Kategori', 'route' => 'chen.finance.categories.index', 'icon' => '🏷️'],
        ['label' => 'Pengaturan', 'route' => 'chen.finance.settings.edit', 'icon' => '⚙️'],
    ],
];
