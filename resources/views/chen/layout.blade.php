<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chen')</title>
    <link rel="stylesheet" href="{{ asset('chen/app.css') }}?v={{ @filemtime(public_path('chen/app.css')) ?: '1' }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<div x-data="{ open: false }" class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-60 shrink-0 bg-slate-900 text-slate-100 min-h-screen">
        @include('chen::partials.nav')
    </aside>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-64 bg-slate-900 text-slate-100">
            @include('chen::partials.nav')
        </aside>
    </div>

    <div class="flex-1 min-w-0">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex items-center gap-3 bg-white border-b border-slate-200 px-4 h-14">
            <button @click="open = true" class="lg:hidden p-2.5 -ml-2 rounded-lg hover:bg-slate-100" aria-label="Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <span class="font-semibold tracking-tight">@yield('heading', 'Chen')</span>
            <form method="POST" action="{{ route('chen.logout') }}" class="ml-auto">
                @csrf
                <button class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900 px-3 py-2 rounded-lg hover:bg-slate-100">Keluar</button>
            </form>
        </header>

        <main class="p-4 sm:p-6 max-w-6xl mx-auto">
            @include('chen::partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
