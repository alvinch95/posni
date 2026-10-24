<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\HamperImporter;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * "Import dari hampers": preview what would be created per serie, then create it as drafts.
 * All the logic lives in HamperImporter (shared with the website:import-hampers command).
 */
class WebProductImportController extends Controller
{
    public function create(HamperImporter $importer)
    {
        $rows = $importer->preview();

        return view('dashboard.website.products.import', [
            'rows' => $rows,
            'importable' => array_sum(array_column($rows, 'to_create')),
        ]);
    }

    public function store(Request $request, HamperImporter $importer)
    {
        $data = $request->validate([
            'serie_ids' => ['required', 'array', 'min:1'],
            'serie_ids.*' => ['integer', 'exists:series,id'],
        ], [
            'serie_ids.required' => 'Pilih minimal satu serie.',
            'serie_ids.array' => 'Pilihan serie tidak valid.',
            'serie_ids.min' => 'Pilih minimal satu serie.',
            'serie_ids.*.integer' => 'Serie yang dipilih tidak valid.',
            'serie_ids.*.exists' => 'Serie yang dipilih tidak ditemukan.',
        ]);

        try {
            // Always drafts: the list has the bulk "Publish" for after a look.
            $result = $importer->import(array_values(array_unique(array_map('intval', $data['serie_ids']))), false);
        } catch (\Throwable $e) {
            report($e); // the import is one transaction, so nothing was saved

            Alert::error('Error', 'Import gagal dan tidak ada data yang disimpan. Coba lagi, atau hubungi developer kalau masih gagal.');

            return redirect()->route('website.products.import');
        }

        $summary = HamperImporter::summarize($result);

        if ($result['created'] > 0) {
            Alert::success('Success', $summary);
        } else {
            Alert::info('Info', $summary);
        }

        return redirect()->route('website.products.index', ['status' => 'draft']);
    }
}
