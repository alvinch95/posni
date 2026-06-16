<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\FinanceSetting;
use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RecurringController extends Controller
{
    private function uid(): int
    {
        return Auth::guard('chen')->id();
    }

    private function ownedOrFail(int $id): RecurringRule
    {
        return RecurringRule::where('chen_user_id', $this->uid())->findOrFail($id);
    }

    public function index()
    {
        $rules = RecurringRule::with('category')->where('chen_user_id', $this->uid())
            ->orderByDesc('active')->orderBy('next_run_date')->get();
        $categories = Category::where('chen_user_id', $this->uid())->orderBy('name')->get();
        $currency = FinanceSetting::where('chen_user_id', $this->uid())->value('currency') ?? 'IDR';

        return view('finance::recurring.index', compact('rules', 'categories', 'currency'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['chen_user_id'] = $this->uid();
        $data['next_run_date'] = $data['start_date']; // first run is the start date
        $data['active'] = true;
        RecurringRule::create($data);

        return redirect()->route('chen.finance.recurring.index')->with('status', 'Aturan berulang dibuat.');
    }

    public function update(Request $request, int $rule)
    {
        $model = $this->ownedOrFail($rule);
        $model->update($this->validateData($request));

        return redirect()->route('chen.finance.recurring.index')->with('status', 'Aturan diperbarui.');
    }

    public function toggle(int $rule)
    {
        $model = $this->ownedOrFail($rule);
        $model->active = ! $model->active;
        $model->save();

        return redirect()->route('chen.finance.recurring.index')->with('status', 'Status aturan diubah.');
    }

    public function destroy(int $rule)
    {
        $this->ownedOrFail($rule)->delete();

        return redirect()->route('chen.finance.recurring.index')->with('status', 'Aturan dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:expense,income'],
            'fin_category_id' => [
                'required',
                Rule::exists('fin_categories', 'id')->where('chen_user_id', $this->uid()),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'frequency' => ['required', 'in:weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
