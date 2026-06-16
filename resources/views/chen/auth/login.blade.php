<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Chen</title>
    <link rel="stylesheet" href="{{ asset('chen/app.css') }}">
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-3xl font-bold tracking-tight text-white">Chen</div>
            <p class="text-slate-400 text-sm mt-1">Ruang pribadi kamu</p>
        </div>
        <div class="bg-white rounded-2xl shadow-xl p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="{{ route('chen.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300"> Ingat saya
                </label>
                <button class="w-full bg-slate-900 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-slate-800">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
