<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Chen</title>
    <link rel="stylesheet" href="{{ asset('chen/app.css') }}">
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-5">
    <div class="w-full max-w-sm">
        <div class="text-center mb-7">
            <div class="text-4xl font-extrabold tracking-tight text-white">Chen</div>
            <p class="text-slate-400 text-sm mt-1.5">Ruang pribadi kamu</p>
        </div>
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-7">
            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="{{ route('chen.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="field-label" for="login-username">Username</label>
                    <input id="login-username" type="text" name="username" value="{{ old('username') }}" required autofocus autocapitalize="none" autocomplete="username" class="field">
                </div>
                <div>
                    <label class="field-label" for="login-pass">Password</label>
                    <input id="login-pass" type="password" name="password" required class="field">
                </div>
                <label class="flex items-center gap-2.5 text-sm text-slate-600 py-1">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ingat saya
                </label>
                <button class="btn-primary btn-block" style="min-height:3rem">Masuk</button>
            </form>
        </div>
        <p class="text-center text-xs text-slate-500 mt-5">Akses khusus. Hubungi pemilik untuk akun.</p>
    </div>
</body>
</html>
