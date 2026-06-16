<div class="px-5 h-14 flex items-center text-lg font-bold tracking-tight border-b border-white/10">
    Chen
</div>
<nav class="p-3 space-y-5">
    @php($modules = app(\App\Chen\Support\ModuleRegistry::class)->all())
    @forelse ($modules as $module)
        <div>
            <div class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <span>{{ $module['icon'] ?? '•' }}</span>
                <span>{{ $module['label'] }}</span>
            </div>
            <div class="mt-1 space-y-1">
                @forelse (($module['links'] ?? []) as $link)
                    @php($isIndex = \Illuminate\Support\Str::endsWith($link['route'], '.index'))
                    @php($active = request()->routeIs($link['route'])
                        || ($isIndex && request()->routeIs(\Illuminate\Support\Str::beforeLast($link['route'], '.index') . '.*')))
                    <a href="{{ route($link['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm
                              {{ $active ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <span class="w-5 text-center">{{ $link['icon'] ?? '' }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @empty
                    {{-- Module declared no links: fall back to a single link to its dashboard. --}}
                    <a href="{{ route('chen.' . $module['key'] . '.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm
                              {{ request()->routeIs('chen.' . $module['key'] . '.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <span>{{ $module['label'] }}</span>
                    </a>
                @endforelse
            </div>
        </div>
    @empty
        <p class="px-3 py-2 text-sm text-slate-400">Belum ada modul.</p>
    @endforelse
</nav>
