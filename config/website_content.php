<?php

/*
 * Editable homepage copy: the key definitions behind the CMS "Konten Homepage" screen and the
 * "copy" map of GET /api/website/v1/site.
 *
 * Every default_id / default_en is the text the frontend ships in messages/id.json and messages/en.json
 * (the admin screen shows it as the placeholder; the frontend falls back to it while no value is saved).
 * Only keys WITHOUT ICU placeholders ({count}, {year}, ...) belong here, because CMS values may not
 * contain the characters { } < >.
 *
 * Shape (key = next-intl message key, "Namespace.key"):
 *
 *   'sections' => [
 *       'hero' => [
 *           'label' => 'Hero',
 *           'keys' => [
 *               'Hero.eyebrow' => [
 *                   'label' => 'Eyebrow',
 *                   'type' => 'text',            // text | textarea
 *                   'default_id' => '…',
 *                   'default_en' => '…',
 *               ],
 *           ],
 *       ],
 *   ],
 */
return [
    'sections' => [
        'hero' => [
            'label' => 'Hero',
            'keys' => [
                'Hero.eyebrow' => [
                    'label' => 'Eyebrow (teks kecil di atas judul)',
                    'type' => 'text',
                    'default_id' => 'Hampers kayu, dipersonalisasi',
                    'default_en' => 'Personalized wooden hampers',
                ],
                'Hero.headlineLine1' => [
                    'label' => 'Judul baris 1',
                    'type' => 'text',
                    'default_id' => 'Hangatkan momen,',
                    'default_en' => 'Warm the moment,',
                ],
                'Hero.headlineLine2Prefix' => [
                    'label' => 'Judul baris 2: teks sebelum kata tebal',
                    'type' => 'text',
                    'default_id' => 'abadikan dalam ',
                    'default_en' => 'immortalize it in ',
                ],
                'Hero.headlineAccent' => [
                    'label' => 'Judul baris 2: kata tebal',
                    'type' => 'text',
                    'default_id' => 'kayu',
                    'default_en' => 'wood',
                ],
                'Hero.headlineSuffix' => [
                    'label' => 'Judul baris 2: tanda baca di akhir',
                    'type' => 'text',
                    'default_id' => '.',
                    'default_en' => '.',
                ],
                'Hero.sub' => [
                    'label' => 'Deskripsi di bawah judul',
                    'type' => 'textarea',
                    'default_id' => 'Hampers kayu yang bisa kamu personalisasi — dari nama yang terukir, sampai pesan tulus di kartu ucapan.',
                    'default_en' => 'Wooden hampers you can personalize — from engraved names to heartfelt messages on the greeting card.',
                ],
                'Hero.ctaPrimary' => [
                    'label' => 'Tombol utama',
                    'type' => 'text',
                    'default_id' => 'Lihat Koleksi',
                    'default_en' => 'Browse Collection',
                ],
                'Hero.ctaSecondary' => [
                    'label' => 'Tombol kedua',
                    'type' => 'text',
                    'default_id' => 'Bikin Custom',
                    'default_en' => 'Custom Order',
                ],
            ],
        ],
        'trust' => [
            'label' => 'Trust strip',
            'keys' => [
                'Trust.sameDayTitle' => [
                    'label' => 'Poin 1: judul (same-day)',
                    'type' => 'text',
                    'default_id' => 'Same-day Jabodetabek',
                    'default_en' => 'Same-day Jabodetabek',
                ],
                'Trust.sameDaySub' => [
                    'label' => 'Poin 1: keterangan',
                    'type' => 'text',
                    'default_id' => 'Pesan sebelum 14:00',
                    'default_en' => 'Order before 2 PM',
                ],
                'Trust.engraveTitle' => [
                    'label' => 'Poin 2: judul (grafir)',
                    'type' => 'text',
                    'default_id' => 'Grafir nama custom',
                    'default_en' => 'Custom name engraving',
                ],
                'Trust.engraveSub' => [
                    'label' => 'Poin 2: keterangan',
                    'type' => 'text',
                    'default_id' => 'Karakter & font pilihan',
                    'default_en' => 'Your pick of character & font',
                ],
                'Trust.cardTitle' => [
                    'label' => 'Poin 3: judul (kartu ucapan)',
                    'type' => 'text',
                    'default_id' => 'Kartu ucapan free',
                    'default_en' => 'Free greeting card',
                ],
                'Trust.cardSub' => [
                    'label' => 'Poin 3: keterangan',
                    'type' => 'text',
                    'default_id' => 'Tulis pesan kamu',
                    'default_en' => 'Write your message',
                ],
                'Trust.shipTitle' => [
                    'label' => 'Poin 4: judul (pengiriman)',
                    'type' => 'text',
                    'default_id' => 'Kirim seluruh Indonesia',
                    'default_en' => 'Ship across Indonesia',
                ],
                'Trust.shipSub' => [
                    'label' => 'Poin 4: keterangan',
                    'type' => 'text',
                    'default_id' => 'JNE, J&T, SiCepat, dll',
                    'default_en' => 'JNE, J&T, SiCepat, etc.',
                ],
            ],
        ],
        'hero_visual' => [
            'label' => 'Foto hero',
            'keys' => [
                'HeroVisual.alt' => [
                    'label' => 'Deskripsi foto (alt text)',
                    'type' => 'textarea',
                    'default_id' => 'Hampers Kayuni Solar F: gelas kayu dengan grafir nama Juliet, coaster kayu, sendok teh, kartu ucapan Happy Birthday, buket bunga kering — flat-lay di atas kotak kraft.',
                    'default_en' => 'Kayuni Solar F hamper: wooden tumbler engraved with the name Juliet, wooden coaster, tea spoon, Happy Birthday greeting card, dried flower bouquet — flat-lay on a kraft box.',
                ],
                'HeroVisual.bestseller' => [
                    'label' => 'Label bestseller',
                    'type' => 'text',
                    'default_id' => 'Bestseller',
                    'default_en' => 'Bestseller',
                ],
                'HeroVisual.productName' => [
                    'label' => 'Nama produk di foto',
                    'type' => 'text',
                    'default_id' => 'Solar F',
                    'default_en' => 'Solar F',
                ],
                'HeroVisual.engraveBadgeTitle' => [
                    'label' => 'Badge grafir: judul',
                    'type' => 'text',
                    'default_id' => 'Grafir nama',
                    'default_en' => 'Name engraving',
                ],
                'HeroVisual.engraveBadgeSub' => [
                    'label' => 'Badge grafir: keterangan',
                    'type' => 'text',
                    'default_id' => 'Karakter & font pilihanmu',
                    'default_en' => 'Your character & font',
                ],
                'HeroVisual.untukLabel' => [
                    'label' => 'Label "Untuk"',
                    'type' => 'text',
                    'default_id' => 'Untuk',
                    'default_en' => 'For',
                ],
                'HeroVisual.recipientName' => [
                    'label' => 'Nama penerima contoh',
                    'type' => 'text',
                    'default_id' => 'Juliet ♡',
                    'default_en' => 'Juliet ♡',
                ],
                'HeroVisual.materialBadgeTitle' => [
                    'label' => 'Badge bahan: judul',
                    'type' => 'text',
                    'default_id' => 'Kayu Solid',
                    'default_en' => 'Solid Wood',
                ],
                'HeroVisual.materialBadgeSub' => [
                    'label' => 'Badge bahan: keterangan',
                    'type' => 'text',
                    'default_id' => 'Mahoni pilihan',
                    'default_en' => 'Premium mahogany',
                ],
            ],
        ],
        'top_products' => [
            'label' => 'Hampers Pilihan',
            'keys' => [
                'TopProducts.sectionEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'Hampers Pilihan',
                    'default_en' => 'Featured Hampers',
                ],
                'TopProducts.sectionTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Yang lagi banyak dicari',
                    'default_en' => 'What everyone\'s loving',
                ],
                'TopProducts.sectionSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'text',
                    'default_id' => 'Pilihan favorit dari customer Kayuni — semua bisa di-personalisasi.',
                    'default_en' => 'Customer favorites from Kayuni — all can be personalized.',
                ],
            ],
        ],
        'categories' => [
            'label' => 'Kategori',
            'keys' => [
                'ShopByCategory.sectionEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'Cari berdasarkan momen',
                    'default_en' => 'Shop by occasion',
                ],
                'ShopByCategory.sectionTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Setiap occasion, ada hampers-nya',
                    'default_en' => 'Every moment has its hamper',
                ],
                'ShopByCategory.sectionSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'text',
                    'default_id' => 'Geser ke samping buat lihat semua kategori.',
                    'default_en' => 'Swipe sideways to see all categories.',
                ],
            ],
        ],
        'cara_pesan' => [
            'label' => 'Cara Pesan',
            'keys' => [
                'CaraPesan.sectionEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'Cara pesan',
                    'default_en' => 'How it works',
                ],
                'CaraPesan.sectionTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Empat langkah, hampers siap di tangan',
                    'default_en' => 'Four steps to your hamper',
                ],
                'CaraPesan.sectionSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'text',
                    'default_id' => 'Mulai dari pilih sampai sampai — tenang, kami bantu setiap langkahnya.',
                    'default_en' => 'From browsing to delivery — we\'ll help you through every step.',
                ],
                'CaraPesan.step1Title' => [
                    'label' => 'Langkah 1: judul',
                    'type' => 'text',
                    'default_id' => 'Pilih hampers',
                    'default_en' => 'Pick a hamper',
                ],
                'CaraPesan.step1Desc' => [
                    'label' => 'Langkah 1: deskripsi',
                    'type' => 'textarea',
                    'default_id' => 'Pilih paket favorit kamu dari koleksi, atau cerita kebutuhan kamu kalau mau bikin custom.',
                    'default_en' => 'Choose your favorite package from the collection, or tell us what you need for a custom one.',
                ],
                'CaraPesan.step2Title' => [
                    'label' => 'Langkah 2: judul',
                    'type' => 'text',
                    'default_id' => 'Chat via WhatsApp',
                    'default_en' => 'Chat on WhatsApp',
                ],
                'CaraPesan.step2Desc' => [
                    'label' => 'Langkah 2: deskripsi',
                    'type' => 'textarea',
                    'default_id' => 'Klik Pesan via WhatsApp, lalu kirim nama yang mau digrafir dan pesan buat kartu ucapan.',
                    'default_en' => 'Tap Order via WhatsApp, then send us the name to engrave and your greeting-card message.',
                ],
                'CaraPesan.step3Title' => [
                    'label' => 'Langkah 3: judul',
                    'type' => 'text',
                    'default_id' => 'Bayar',
                    'default_en' => 'Pay',
                ],
                'CaraPesan.step3Desc' => [
                    'label' => 'Langkah 3: deskripsi',
                    'type' => 'textarea',
                    'default_id' => 'Tim CS kirim total dan cara pembayaran. Setelah pembayaran dikonfirmasi, hampers kamu langsung kami siapkan.',
                    'default_en' => 'Our CS team sends the total and payment details. Once payment is confirmed, we start preparing your hamper right away.',
                ],
                'CaraPesan.step4Title' => [
                    'label' => 'Langkah 4: judul',
                    'type' => 'text',
                    'default_id' => 'Terima',
                    'default_en' => 'Receive',
                ],
                'CaraPesan.step4Desc' => [
                    'label' => 'Langkah 4: deskripsi',
                    'type' => 'textarea',
                    'default_id' => 'Same-day Jabodetabek atau kirim ke seluruh Indonesia. Selalu rapi sampai tujuan.',
                    'default_en' => 'Same-day Jabodetabek or shipping nationwide. Always arrives in perfect shape.',
                ],
            ],
        ],
        'custom_cta' => [
            'label' => 'CTA Custom',
            'keys' => [
                'CustomCTA.title' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Mau hampers yang gak ada di sini?',
                    'default_en' => 'Looking for something custom?',
                ],
                'CustomCTA.desc' => [
                    'label' => 'Deskripsi',
                    'type' => 'textarea',
                    'default_id' => 'Cerita aja kebutuhan kamu — tema, budget, jumlah. Kami bantu bikin hampers yang pas, dari 1 box buat orang tersayang sampai puluhan untuk kantor.',
                    'default_en' => 'Just tell us what you need — theme, budget, quantity. We\'ll help craft the perfect hamper, from a single gift to dozens for your office.',
                ],
                'CustomCTA.ctaWa' => [
                    'label' => 'Tombol WhatsApp',
                    'type' => 'text',
                    'default_id' => 'Chat lewat WhatsApp',
                    'default_en' => 'Chat on WhatsApp',
                ],
                'CustomCTA.ctaCustom' => [
                    'label' => 'Tombol custom',
                    'type' => 'text',
                    'default_id' => 'Bikin custom',
                    'default_en' => 'Build custom',
                ],
            ],
        ],
        'testimonials' => [
            'label' => 'Testimoni (judul)',
            'keys' => [
                'Testimonials.sectionEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'Cerita customer',
                    'default_en' => 'Customer stories',
                ],
                'Testimonials.sectionTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Dari yang udah belanja',
                    'default_en' => 'From people who\'ve shopped',
                ],
                'Testimonials.sectionSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'text',
                    'default_id' => 'Apa kata mereka tentang Kayuni.',
                    'default_en' => 'What they say about Kayuni.',
                ],
            ],
        ],
        'faq' => [
            'label' => 'FAQ (judul)',
            'keys' => [
                'Faq.sectionEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'FAQ',
                    'default_en' => 'FAQ',
                ],
                'Faq.sectionTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Yang sering ditanyain',
                    'default_en' => 'Frequently asked',
                ],
                'Faq.sectionSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'text',
                    'default_id' => 'Belum nemu jawabannya? Chat kami di WhatsApp, ya.',
                    'default_en' => 'Can\'t find your answer? Chat with us on WhatsApp.',
                ],
            ],
        ],
        'koleksi' => [
            'label' => 'Halaman Koleksi',
            'keys' => [
                'KoleksiPage.pageEyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default_id' => 'Semua Koleksi',
                    'default_en' => 'Full Collection',
                ],
                'KoleksiPage.pageTitle' => [
                    'label' => 'Judul',
                    'type' => 'text',
                    'default_id' => 'Cari hampers yang pas',
                    'default_en' => 'Find the right hamper',
                ],
                'KoleksiPage.pageSub' => [
                    'label' => 'Sub-judul',
                    'type' => 'textarea',
                    'default_id' => 'Geser, filter, dan personalisasi. Setiap item bisa kami grafir nama dan disertakan kartu ucapan.',
                    'default_en' => 'Browse, filter, and personalize. Every item can be name-engraved and includes a free greeting card.',
                ],
            ],
        ],
        'footer' => [
            'label' => 'Footer',
            'keys' => [
                'Footer.tagline' => [
                    'label' => 'Tagline',
                    'type' => 'text',
                    'default_id' => 'Kado kayu, untuk yang tersayang.',
                    'default_en' => 'Wooden gifts, for the ones you love.',
                ],
            ],
        ],
    ],
];
