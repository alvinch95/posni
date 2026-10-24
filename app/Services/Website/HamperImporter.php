<?php

namespace App\Services\Website;

use App\Models\Hamper;
use App\Models\Serie;
use App\Models\WebProduct;
use App\Models\WebProductOption;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills "Produk Website" from the hampers that already exist in posni, as drafts by default.
 *
 * Hampers named "<base> Grafir" and "<base> Tanpa Grafir" of one serie are the two options of one
 * product; any other hamper is a single engraved option. Shared by the CMS button and the
 * website:import-hampers command, so both always agree (preview() is exactly what import() creates).
 *
 * Nothing existing is ever touched: a hamper that a live (non-deleted) web product already uses is
 * left alone, and so is the whole group it belongs to, so the import can never create a duplicate.
 * It only reads posni tables and only inserts web_* rows.
 */
class HamperImporter
{
    /** "<base> Grafir|Tanpa Grafir [(variant)]": the suffix sits between the base name and an optional variant. */
    private const NAME_PATTERN = '/^(.*?)\s+(Tanpa Grafir|Grafir)(\s*\(.*\))?\s*$/i';

    /** The auto-created serie that wraps every single item as a hamper; not a real hamper to sell. */
    private const ITEM_SERIE = 'item';

    /** web_products.slug is varchar(255); leave room for a "-123" suffix. */
    private const MAX_SLUG = 200;

    private const KINDS = ['engraved', 'plain']; // option order

    /**
     * Split a hamper name into its product base name and the option kind it stands for.
     *
     *   "Solar F Grafir"             -> Solar F, engraved
     *   "Solar F Tanpa Grafir"       -> Solar F, plain
     *   "Miranda E Grafir (Putih)"   -> Miranda E (Putih), engraved
     *   "Kappa A"                    -> Kappa A, engraved (a single-name hamper is sold engraved)
     *
     * @return array{base: string, kind: string}
     */
    public static function parseName(string $hamperName): array
    {
        if (preg_match(self::NAME_PATTERN, $hamperName, $match) === 1) {
            $base = trim($match[1] . ($match[3] ?? ''));

            // " Grafir" on its own would leave nothing to call the product.
            if ($base !== '') {
                return [
                    'base' => $base,
                    'kind' => strcasecmp($match[2], 'Tanpa Grafir') === 0 ? 'plain' : 'engraved',
                ];
            }
        }

        return ['base' => trim($hamperName), 'kind' => 'engraved'];
    }

    /**
     * One row per serie (all series except Item when $serieIds is null), sorted by name:
     * serie_id, serie, hampers (importable ones), linked (of those, already on the website) and
     * to_create (products an import would create) and partial (groups skipped because some hamper
     * of the product is on the website already, see import()). Writes nothing.
     *
     * @param  array<int, int|string>|null  $serieIds
     * @return array<int, array{serie_id: int, serie: string, hampers: int, linked: int, to_create: int, partial: int}>
     */
    public function preview(?array $serieIds = null): array
    {
        return $this->plan($serieIds)['rows'];
    }

    /**
     * Create the products the preview announces, in one transaction (all or nothing).
     *
     * created / products: what was made. skipped_linked: groups whose hampers are all on the website
     * already; skipped_partial: groups where only some are, or whose product is on the website through
     * a hamper that was deleted or priced at 0 since (skipped whole, never half-duplicated);
     * skipped_duplicate: hampers dropped because a lower-id hamper of the same kind was kept;
     * skipped_invalid: hampers with a price that is empty or not above zero.
     *
     * @param  array<int, int|string>|null  $serieIds
     * @return array{created: int, skipped_linked: int, skipped_partial: int, skipped_duplicate: int, skipped_invalid: int, products: array<int, string>}
     */
    public function import(?array $serieIds = null, bool $publish = false): array
    {
        return DB::transaction(function () use ($serieIds, $publish) {
            $plan = $this->plan($serieIds);

            $this->loadContents($plan['groups']);
            $taken = array_fill_keys(
                WebProduct::withTrashed()->pluck('slug')->map(fn ($slug) => mb_strtolower((string) $slug))->all(),
                true
            );

            $names = [];
            foreach ($plan['groups'] as $group) {
                $product = WebProduct::create([
                    'slug' => $this->uniqueSlug($group['name'], $taken),
                    'name_id' => $group['name'],
                    'name_en' => null,
                    'description_id' => null,
                    'description_en' => null,
                    'contents_id' => $this->contents($group),
                    'is_published' => $publish,
                    'is_bestseller' => false,
                    'is_featured' => false,
                    'sort_order' => 0,
                ]);

                foreach (self::KINDS as $order => $kind) {
                    if ($group['slots'][$kind] === null) {
                        continue;
                    }

                    WebProductOption::create([
                        'web_product_id' => $product->id,
                        'kind' => $kind,
                        'hamper_id' => $group['slots'][$kind]->id,
                        'price_override' => null,
                        'sort_order' => $order,
                    ]);
                }

                $names[] = $group['name'];
            }

            return [
                'created' => count($names),
                'skipped_linked' => $plan['skipped_linked'],
                'skipped_partial' => $plan['skipped_partial'],
                'skipped_duplicate' => $plan['skipped_duplicate'],
                'skipped_invalid' => $plan['skipped_invalid'],
                'products' => $names,
            ];
        });
    }

    /**
     * The series an import can use (every serie except Item), as [id => name] sorted by name.
     *
     * @return array<int, string>
     */
    public function importableSeries(): array
    {
        return $this->series(null)->all();
    }

    /**
     * One Indonesian sentence for the result of import(), shared by the CMS flash and the command.
     *
     * @param  array{created: int, skipped_linked: int, skipped_partial: int, skipped_duplicate: int, skipped_invalid: int}  $result
     */
    public static function summarize(array $result, bool $published = false): string
    {
        $parts = [];

        if ($result['created'] > 0) {
            $parts[] = $result['created'] . ' produk dibuat ' . ($published ? 'dan langsung publish' : 'sebagai draft');
        } else {
            $parts[] = 'Tidak ada produk baru yang dibuat';
        }

        if ($result['skipped_linked'] > 0) {
            $parts[] = $result['skipped_linked'] . ' dilewati (sudah ada di website)';
        }
        if ($result['skipped_partial'] > 0) {
            $parts[] = $result['skipped_partial'] . ' dilewati (sebagian sudah di website, cek manual)';
        }
        if ($result['skipped_duplicate'] > 0) {
            $parts[] = $result['skipped_duplicate'] . ' hamper kembar dilewati';
        }
        if ($result['skipped_invalid'] > 0) {
            $parts[] = $result['skipped_invalid'] . ' hamper dilewati (harga kosong atau 0)';
        }

        return implode(' · ', $parts);
    }

    /**
     * The single source of truth behind preview() and import(): which hampers are importable, how
     * they group into products, and which groups are skipped and why.
     *
     * @param  array<int, int|string>|null  $serieIds
     */
    private function plan(?array $serieIds): array
    {
        $series = $this->series($serieIds);
        $rank = array_flip($series->keys()->all()); // serie id => position in name order

        $hampers = Hamper::query() // soft-deleted hampers are left out by the model's scope
            ->whereIn('serie_id', $series->keys()->all())
            ->orderBy('id')
            ->get(['id', 'serie_id', 'name', 'selling_price']);

        [$eligible, $invalid] = $hampers->partition(fn (Hamper $hamper) => (int) $hamper->selling_price > 0);
        $live = $this->linked();
        $linked = $live['ids'];

        // Group by serie + lowercase base name; the lowest id wins a kind slot, later same-kind ones are duplicates.
        $groups = [];
        foreach ($eligible as $hamper) {
            $parsed = self::parseName((string) $hamper->name);
            $key = self::groupKey($hamper->serie_id, $parsed['base']);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'serie_id' => (int) $hamper->serie_id,
                    'hampers' => [],
                    'slots' => ['engraved' => null, 'plain' => null],
                    'bases' => ['engraved' => null, 'plain' => null],
                    'duplicates' => 0,
                ];
            }

            $groups[$key]['hampers'][] = $hamper;

            if ($groups[$key]['slots'][$parsed['kind']] === null) {
                $groups[$key]['slots'][$parsed['kind']] = $hamper;
                $groups[$key]['bases'][$parsed['kind']] = $parsed['base'];
            } else {
                $groups[$key]['duplicates']++;
            }
        }

        $create = [];
        $skippedLinked = 0;
        $skippedPartial = 0;
        $skippedDuplicate = 0;
        $toCreate = [];
        $partialPerSerie = [];

        foreach ($groups as $key => $group) {
            $linkedCount = 0;
            foreach ($group['hampers'] as $hamper) {
                if (isset($linked[(int) $hamper->id])) {
                    $linkedCount++;
                }
            }

            if ($linkedCount === 0 && ! isset($live['keys'][$key])) {
                // Engraved wins the display name, as it does the contents order.
                $group['name'] = $group['bases']['engraved'] ?? $group['bases']['plain'];
                $create[] = $group;
                $skippedDuplicate += $group['duplicates'];
                $toCreate[$group['serie_id']] = ($toCreate[$group['serie_id']] ?? 0) + 1;
            } elseif ($linkedCount > 0 && $linkedCount === count($group['hampers'])) {
                $skippedLinked++;
            } else {
                // Some hamper of the product is on the website (here, or deleted/priced at 0 since):
                // never half-duplicate it, the owner sorts it out by hand.
                $skippedPartial++;
                $partialPerSerie[$group['serie_id']] = ($partialPerSerie[$group['serie_id']] ?? 0) + 1;
            }
        }

        usort($create, function (array $a, array $b) use ($rank) {
            return [$rank[$a['serie_id']], mb_strtolower($a['name']), $a['name']]
                <=> [$rank[$b['serie_id']], mb_strtolower($b['name']), $b['name']];
        });

        $count = [];
        $linkedPerSerie = [];
        foreach ($eligible as $hamper) {
            $count[(int) $hamper->serie_id] = ($count[(int) $hamper->serie_id] ?? 0) + 1;
            if (isset($linked[(int) $hamper->id])) {
                $linkedPerSerie[(int) $hamper->serie_id] = ($linkedPerSerie[(int) $hamper->serie_id] ?? 0) + 1;
            }
        }

        $rows = [];
        foreach ($series as $id => $name) {
            $rows[] = [
                'serie_id' => (int) $id,
                'serie' => (string) $name,
                'hampers' => $count[$id] ?? 0,
                'linked' => $linkedPerSerie[$id] ?? 0,
                'to_create' => $toCreate[$id] ?? 0,
                'partial' => $partialPerSerie[$id] ?? 0,
            ];
        }

        return [
            'rows' => $rows,
            'groups' => $create,
            'skipped_linked' => $skippedLinked,
            'skipped_partial' => $skippedPartial,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_invalid' => $invalid->count(),
        ];
    }

    /**
     * Serie id => name for the series an import may use: every serie except Item (any case), or only
     * the requested ones, sorted by name without regard to case.
     *
     * @param  array<int, int|string>|null  $serieIds
     * @return Collection<int, string>
     */
    private function series(?array $serieIds): Collection
    {
        $query = Serie::query()->whereRaw('LOWER(TRIM(name)) <> ?', [self::ITEM_SERIE]);

        if ($serieIds !== null) {
            $query->whereIn('id', array_map('intval', $serieIds));
        }

        return $query->get(['id', 'name'])
            ->sort(function (Serie $a, Serie $b) {
                return [mb_strtolower((string) $a->name), (int) $a->id] <=> [mb_strtolower((string) $b->name), (int) $b->id];
            })
            ->mapWithKeys(fn (Serie $serie) => [(int) $serie->id => (string) $serie->name]);
    }

    /**
     * What live products already use. A soft-deleted product does not count (its relation is hidden
     * by the SoftDeletes scope), so its hampers can be imported again.
     *
     * ids:  [hamperId => true] for every hamper a live product points at.
     * keys: [groupKey => true] for the same hampers, looked up with withTrashed and at any price, so
     *       a product whose hamper was deleted in posni (or priced at 0) since still owns its group:
     *       the unlinked twin ("... Tanpa Grafir") must not be imported as a second product.
     *
     * @return array{ids: array<int, true>, keys: array<string, true>}
     */
    private function linked(): array
    {
        $ids = WebProductOption::query()
            ->whereHas('product')
            ->pluck('hamper_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $keys = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (Hamper::withTrashed()->whereIn('id', $chunk)->get(['id', 'serie_id', 'name']) as $hamper) {
                $keys[self::groupKey($hamper->serie_id, self::parseName((string) $hamper->name)['base'])] = true;
            }
        }

        return ['ids' => array_fill_keys($ids, true), 'keys' => $keys];
    }

    /** serie + lowercase base name (inner whitespace collapsed): what makes two hampers one product. */
    private static function groupKey($serieId, string $base): string
    {
        return $serieId . '|' . mb_strtolower(preg_replace('/\s+/u', ' ', $base));
    }

    /**
     * Eager load the items of every hamper that will be imported, in one go. Soft-deleted details and
     * items drop out through the models' scopes, the same source as the form's "Isi dari posni".
     *
     * @param  array<int, array<string, mixed>>  $groups
     */
    private function loadContents(array $groups): void
    {
        $kept = [];
        foreach ($groups as $group) {
            foreach (self::KINDS as $kind) {
                if ($group['slots'][$kind] !== null) {
                    $kept[] = $group['slots'][$kind];
                }
            }
        }

        (new EloquentCollection($kept))->load([
            'details' => fn ($details) => $details->orderBy('id'),
            'details.item',
        ]);
    }

    /**
     * Unique item names, one per line: the engraved hamper's items in its order, then whatever only
     * the plain hamper adds. Null when there is nothing to list.
     *
     * @param  array<string, mixed>  $group
     */
    private function contents(array $group): ?string
    {
        $names = [];

        foreach (self::KINDS as $kind) {
            $hamper = $group['slots'][$kind];
            if ($hamper === null) {
                continue;
            }

            foreach ($hamper->details as $detail) {
                $name = $detail->item ? trim((string) $detail->item->name) : '';
                if ($name !== '') {
                    $names[$name] = true;
                }
            }
        }

        return $names === [] ? null : implode("\n", array_map('strval', array_keys($names)));
    }

    /**
     * Str::slug of the name, with -2, -3, ... appended until no row, soft-deleted ones included
     * (the column is unique across them), uses it. Records the slug as taken.
     *
     * @param  array<string, true>  $taken  lowercase slugs in use
     */
    private function uniqueSlug(string $name, array &$taken): string
    {
        $slug = rtrim(Str::limit(Str::slug($name), self::MAX_SLUG, ''), '-');
        if ($slug === '') {
            $slug = 'produk';
        }

        $candidate = $slug;
        for ($n = 2; isset($taken[$candidate]); $n++) {
            $candidate = $slug . '-' . $n;
        }

        $taken[$candidate] = true;

        return $candidate;
    }
}
