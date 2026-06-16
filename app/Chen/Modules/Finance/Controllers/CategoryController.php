<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    private function uid(): int
    {
        return Auth::guard('chen')->id();
    }

    /** Resolve a category owned by the current user or 404. */
    private function ownedOrFail(int $id): Category
    {
        return Category::where('chen_user_id', $this->uid())->findOrFail($id);
    }

    public function index()
    {
        $categories = Category::where('chen_user_id', $this->uid())
            ->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();

        return view('finance::categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:expense,income'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['chen_user_id'] = $this->uid();
        Category::create($data);

        return redirect()->route('chen.finance.categories.index')->with('status', 'Kategori ditambahkan.');
    }

    public function update(Request $request, int $category)
    {
        $model = $this->ownedOrFail($category);
        $data = $request->validate([
            'type' => ['required', 'in:expense,income'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $model->update($data);

        return redirect()->route('chen.finance.categories.index')->with('status', 'Kategori diperbarui.');
    }

    public function destroy(int $category)
    {
        $this->ownedOrFail($category)->delete(); // soft delete

        return redirect()->route('chen.finance.categories.index')->with('status', 'Kategori dihapus.');
    }
}
