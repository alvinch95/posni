<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\FinanceSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    private function uid(): int
    {
        return Auth::guard('chen')->id();
    }

    public function edit()
    {
        $setting = FinanceSetting::firstOrNew(['chen_user_id' => $this->uid()]);

        return view('finance::settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'max:8'],
            'monthly_spending_target' => ['nullable', 'numeric', 'min:0'],
            'monthly_savings_target' => ['nullable', 'numeric', 'min:0'],
        ]);

        FinanceSetting::updateOrCreate(['chen_user_id' => $this->uid()], $data);

        return redirect()->route('chen.finance.settings.edit')->with('status', 'Pengaturan disimpan.');
    }
}
