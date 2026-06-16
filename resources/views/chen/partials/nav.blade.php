<div class="px-5 h-14 flex items-center text-lg font-bold tracking-tight border-b border-white/10">
    Chen
</div>
<nav class="p-3 space-y-1">
    @php($modules = app(\App\Chen\Support\ModuleRegistry::class)->all())
    @forelse ($modules as $module)
        <a href="{{ route('chen.' . $module['key'] . '.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm
                  {{ request()->routeIs('chen.' . $module['key'] . '.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5' }}">
            <span>{{ $module['icon'] ?? '•' }}</span>
            <span>{{ $module['label'] }}</span>
        </a>
    @empty
        <p class="px-3 py-2 text-sm text-slate-400">Belum ada modul.</p>
    @endforelse
</nav>
