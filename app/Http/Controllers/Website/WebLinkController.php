<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebLink;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * Marketplace and social links. There is no index page: the list lives on the contact page
 * (WebContactController), and every action comes back there.
 */
class WebLinkController extends Controller
{
    public function create()
    {
        return view('dashboard.website.links.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        WebLink::create($this->normalize($request, $data));

        Alert::success('Success', 'Link berhasil ditambahkan');

        return redirect()->route('website.contact.edit');
    }

    public function edit(WebLink $link)
    {
        return view('dashboard.website.links.edit', ['link' => $link]);
    }

    public function update(Request $request, WebLink $link)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $link->update($this->normalize($request, $data));

        Alert::success('Success', 'Link berhasil diperbarui');

        return redirect()->route('website.contact.edit');
    }

    public function destroy(WebLink $link)
    {
        $link->delete();

        Alert::success('Success', 'Link berhasil dihapus');

        return redirect()->route('website.contact.edit');
    }

    private function rules(): array
    {
        return [
            'group' => 'required|in:' . implode(',', array_keys(WebLink::GROUPS)),
            'platform' => 'required|in:' . implode(',', array_keys(WebLink::PLATFORMS)),
            'label' => 'required|max:50',
            'url' => 'required|url|starts_with:http://,https://|max:255',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'group.required' => 'Grup wajib dipilih.',
            'group.in' => 'Grup tidak valid.',
            'platform.required' => 'Platform wajib dipilih.',
            'platform.in' => 'Platform tidak valid.',
            'label.required' => 'Label wajib diisi.',
            'label.max' => 'Label maksimal :max karakter.',
            'url.required' => 'URL wajib diisi.',
            'url.url' => 'URL tidak valid. Contoh: https://shopee.co.id/kayuni',
            'url.starts_with' => 'URL harus diawali http:// atau https://',
            'url.max' => 'URL maksimal :max karakter.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
            'sort_order.max' => 'Urutan maksimal :max.',
            'is_active.boolean' => 'Status tidak valid.',
        ];
    }

    /** Defaults for the optional fields. */
    private function normalize(Request $request, array $data): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
