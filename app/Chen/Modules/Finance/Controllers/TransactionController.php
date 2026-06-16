<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    private function uid(): int
    {
        return Auth::guard('chen')->id();
    }

    public function index(Request $request)
    {
        $month = $request->query('month'); // format YYYY-MM
        $type = $request->query('type');   // expense|income|null
        $categoryId = $request->query('category');
        $search = $request->query('q');

        $query = Transaction::with('category')->where('chen_user_id', $this->uid());

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$y, $m] = explode('-', $month);
            $query->whereYear('date', $y)->whereMonth('date', $m);
        }
        if (in_array($type, ['expense', 'income'], true)) {
            $query->where('type', $type);
        }
        if ($categoryId) {
            $query->where('fin_category_id', $categoryId);
        }
        if ($search) {
            $query->where('notes', 'like', '%' . $search . '%');
        }

        // Compute totals from the filtered set BEFORE paginating (paginate() mutates the builder).
        $incomeTotal = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expenseTotal = (float) (clone $query)->where('type', 'expense')->sum('amount');
        $net = $incomeTotal - $expenseTotal;

        $transactions = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();
        $categories = Category::where('chen_user_id', $this->uid())->orderBy('name')->get();

        return view('finance::transactions.index', compact(
            'transactions', 'incomeTotal', 'expenseTotal', 'net', 'categories', 'month', 'type'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['chen_user_id'] = $this->uid();
        Transaction::create($data);

        return redirect()->route('chen.finance.transactions.index')->with('status', 'Transaksi disimpan.');
    }

    public function update(Request $request, int $transaction)
    {
        $model = Transaction::where('chen_user_id', $this->uid())->findOrFail($transaction);
        $model->update($this->validateData($request));

        return redirect()->route('chen.finance.transactions.index')->with('status', 'Transaksi diperbarui.');
    }

    public function destroy(int $transaction)
    {
        Transaction::where('chen_user_id', $this->uid())->findOrFail($transaction)->delete();

        return redirect()->route('chen.finance.transactions.index')->with('status', 'Transaksi dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:expense,income'],
            'fin_category_id' => [
                'required',
                Rule::exists('fin_categories', 'id')->where('chen_user_id', $this->uid()),
            ],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
