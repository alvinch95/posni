# Chen Platform + Finance Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build "Chen", a personal-app platform inside the existing posni Laravel 8 project — served on its own subdomain, with its own login and visual identity — and ship its first module, Finance (expenses, income, recurring transactions, monthly savings analytics).

**Architecture:** A single `App\Chen\ChenServiceProvider` registers everything additively: a subdomain route group, a separate `chen` auth guard, a `chen`-prefixed migration path, module discovery, console commands, and a daily schedule. Existing posni files are touched in only two places (`config/app.php` providers, `config/auth.php`). Expense and income share one `fin_transactions` table discriminated by a `type` column. Recurring rules materialize real transaction rows via an idempotent generator (scheduled daily + catch-up on dashboard load).

**Tech Stack:** Laravel 8 (PHP 7.3/8), Blade, Tailwind (Play CDN), Alpine.js (CDN), ApexCharts (CDN), PHPUnit + sqlite in-memory for tests.

---

## Conventions for every task

- **Models:** use `protected $guarded = ['id'];` (matches posni). Chen models live under `App\Chen`.
- **Migrations:** Laravel-8 class style (`class CreateX extends Migration { up(); down(); }`). All Chen/Finance migrations go in **`database/migrations/chen/`** (a subfolder), registered by the provider via `loadMigrationsFrom()`. This keeps them out of posni's top-level migration set and lets tests migrate only this path.
- **Factories:** Chen models declare `newFactory()` so factory resolution works outside `App\Models`. Factories live in `database/factories/Chen/`.
- **Tests:** every Chen feature test extends `Tests\Chen\ChenTestCase` (Task 1), which switches the default DB connection to sqlite `:memory:` and migrates only `database/migrations/chen`. **Never touches the shared MySQL.** Run a single test with:
  `vendor/bin/phpunit --filter <TestName>` (on Windows: `php vendor/bin/phpunit --filter <TestName>`).
- **Routes:** all Chen routes are named with the `chen.` prefix; module routes additionally carry their module key prefix (e.g. `chen.finance.transactions.index`).
- **Currency display:** amounts are `DECIMAL(15,2)`; format in Blade with `number_format($v, 0, ',', '.')` (IDR style). Never use float math.
- **Commit** after each task with the message shown in its final step.

---

## Phase A — Platform shell

### Task 1: Test harness + config + service provider skeleton

**Files:**
- Create: `config/chen.php`
- Create: `app/Chen/Support/ModuleRegistry.php`
- Create: `app/Chen/ChenServiceProvider.php`
- Create: `tests/Chen/ChenTestCase.php`
- Create: `database/migrations/chen/.gitkeep`
- Modify: `config/app.php` (providers array)
- Test: `tests/Feature/Chen/ProviderBootTest.php`

- [ ] **Step 1: Create the config file**

`config/chen.php`:
```php
<?php

return [
    // Base domain the Chen subdomain hangs off of. Subdomain = "chen." . this value.
    // Defaults to posni.test so the test suite works with no extra setup.
    'domain' => env('CHEN_DOMAIN', 'posni.test'),
];
```

- [ ] **Step 2: Create the ModuleRegistry**

`app/Chen/Support/ModuleRegistry.php`:
```php
<?php

namespace App\Chen\Support;

class ModuleRegistry
{
    /**
     * Discover module manifests under app/Chen/Modules/<Module>/module.php.
     * Each manifest returns an array with at least: key, label, icon, order, enabled, path.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $base = app_path('Chen/Modules');
        if (! is_dir($base)) {
            return [];
        }

        $modules = [];
        foreach (glob($base . '/*/module.php') as $manifestFile) {
            $manifest = require $manifestFile;
            $manifest['path'] = dirname($manifestFile);
            $modules[] = $manifest;
        }

        $modules = array_values(array_filter($modules, fn ($m) => ($m['enabled'] ?? false) === true));
        usort($modules, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $modules;
    }
}
```

- [ ] **Step 3: Create the service provider**

`app/Chen/ChenServiceProvider.php`:
```php
<?php

namespace App\Chen;

use App\Chen\Support\ModuleRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ChenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry());
    }

    public function boot(): void
    {
        // Migrations live in their own folder so they stay out of posni's top-level set.
        $this->loadMigrationsFrom(database_path('migrations/chen'));

        // Shell views under the "chen" namespace.
        $this->loadViewsFrom(resource_path('views/chen'), 'chen');

        // Each enabled module registers its own view namespace (<key>::view).
        $registry = $this->app->make(ModuleRegistry::class);
        foreach ($registry->all() as $module) {
            if (is_dir($module['path'] . '/Views')) {
                View::addNamespace($module['key'], $module['path'] . '/Views');
            }
        }

        // Subdomain route group — additive, posni's web/api routes are untouched.
        Route::domain('chen.' . config('chen.domain'))
            ->middleware('web')
            ->name('chen.')
            ->group(base_path('routes/chen.php'));

        // Console commands + daily schedule, without editing app/Console/Kernel.php.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Chen\Console\CreateUser::class,
                \App\Chen\Modules\Finance\Console\RunRecurring::class,
            ]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('chen:finance:run-recurring')->dailyAt('00:30');
        });
    }
}
```

> Note: classes referenced in `commands([...])` are created in later tasks. They must exist before `php artisan` runs cleanly; until Task 5 and Task 10, comment out the not-yet-created class lines if you run artisan between tasks. The TDD tests in this task do not invoke those commands.

- [ ] **Step 4: Register the provider**

Modify `config/app.php` — add to the `'providers'` array (after `App\Providers\RouteServiceProvider::class,`):
```php
        App\Chen\ChenServiceProvider::class,
```

- [ ] **Step 5: Create the placeholder migration folder**

Create empty file `database/migrations/chen/.gitkeep` (so the directory exists and `loadMigrationsFrom` does not error).

- [ ] **Step 6: Create the base test case**

`tests/Chen/ChenTestCase.php`:
```php
<?php

namespace Tests\Chen;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

abstract class ChenTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolate from MySQL: run only Chen migrations on an in-memory sqlite db.
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/chen',
            '--database' => 'sqlite',
        ]);
    }

    /** Base URL for the Chen subdomain in tests. */
    protected function chenUrl(string $path = '/'): string
    {
        return 'http://chen.' . config('chen.domain') . $path;
    }
}
```

- [ ] **Step 7: Write the failing test**

`tests/Feature/Chen/ProviderBootTest.php`:
```php
<?php

namespace Tests\Feature\Chen;

use App\Chen\Support\ModuleRegistry;
use Tests\Chen\ChenTestCase;

class ProviderBootTest extends ChenTestCase
{
    public function test_module_registry_is_bound(): void
    {
        $this->assertInstanceOf(ModuleRegistry::class, app(ModuleRegistry::class));
    }

    public function test_chen_domain_config_has_a_value(): void
    {
        $this->assertNotEmpty(config('chen.domain'));
    }
}
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter ProviderBootTest`
Expected: PASS (2 assertions). If `migrate` complains about the empty path, confirm `.gitkeep` exists and `config/database.php` has a `sqlite` connection (Laravel 8 default does).

- [ ] **Step 9: Commit**

```bash
git add config/chen.php config/app.php app/Chen/Support/ModuleRegistry.php app/Chen/ChenServiceProvider.php tests/Chen/ChenTestCase.php tests/Feature/Chen/ProviderBootTest.php database/migrations/chen/.gitkeep
git commit -m "feat(chen): platform service provider, module registry, test harness"
```

---

### Task 2: Chen auth guard, User + Setting models, migrations

**Files:**
- Create: `database/migrations/chen/2026_06_16_000001_create_chen_users_table.php`
- Create: `database/migrations/chen/2026_06_16_000002_create_chen_settings_table.php`
- Create: `app/Chen/Models/User.php`
- Create: `app/Chen/Models/Setting.php`
- Create: `database/factories/Chen/UserFactory.php`
- Modify: `config/auth.php` (guards + providers — additive)
- Test: `tests/Feature/Chen/ChenUserModelTest.php`

- [ ] **Step 1: Create the users migration**

`database/migrations/chen/2026_06_16_000001_create_chen_users_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChenUsersTable extends Migration
{
    public function up()
    {
        Schema::create('chen_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chen_users');
    }
}
```

- [ ] **Step 2: Create the settings migration**

`database/migrations/chen/2026_06_16_000002_create_chen_settings_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChenSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('chen_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('chen_users')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('default_currency', 8)->default('IDR');
            $table->string('locale', 8)->default('id');
            $table->string('theme', 32)->default('light');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chen_settings');
    }
}
```

- [ ] **Step 3: Create the User model**

`app/Chen/Models/User.php`:
```php
<?php

namespace App\Chen\Models;

use Database\Factories\Chen\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'chen_users';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function setting()
    {
        return $this->hasOne(Setting::class, 'user_id');
    }
}
```

- [ ] **Step 4: Create the Setting model**

`app/Chen/Models/Setting.php`:
```php
<?php

namespace App\Chen\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'chen_settings';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

- [ ] **Step 5: Create the User factory**

`database/factories/Chen/UserFactory.php`:
```php
<?php

namespace Database\Factories\Chen;

use App\Chen\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
```

- [ ] **Step 6: Register the guard and provider (additive)**

Modify `config/auth.php`:
- In `'guards'`, after the `web` guard, add:
```php
        'chen' => [
            'driver' => 'session',
            'provider' => 'chen_users',
        ],
```
- In `'providers'`, after the `users` provider, add:
```php
        'chen_users' => [
            'driver' => 'eloquent',
            'model' => App\Chen\Models\User::class,
        ],
```
Do **not** modify the existing `web` guard or `users` provider.

- [ ] **Step 7: Write the failing test**

`tests/Feature/Chen/ChenUserModelTest.php`:
```php
<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Tests\Chen\ChenTestCase;

class ChenUserModelTest extends ChenTestCase
{
    public function test_can_create_chen_user_via_factory(): void
    {
        $user = User::factory()->create(['email' => 'a@b.com']);

        $this->assertDatabaseHas('chen_users', ['email' => 'a@b.com']);
        $this->assertSame('chen_users', $user->getTable());
    }

    public function test_chen_guard_is_configured(): void
    {
        $this->assertSame('chen_users', config('auth.guards.chen.provider'));
        $this->assertSame(User::class, config('auth.providers.chen_users.model'));
    }
}
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter ChenUserModelTest`
Expected: PASS (3 assertions).

- [ ] **Step 9: Commit**

```bash
git add database/migrations/chen app/Chen/Models config/auth.php database/factories/Chen/UserFactory.php tests/Feature/Chen/ChenUserModelTest.php
git commit -m "feat(chen): chen_users + chen_settings, User/Setting models, chen auth guard"
```

---

### Task 3: Shell layout, login routes, authentication flow

**Files:**
- Create: `routes/chen.php`
- Create: `app/Chen/Http/Controllers/Auth/LoginController.php`
- Create: `resources/views/chen/layout.blade.php`
- Create: `resources/views/chen/partials/nav.blade.php`
- Create: `resources/views/chen/partials/flash.blade.php`
- Create: `resources/views/chen/auth/login.blade.php`
- Create: `resources/views/chen/home.blade.php`
- Test: `tests/Feature/Chen/AuthFlowTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/AuthFlowTest.php`:
```php
<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Chen\ChenTestCase;

class AuthFlowTest extends ChenTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get($this->chenUrl('/'))
            ->assertRedirect($this->chenUrl('/login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get($this->chenUrl('/login'))
            ->assertOk()
            ->assertSee('Chen');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'me@chen.app', 'password' => Hash::make('secret123')]);

        $this->post($this->chenUrl('/login'), ['email' => 'me@chen.app', 'password' => 'secret123'])
            ->assertRedirect($this->chenUrl('/'));

        $this->assertAuthenticatedAs(User::first(), 'chen');
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'me@chen.app', 'password' => Hash::make('secret123')]);

        $this->from($this->chenUrl('/login'))
            ->post($this->chenUrl('/login'), ['email' => 'me@chen.app', 'password' => 'wrong'])
            ->assertRedirect($this->chenUrl('/login'));

        $this->assertGuest('chen');
    }

    public function test_authenticated_user_can_reach_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'chen')
            ->get($this->chenUrl('/'))
            ->assertOk()
            ->assertSee('Chen');
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'chen')
            ->post($this->chenUrl('/logout'))
            ->assertRedirect($this->chenUrl('/login'));

        $this->assertGuest('chen');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter AuthFlowTest`
Expected: FAIL (routes/views not defined → 404s / route-not-found).

- [ ] **Step 3: Create the LoginController**

`app/Chen/Http/Controllers/Auth/LoginController.php`:
```php
<?php

namespace App\Chen\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::guard('chen')->check()) {
            return redirect()->route('chen.home');
        }

        return view('chen::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('chen')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('chen.home'));
    }

    public function logout(Request $request)
    {
        Auth::guard('chen')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('chen.login');
    }
}
```

- [ ] **Step 4: Create the route file**

`routes/chen.php`:
```php
<?php

use App\Chen\Http\Controllers\Auth\LoginController;
use App\Chen\Support\ModuleRegistry;
use Illuminate\Support\Facades\Route;

// Guest auth routes
Route::middleware('guest:chen')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated shell + modules
Route::middleware('auth:chen')->group(function () {
    Route::get('/', fn () => view('chen::home'))->name('home');

    // Load each enabled module's routes under its key prefix.
    foreach (app(ModuleRegistry::class)->all() as $module) {
        $routes = $module['path'] . '/routes.php';
        if (file_exists($routes)) {
            Route::prefix($module['key'])
                ->name($module['key'] . '.')
                ->group($routes);
        }
    }
});
```

> The `guest:chen` / `auth:chen` middleware use Laravel's built-in `Authenticate` and `RedirectIfAuthenticated`. Their default redirect for `auth` is `route('login')`; because our routes are name-prefixed `chen.`, ensure the unauthenticated redirect points to `chen.login`. Do this in Step 5.

- [ ] **Step 5: Point unauthenticated redirects at the Chen login**

Modify `app/Http/Middleware/Authenticate.php` — replace the `redirectTo` method body to branch on guard/subdomain (additive, posni's web behavior preserved):
```php
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            if ($request->routeIs('chen.*') || str_starts_with($request->getHost(), 'chen.')) {
                return route('chen.login');
            }
            return route('login');
        }
    }
```

- [ ] **Step 6: Create the layout**

`resources/views/chen/layout.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chen')</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <button @click="open = true" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100" aria-label="Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <span class="font-semibold tracking-tight">@yield('heading', 'Chen')</span>
            <form method="POST" action="{{ route('chen.logout') }}" class="ml-auto">
                @csrf
                <button class="text-sm text-slate-500 hover:text-slate-900">Keluar</button>
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
```

- [ ] **Step 7: Create the nav partial**

`resources/views/chen/partials/nav.blade.php`:
```blade
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
```

- [ ] **Step 8: Create the flash partial**

`resources/views/chen/partials/flash.blade.php`:
```blade
@if (session('status'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
        {{ session('status') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
```

- [ ] **Step 9: Create the login view**

`resources/views/chen/auth/login.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Chen</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
```

- [ ] **Step 10: Create the home view**

`resources/views/chen/home.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Chen')
@section('heading', 'Beranda')
@section('content')
    <div class="rounded-2xl bg-white border border-slate-200 p-6">
        <h1 class="text-xl font-semibold">Halo 👋</h1>
        <p class="text-slate-500 mt-1 text-sm">Pilih modul dari menu samping untuk mulai.</p>
    </div>
@endsection
```

- [ ] **Step 11: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter AuthFlowTest`
Expected: PASS (all assertions). The home route renders "Chen" via the nav; the nav `@forelse` is empty until Task 6 — that's fine.

- [ ] **Step 12: Commit**

```bash
git add routes/chen.php app/Chen/Http/Controllers/Auth app/Http/Middleware/Authenticate.php resources/views/chen tests/Feature/Chen/AuthFlowTest.php
git commit -m "feat(chen): subdomain shell layout, login/logout flow, guarded home"
```

---

### Task 4: `chen:user` account-creation command

**Files:**
- Create: `app/Chen/Console/CreateUser.php`
- Test: `tests/Feature/Chen/CreateUserCommandTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/CreateUserCommandTest.php`:
```php
<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Chen\ChenTestCase;

class CreateUserCommandTest extends ChenTestCase
{
    public function test_command_creates_a_chen_user(): void
    {
        $this->artisan('chen:user', ['email' => 'owner@chen.app'])
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Password', 'topsecret1')
            ->assertExitCode(0);

        $user = User::where('email', 'owner@chen.app')->first();
        $this->assertNotNull($user);
        $this->assertSame('Owner', $user->name);
        $this->assertTrue(Hash::check('topsecret1', $user->password));
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@chen.app']);

        $this->artisan('chen:user', ['email' => 'dupe@chen.app'])
            ->assertExitCode(1);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter CreateUserCommandTest`
Expected: FAIL ("command chen:user is not defined").

- [ ] **Step 3: Implement the command**

`app/Chen/Console/CreateUser.php`:
```php
<?php

namespace App\Chen\Console;

use App\Chen\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'chen:user {email}';
    protected $description = 'Create a Chen platform user (login-only, full access)';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("A Chen user with email {$email} already exists.");
            return 1;
        }

        $name = $this->ask('Name');
        $password = $this->secret('Password') ?: $this->ask('Password');

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Chen user {$email} created.");
        return 0;
    }
}
```

> The test uses `expectsQuestion('Password', ...)`; `secret()` is matched by `expectsQuestion` in PHPUnit, so the `?: $this->ask` fallback is only for interactive terminals. Keep both.

- [ ] **Step 4: Verify the command is registered**

Confirm `App\Chen\Console\CreateUser::class` is in the `commands([...])` array of `app/Chen/ChenServiceProvider.php` (added in Task 1, Step 3). Uncomment if you commented it earlier.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter CreateUserCommandTest`
Expected: PASS (5 assertions across 2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Chen/Console/CreateUser.php tests/Feature/Chen/CreateUserCommandTest.php
git commit -m "feat(chen): chen:user artisan command to create accounts"
```

---

## Phase B — Finance module

### Task 5: Finance module manifest, routes skeleton, nav integration

**Files:**
- Create: `app/Chen/Modules/Finance/module.php`
- Create: `app/Chen/Modules/Finance/routes.php`
- Create: `app/Chen/Modules/Finance/Controllers/DashboardController.php` (stub, fleshed out in Task 11)
- Create: `app/Chen/Modules/Finance/Views/dashboard.blade.php` (stub)
- Test: `tests/Feature/Chen/Finance/ModuleNavTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/ModuleNavTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Support\ModuleRegistry;
use Tests\Chen\ChenTestCase;

class ModuleNavTest extends ChenTestCase
{
    public function test_finance_module_is_discovered(): void
    {
        $keys = array_column(app(ModuleRegistry::class)->all(), 'key');
        $this->assertContains('finance', $keys);
    }

    public function test_finance_dashboard_route_is_guarded_and_renders(): void
    {
        $this->get($this->chenUrl('/finance'))->assertRedirect($this->chenUrl('/login'));

        $this->actingAs(User::factory()->create(), 'chen')
            ->get($this->chenUrl('/finance'))
            ->assertOk()
            ->assertSee('Finance');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter ModuleNavTest`
Expected: FAIL (module not found / route 404).

- [ ] **Step 3: Create the manifest**

`app/Chen/Modules/Finance/module.php`:
```php
<?php

return [
    'key' => 'finance',
    'label' => 'Finance',
    'icon' => '💰',
    'order' => 10,
    'enabled' => true,
];
```

- [ ] **Step 4: Create the routes skeleton**

`app/Chen/Modules/Finance/routes.php`:
```php
<?php

use App\Chen\Modules\Finance\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Already inside prefix "finance" and name "chen.finance." (see routes/chen.php).
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

- [ ] **Step 5: Create the stub controller**

`app/Chen/Modules/Finance/Controllers/DashboardController.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('finance::dashboard');
    }
}
```

- [ ] **Step 6: Create the stub view**

`app/Chen/Modules/Finance/Views/dashboard.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Finance — Chen')
@section('heading', 'Finance')
@section('content')
    <h1 class="text-lg font-semibold">Finance</h1>
@endsection
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter ModuleNavTest`
Expected: PASS. (View namespace `finance::` is registered by the provider at boot from the manifest.)

- [ ] **Step 8: Commit**

```bash
git add app/Chen/Modules/Finance tests/Feature/Chen/Finance/ModuleNavTest.php
git commit -m "feat(finance): module manifest, routes skeleton, dashboard stub"
```

---

### Task 6: Finance migrations, models, factories

**Files:**
- Create: `database/migrations/chen/2026_06_16_000010_create_fin_categories_table.php`
- Create: `database/migrations/chen/2026_06_16_000011_create_fin_recurring_rules_table.php`
- Create: `database/migrations/chen/2026_06_16_000012_create_fin_transactions_table.php`
- Create: `database/migrations/chen/2026_06_16_000013_create_fin_settings_table.php`
- Create: `app/Chen/Modules/Finance/Models/{Category,Transaction,RecurringRule,FinanceSetting}.php`
- Create: `database/factories/Chen/Finance/{CategoryFactory,TransactionFactory,RecurringRuleFactory}.php`
- Test: `tests/Feature/Chen/Finance/ModelsTest.php`

- [ ] **Step 1: Create the categories migration**

`database/migrations/chen/2026_06_16_000010_create_fin_categories_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('fin_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->enum('type', ['expense', 'income']);
            $table->string('name');
            $table->string('color', 9)->default('#64748b');
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['chen_user_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_categories');
    }
}
```

- [ ] **Step 2: Create the recurring rules migration**

`database/migrations/chen/2026_06_16_000011_create_fin_recurring_rules_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinRecurringRulesTable extends Migration
{
    public function up()
    {
        Schema::create('fin_recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->foreignId('fin_category_id')->constrained('fin_categories');
            $table->enum('type', ['expense', 'income']);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->enum('frequency', ['weekly', 'monthly', 'yearly']);
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable(); // 0=Sun..6=Sat
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['chen_user_id', 'active', 'next_run_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_recurring_rules');
    }
}
```

- [ ] **Step 3: Create the transactions migration**

`database/migrations/chen/2026_06_16_000012_create_fin_transactions_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('fin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->enum('type', ['expense', 'income']);
            $table->foreignId('fin_category_id')->constrained('fin_categories');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('recurring_rule_id')->nullable()->constrained('fin_recurring_rules')->nullOnDelete();
            $table->timestamps();
            $table->index(['chen_user_id', 'date']);
            $table->index(['chen_user_id', 'type', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_transactions');
    }
}
```

- [ ] **Step 4: Create the settings migration**

`database/migrations/chen/2026_06_16_000013_create_fin_settings_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('fin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->unique()->constrained('chen_users')->cascadeOnDelete();
            $table->string('currency', 8)->default('IDR');
            $table->decimal('monthly_spending_target', 15, 2)->nullable();
            $table->decimal('monthly_savings_target', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_settings');
    }
}
```

- [ ] **Step 5: Create the models**

`app/Chen/Modules/Finance/Models/Category.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fin_categories';
    protected $guarded = ['id'];

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'fin_category_id');
    }
}
```

`app/Chen/Modules/Finance/Models/Transaction.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'fin_transactions';
    protected $guarded = ['id'];
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fin_category_id');
    }
}
```

`app/Chen/Modules/Finance/Models/RecurringRule.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\RecurringRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringRule extends Model
{
    use HasFactory;

    protected $table = 'fin_recurring_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return RecurringRuleFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fin_category_id');
    }
}
```

`app/Chen/Modules/Finance/Models/FinanceSetting.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    protected $table = 'fin_settings';
    protected $guarded = ['id'];
    protected $casts = [
        'monthly_spending_target' => 'decimal:2',
        'monthly_savings_target' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }
}
```

- [ ] **Step 6: Create the factories**

`database/factories/Chen/Finance/CategoryFactory.php`:
```php
<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'type' => 'expense',
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
            'sort_order' => 0,
        ];
    }

    public function income()
    {
        return $this->state(['type' => 'income']);
    }
}
```

`database/factories/Chen/Finance/TransactionFactory.php`:
```php
<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'type' => 'expense',
            'fin_category_id' => Category::factory(),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function income()
    {
        return $this->state(['type' => 'income']);
    }
}
```

`database/factories/Chen/Finance/RecurringRuleFactory.php`:
```php
<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringRuleFactory extends Factory
{
    protected $model = RecurringRule::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'fin_category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => $this->faker->numberBetween(50000, 1000000),
            'notes' => null,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'weekday' => null,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'next_run_date' => '2026-01-01',
            'active' => true,
        ];
    }
}
```

- [ ] **Step 7: Write the failing test**

`tests/Feature/Chen/Finance/ModelsTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class ModelsTest extends ChenTestCase
{
    public function test_can_persist_a_transaction_with_category(): void
    {
        $category = Category::factory()->create(['name' => 'Makan']);
        $txn = Transaction::factory()->create([
            'chen_user_id' => $category->chen_user_id,
            'fin_category_id' => $category->id,
            'amount' => 25000,
        ]);

        $this->assertDatabaseHas('fin_transactions', ['id' => $txn->id, 'amount' => 25000.00]);
        $this->assertSame('Makan', $txn->category->name);
    }

    public function test_category_soft_deletes(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->assertSoftDeleted('fin_categories', ['id' => $category->id]);
    }

    public function test_recurring_rule_casts_dates_and_bool(): void
    {
        $rule = RecurringRule::factory()->create();

        $this->assertTrue($rule->active);
        $this->assertSame('2026-01-01', $rule->next_run_date->format('Y-m-d'));
    }
}
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter ModelsTest`
Expected: PASS (4 assertions).

- [ ] **Step 9: Commit**

```bash
git add database/migrations/chen app/Chen/Modules/Finance/Models database/factories/Chen/Finance tests/Feature/Chen/Finance/ModelsTest.php
git commit -m "feat(finance): migrations, models, factories for categories/transactions/recurring/settings"
```

---

### Task 7: Categories CRUD

**Files:**
- Create: `app/Chen/Modules/Finance/Controllers/CategoryController.php`
- Create: `app/Chen/Modules/Finance/Views/categories/index.blade.php`
- Modify: `app/Chen/Modules/Finance/routes.php` (add category routes)
- Test: `tests/Feature/Chen/Finance/CategoryControllerTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/CategoryControllerTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class CategoryControllerTest extends ChenTestCase
{
    public function test_user_sees_only_their_categories(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->create(['chen_user_id' => $me->id, 'name' => 'Mine']);
        Category::factory()->create(['chen_user_id' => $other->id, 'name' => 'Theirs']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/categories'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_can_create_category(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/categories'), [
                'type' => 'expense', 'name' => 'Transport', 'color' => '#ff0000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_categories', [
            'chen_user_id' => $me->id, 'name' => 'Transport', 'type' => 'expense',
        ]);
    }

    public function test_can_update_own_category(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id, 'name' => 'Old']);

        $this->actingAs($me, 'chen')
            ->put($this->chenUrl('/finance/categories/' . $cat->id), [
                'type' => 'expense', 'name' => 'New', 'color' => '#00ff00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_categories', ['id' => $cat->id, 'name' => 'New']);
    }

    public function test_cannot_update_another_users_category(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => User::factory()->create()->id]);

        $this->actingAs($me, 'chen')
            ->put($this->chenUrl('/finance/categories/' . $cat->id), [
                'type' => 'expense', 'name' => 'Hack', 'color' => '#000000',
            ])
            ->assertNotFound();
    }

    public function test_deleting_category_in_use_soft_deletes(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/categories/' . $cat->id))
            ->assertRedirect();

        $this->assertSoftDeleted('fin_categories', ['id' => $cat->id]);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter CategoryControllerTest`
Expected: FAIL (routes 404).

- [ ] **Step 3: Add routes**

Append to `app/Chen/Modules/Finance/routes.php` (before the closing of the file, after the dashboard route):
```php
use App\Chen\Modules\Finance\Controllers\CategoryController;

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
```
> Put the `use` statements at the top of `routes.php` with the existing `use` lines.

- [ ] **Step 4: Implement the controller**

`app/Chen/Modules/Finance/Controllers/CategoryController.php`:
```php
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
```
> Route-model binding is bypassed (we accept the raw id and scope by user) so cross-user access returns 404 via `findOrFail`.

- [ ] **Step 5: Create the view**

`app/Chen/Modules/Finance/Views/categories/index.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Kategori — Finance')
@section('heading', 'Kategori')
@section('content')
<div x-data="{ open: false, edit: null }" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Kategori</h1>
        <button @click="open = true; edit = null"
                class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['expense' => 'Pengeluaran', 'income' => 'Pemasukan'] as $type => $label)
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <h2 class="text-sm font-semibold text-slate-500 mb-2">{{ $label }}</h2>
                <ul class="space-y-1">
                    @forelse ($categories->where('type', $type) as $cat)
                        <li class="flex items-center gap-2 py-1.5">
                            <span class="w-3 h-3 rounded-full" style="background: {{ $cat->color }}"></span>
                            <span class="text-sm">{{ $cat->name }}</span>
                            <span class="ml-auto flex gap-2">
                                <button class="text-xs text-slate-500 hover:text-slate-900"
                                        @click='edit = @json($cat); open = true'>Edit</button>
                                <form method="POST" action="{{ route('chen.finance.categories.destroy', $cat->id) }}"
                                      onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-500 hover:text-rose-700">Hapus</button>
                                </form>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400 py-1.5">Belum ada.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    {{-- Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3" x-text="edit ? 'Edit Kategori' : 'Tambah Kategori'"></h3>
            <form method="POST"
                  :action="edit ? '{{ url('finance/categories') }}/' + edit.id : '{{ route('chen.finance.categories.store') }}'">
                @csrf
                <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-3">
                    <select name="type" x-bind:value="edit ? edit.type : 'expense'" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <input name="name" :value="edit ? edit.name : ''" placeholder="Nama kategori" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <input type="color" name="color" :value="edit ? edit.color : '#64748b'"
                           class="w-16 h-9 rounded border-slate-300">
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="text-sm px-3 py-2">Batal</button>
                    <button class="bg-slate-900 text-white text-sm rounded-lg px-4 py-2">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter CategoryControllerTest`
Expected: PASS (all assertions).

- [ ] **Step 7: Commit**

```bash
git add app/Chen/Modules/Finance/Controllers/CategoryController.php app/Chen/Modules/Finance/Views/categories app/Chen/Modules/Finance/routes.php tests/Feature/Chen/Finance/CategoryControllerTest.php
git commit -m "feat(finance): categories CRUD scoped to user with soft-delete"
```

---

### Task 8: Transactions CRUD (expense + income) with filters

**Files:**
- Create: `app/Chen/Modules/Finance/Controllers/TransactionController.php`
- Create: `app/Chen/Modules/Finance/Views/transactions/index.blade.php`
- Modify: `app/Chen/Modules/Finance/routes.php`
- Test: `tests/Feature/Chen/Finance/TransactionControllerTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/TransactionControllerTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class TransactionControllerTest extends ChenTestCase
{
    public function test_index_lists_only_own_transactions(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $mine->id, 'notes' => 'KopiSaya']);
        $their = Category::factory()->create(['chen_user_id' => $other->id]);
        Transaction::factory()->create(['chen_user_id' => $other->id, 'fin_category_id' => $their->id, 'notes' => 'KopiOrang']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/transactions'))
            ->assertOk()
            ->assertSee('KopiSaya')
            ->assertDontSee('KopiOrang');
    }

    public function test_can_store_expense(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id, 'type' => 'expense']);

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/transactions'), [
                'type' => 'expense', 'fin_category_id' => $cat->id,
                'date' => '2026-06-10', 'amount' => 42000, 'notes' => 'Bensin',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_transactions', [
            'chen_user_id' => $me->id, 'amount' => 42000.00, 'notes' => 'Bensin', 'type' => 'expense',
        ]);
    }

    public function test_store_rejects_category_of_other_user(): void
    {
        $me = User::factory()->create();
        $foreignCat = Category::factory()->create(['chen_user_id' => User::factory()->create()->id]);

        $this->actingAs($me, 'chen')
            ->from($this->chenUrl('/finance/transactions'))
            ->post($this->chenUrl('/finance/transactions'), [
                'type' => 'expense', 'fin_category_id' => $foreignCat->id,
                'date' => '2026-06-10', 'amount' => 1000,
            ])
            ->assertSessionHasErrors('fin_category_id');
    }

    public function test_can_delete_own_transaction(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        $txn = Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/transactions/' . $txn->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('fin_transactions', ['id' => $txn->id]);
    }

    public function test_month_filter_limits_results(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'date' => '2026-05-15', 'notes' => 'Mei']);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'date' => '2026-06-15', 'notes' => 'Juni']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/transactions?month=2026-06'))
            ->assertOk()
            ->assertSee('Juni')
            ->assertDontSee('Mei');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter TransactionControllerTest`
Expected: FAIL (routes 404).

- [ ] **Step 3: Add routes**

Add to top `use` block + body of `app/Chen/Modules/Finance/routes.php`:
```php
use App\Chen\Modules\Finance\Controllers\TransactionController;

Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
```

- [ ] **Step 4: Implement the controller**

`app/Chen/Modules/Finance/Controllers/TransactionController.php`:
```php
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

        $transactions = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();
        $total = (clone $query)->sum('amount');
        $categories = Category::where('chen_user_id', $this->uid())->orderBy('name')->get();

        return view('finance::transactions.index', compact('transactions', 'total', 'categories', 'month', 'type'));
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
```

- [ ] **Step 5: Create the view**

`app/Chen/Modules/Finance/Views/transactions/index.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Transaksi — Finance')
@section('heading', 'Transaksi')
@section('content')
<div x-data="{ open: false, edit: null }" class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <h1 class="text-lg font-semibold">Transaksi</h1>
        <button @click="open = true; edit = null"
                class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 text-sm">
        <input type="month" name="month" value="{{ $month }}" class="rounded-lg border-slate-300">
        <select name="type" class="rounded-lg border-slate-300">
            <option value="">Semua</option>
            <option value="expense" @selected($type==='expense')>Pengeluaran</option>
            <option value="income" @selected($type==='income')>Pemasukan</option>
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari catatan…" class="rounded-lg border-slate-300">
        <button class="bg-slate-200 rounded-lg px-3 hover:bg-slate-300">Filter</button>
    </form>

    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Kategori</th>
                    <th class="px-4 py-2">Catatan</th><th class="px-4 py-2 text-right">Jumlah</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transactions as $t)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $t->date->format('d M Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $t->category->color ?? '#999' }}"></span>
                                {{ $t->category->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $t->notes }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-800' }}">
                            {{ $t->type === 'income' ? '+' : '−' }} {{ number_format($t->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button class="text-xs text-slate-500 hover:text-slate-900" @click='edit = @json($t); open = true'>Edit</button>
                            <form method="POST" action="{{ route('chen.finance.transactions.destroy', $t->id) }}" class="inline"
                                  onsubmit="return confirm('Hapus transaksi ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-500 hover:text-rose-700 ml-1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr><td colspan="3" class="px-4 py-2 text-right">Total</td>
                    <td class="px-4 py-2 text-right">{{ number_format($total, 0, ',', '.') }}</td><td></td></tr>
            </tfoot>
        </table>
    </div>
    <div>{{ $transactions->links() }}</div>

    {{-- Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3" x-text="edit ? 'Edit Transaksi' : 'Tambah Transaksi'"></h3>
            <form method="POST"
                  :action="edit ? '{{ url('finance/transactions') }}/' + edit.id : '{{ route('chen.finance.transactions.store') }}'">
                @csrf
                <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-3">
                    <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <select name="fin_category_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ ucfirst($c->type) }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date" :value="edit ? edit.date.substring(0,10) : '{{ date('Y-m-d') }}'" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <input type="number" step="1" min="0" name="amount" :value="edit ? edit.amount : ''" placeholder="Jumlah" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <textarea name="notes" placeholder="Catatan (opsional)" x-text="edit ? edit.notes : ''"
                              class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="text-sm px-3 py-2">Batal</button>
                    <button class="bg-slate-900 text-white text-sm rounded-lg px-4 py-2">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter TransactionControllerTest`
Expected: PASS (all assertions).

- [ ] **Step 7: Commit**

```bash
git add app/Chen/Modules/Finance/Controllers/TransactionController.php app/Chen/Modules/Finance/Views/transactions app/Chen/Modules/Finance/routes.php tests/Feature/Chen/Finance/TransactionControllerTest.php
git commit -m "feat(finance): transactions CRUD with month/type/category/search filters"
```

---

### Task 9: Recurring rules — generator service + command

**Files:**
- Create: `app/Chen/Modules/Finance/Services/RecurringGenerator.php`
- Create: `app/Chen/Modules/Finance/Console/RunRecurring.php`
- Test: `tests/Feature/Chen/Finance/RecurringGeneratorTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/RecurringGeneratorTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\RecurringGenerator;
use Carbon\Carbon;
use Tests\Chen\ChenTestCase;

class RecurringGeneratorTest extends ChenTestCase
{
    private function rule(array $overrides = []): RecurringRule
    {
        $user = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $user->id]);

        return RecurringRule::factory()->create(array_merge([
            'chen_user_id' => $user->id,
            'fin_category_id' => $cat->id,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'start_date' => '2026-01-01',
            'next_run_date' => '2026-01-01',
            'amount' => 100000,
        ], $overrides));
    }

    public function test_generates_due_transactions_up_to_today(): void
    {
        $rule = $this->rule();
        // From 2026-01-01 monthly through 2026-06-16 => Jan,Feb,Mar,Apr,May,Jun = 6 rows.
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        $this->assertSame(6, Transaction::where('recurring_rule_id', $rule->id)->count());
        $this->assertSame('2026-07-01', $rule->fresh()->next_run_date->format('Y-m-d'));
    }

    public function test_is_idempotent(): void
    {
        $rule = $this->rule();
        $gen = app(RecurringGenerator::class);
        $gen->run(Carbon::parse('2026-06-16'));
        $gen->run(Carbon::parse('2026-06-16')); // second run must add nothing

        $this->assertSame(6, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_respects_end_date(): void
    {
        $rule = $this->rule(['end_date' => '2026-03-31']);
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        // Jan, Feb, Mar only = 3 rows.
        $this->assertSame(3, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_skips_inactive_rules(): void
    {
        $rule = $this->rule(['active' => false]);
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        $this->assertSame(0, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_command_runs_generator(): void
    {
        $rule = $this->rule(['next_run_date' => '2026-06-01', 'start_date' => '2026-06-01']);

        $this->artisan('chen:finance:run-recurring')->assertExitCode(0);

        $this->assertGreaterThanOrEqual(1, Transaction::where('recurring_rule_id', $rule->id)->count());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter RecurringGeneratorTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement the generator**

`app/Chen/Modules/Finance/Services/RecurringGenerator.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Services;

use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RecurringGenerator
{
    /**
     * Materialize all due transactions for active rules up to and including $asOf.
     * Idempotent: each run only creates rows for periods not yet generated,
     * tracked by the rule's next_run_date cursor.
     *
     * @return int number of transactions created
     */
    public function run(?CarbonInterface $asOf = null): int
    {
        $asOf = $asOf ? $asOf->copy()->startOfDay() : Carbon::now()->startOfDay();
        $created = 0;

        RecurringRule::where('active', true)
            ->where('next_run_date', '<=', $asOf->toDateString())
            ->each(function (RecurringRule $rule) use ($asOf, &$created) {
                $created += $this->runRule($rule, $asOf);
            });

        return $created;
    }

    private function runRule(RecurringRule $rule, CarbonInterface $asOf): int
    {
        $cursor = $rule->next_run_date->copy();
        $end = $rule->end_date ? $rule->end_date->copy() : null;
        $count = 0;

        while ($cursor->lessThanOrEqualTo($asOf)) {
            if ($end && $cursor->greaterThan($end)) {
                break;
            }

            Transaction::create([
                'chen_user_id' => $rule->chen_user_id,
                'type' => $rule->type,
                'fin_category_id' => $rule->fin_category_id,
                'date' => $cursor->toDateString(),
                'amount' => $rule->amount,
                'notes' => $rule->notes,
                'recurring_rule_id' => $rule->id,
            ]);
            $count++;

            $cursor = $this->advance($cursor, $rule->frequency);
        }

        $rule->next_run_date = $cursor->toDateString();
        $rule->save();

        return $count;
    }

    private function advance(CarbonInterface $date, string $frequency): CarbonInterface
    {
        switch ($frequency) {
            case 'weekly':
                return $date->copy()->addWeek();
            case 'yearly':
                return $date->copy()->addYear();
            case 'monthly':
            default:
                return $date->copy()->addMonthNoOverflow();
        }
    }
}
```
> `addMonthNoOverflow()` keeps Jan-31 → Feb-28 instead of skipping to March, which matters for month-end rules.

- [ ] **Step 4: Implement the command**

`app/Chen/Modules/Finance/Console/RunRecurring.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Console;

use App\Chen\Modules\Finance\Services\RecurringGenerator;
use Illuminate\Console\Command;

class RunRecurring extends Command
{
    protected $signature = 'chen:finance:run-recurring';
    protected $description = 'Materialize due recurring finance transactions';

    public function handle(RecurringGenerator $generator): int
    {
        $created = $generator->run();
        $this->info("Created {$created} recurring transaction(s).");

        return 0;
    }
}
```

- [ ] **Step 5: Verify command registration**

Confirm `App\Chen\Modules\Finance\Console\RunRecurring::class` is in the provider's `commands([...])` array (Task 1). Uncomment if needed.

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter RecurringGeneratorTest`
Expected: PASS (all assertions). If the "up to today" count differs, confirm the test's fixed `$asOf` dates are used (the generator must accept the injected date, not call `now()`).

- [ ] **Step 7: Commit**

```bash
git add app/Chen/Modules/Finance/Services app/Chen/Modules/Finance/Console tests/Feature/Chen/Finance/RecurringGeneratorTest.php
git commit -m "feat(finance): idempotent recurring transaction generator + scheduled command"
```

---

### Task 10: Recurring rules CRUD UI

**Files:**
- Create: `app/Chen/Modules/Finance/Controllers/RecurringController.php`
- Create: `app/Chen/Modules/Finance/Views/recurring/index.blade.php`
- Modify: `app/Chen/Modules/Finance/routes.php`
- Test: `tests/Feature/Chen/Finance/RecurringControllerTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/RecurringControllerTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use Tests\Chen\ChenTestCase;

class RecurringControllerTest extends ChenTestCase
{
    public function test_can_create_rule_with_next_run_seeded_from_start(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/recurring'), [
                'type' => 'expense', 'fin_category_id' => $cat->id, 'amount' => 500000,
                'frequency' => 'monthly', 'day_of_month' => 1, 'start_date' => '2026-07-01',
            ])
            ->assertRedirect();

        $rule = RecurringRule::where('chen_user_id', $me->id)->first();
        $this->assertNotNull($rule);
        $this->assertSame('2026-07-01', $rule->next_run_date->format('Y-m-d'));
    }

    public function test_can_toggle_active(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        $rule = RecurringRule::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'active' => true]);

        $this->actingAs($me, 'chen')
            ->patch($this->chenUrl('/finance/recurring/' . $rule->id . '/toggle'))
            ->assertRedirect();

        $this->assertFalse($rule->fresh()->active);
    }

    public function test_cannot_delete_another_users_rule(): void
    {
        $me = User::factory()->create();
        $rule = RecurringRule::factory()->create([
            'chen_user_id' => User::factory()->create()->id,
            'fin_category_id' => Category::factory()->create()->id,
        ]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/recurring/' . $rule->id))
            ->assertNotFound();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter RecurringControllerTest`
Expected: FAIL (routes 404).

- [ ] **Step 3: Add routes**

Add to `app/Chen/Modules/Finance/routes.php`:
```php
use App\Chen\Modules\Finance\Controllers\RecurringController;

Route::get('/recurring', [RecurringController::class, 'index'])->name('recurring.index');
Route::post('/recurring', [RecurringController::class, 'store'])->name('recurring.store');
Route::put('/recurring/{rule}', [RecurringController::class, 'update'])->name('recurring.update');
Route::patch('/recurring/{rule}/toggle', [RecurringController::class, 'toggle'])->name('recurring.toggle');
Route::delete('/recurring/{rule}', [RecurringController::class, 'destroy'])->name('recurring.destroy');
```

- [ ] **Step 4: Implement the controller**

`app/Chen/Modules/Finance/Controllers/RecurringController.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\Category;
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

        return view('finance::recurring.index', compact('rules', 'categories'));
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
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'weekday' => ['nullable', 'integer', 'between:0,6'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
```

- [ ] **Step 5: Create the view**

`app/Chen/Modules/Finance/Views/recurring/index.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Berulang — Finance')
@section('heading', 'Transaksi Berulang')
@section('content')
<div x-data="{ open: false }" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Transaksi Berulang</h1>
        <button @click="open = true" class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-4 py-2">Kategori</th><th class="px-4 py-2">Frekuensi</th>
                    <th class="px-4 py-2">Jalan Berikutnya</th><th class="px-4 py-2 text-right">Jumlah</th>
                    <th class="px-4 py-2">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rules as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->category->name ?? '—' }} <span class="text-xs text-slate-400">({{ $r->type }})</span></td>
                        <td class="px-4 py-2">{{ ucfirst($r->frequency) }}</td>
                        <td class="px-4 py-2">{{ $r->next_run_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('chen.finance.recurring.toggle', $r->id) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs rounded-full px-2 py-0.5 {{ $r->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $r->active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('chen.finance.recurring.destroy', $r->id) }}"
                                  onsubmit="return confirm('Hapus aturan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-500 hover:text-rose-700">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada aturan berulang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3">Tambah Aturan Berulang</h3>
            <form method="POST" action="{{ route('chen.finance.recurring.store') }}">
                @csrf
                <div class="space-y-3">
                    <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <select name="fin_category_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ ucfirst($c->type) }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="amount" min="0" placeholder="Jumlah" required class="w-full rounded-lg border-slate-300 text-sm">
                    <select name="frequency" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="monthly">Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="yearly">Tahunan</option>
                    </select>
                    <label class="block text-sm text-slate-600">Mulai
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <label class="block text-sm text-slate-600">Berakhir (opsional)
                        <input type="date" name="end_date" class="w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <textarea name="notes" placeholder="Catatan (opsional)" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="text-sm px-3 py-2">Batal</button>
                    <button class="bg-slate-900 text-white text-sm rounded-lg px-4 py-2">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter RecurringControllerTest`
Expected: PASS (all assertions).

- [ ] **Step 7: Commit**

```bash
git add app/Chen/Modules/Finance/Controllers/RecurringController.php app/Chen/Modules/Finance/Views/recurring app/Chen/Modules/Finance/routes.php tests/Feature/Chen/Finance/RecurringControllerTest.php
git commit -m "feat(finance): recurring rules CRUD UI with active toggle"
```

---

### Task 11: Finance settings

**Files:**
- Create: `app/Chen/Modules/Finance/Controllers/SettingController.php`
- Create: `app/Chen/Modules/Finance/Views/settings/index.blade.php`
- Modify: `app/Chen/Modules/Finance/routes.php`
- Test: `tests/Feature/Chen/Finance/SettingControllerTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/SettingControllerTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use Tests\Chen\ChenTestCase;

class SettingControllerTest extends ChenTestCase
{
    public function test_settings_page_renders(): void
    {
        $this->actingAs(User::factory()->create(), 'chen')
            ->get($this->chenUrl('/finance/settings'))
            ->assertOk()
            ->assertSee('Pengaturan');
    }

    public function test_can_save_settings_upserting_one_row(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/settings'), [
                'currency' => 'IDR', 'monthly_spending_target' => 5000000, 'monthly_savings_target' => 2000000,
            ])->assertRedirect();

        // Save again — must update, not create a second row (unique chen_user_id).
        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/settings'), [
                'currency' => 'IDR', 'monthly_spending_target' => 6000000, 'monthly_savings_target' => 2500000,
            ])->assertRedirect();

        $this->assertDatabaseCount('fin_settings', 1);
        $this->assertDatabaseHas('fin_settings', [
            'chen_user_id' => $me->id, 'monthly_spending_target' => 6000000.00,
        ]);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter SettingControllerTest`
Expected: FAIL (routes 404).

- [ ] **Step 3: Add routes**

Add to `app/Chen/Modules/Finance/routes.php`:
```php
use App\Chen\Modules\Finance\Controllers\SettingController;

Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
```

- [ ] **Step 4: Implement the controller**

`app/Chen/Modules/Finance/Controllers/SettingController.php`:
```php
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
```

- [ ] **Step 5: Create the view**

`app/Chen/Modules/Finance/Views/settings/index.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Pengaturan — Finance')
@section('heading', 'Pengaturan Finance')
@section('content')
<div class="max-w-md">
    <h1 class="text-lg font-semibold mb-4">Pengaturan</h1>
    <form method="POST" action="{{ route('chen.finance.settings.update') }}"
          class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4">
        @csrf
        <label class="block text-sm text-slate-600">Mata uang
            <input name="currency" value="{{ old('currency', $setting->currency ?? 'IDR') }}" required
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-sm text-slate-600">Target pengeluaran / bulan
            <input type="number" min="0" name="monthly_spending_target"
                   value="{{ old('monthly_spending_target', $setting->monthly_spending_target) }}"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-sm text-slate-600">Target tabungan / bulan
            <input type="number" min="0" name="monthly_savings_target"
                   value="{{ old('monthly_savings_target', $setting->monthly_savings_target) }}"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <button class="bg-slate-900 text-white text-sm rounded-lg px-4 py-2">Simpan</button>
    </form>
</div>
@endsection
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter SettingControllerTest`
Expected: PASS (all assertions).

- [ ] **Step 7: Commit**

```bash
git add app/Chen/Modules/Finance/Controllers/SettingController.php app/Chen/Modules/Finance/Views/settings app/Chen/Modules/Finance/routes.php tests/Feature/Chen/Finance/SettingControllerTest.php
git commit -m "feat(finance): per-user finance settings (currency + monthly targets)"
```

---

### Task 12: Dashboard analytics (cards, savings trend, category breakdown) + recurring catch-up

**Files:**
- Create: `app/Chen/Modules/Finance/Services/Analytics.php`
- Modify: `app/Chen/Modules/Finance/Controllers/DashboardController.php`
- Replace: `app/Chen/Modules/Finance/Views/dashboard.blade.php`
- Test: `tests/Feature/Chen/Finance/AnalyticsTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Chen/Finance/AnalyticsTest.php`:
```php
<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\Analytics;
use Carbon\Carbon;
use Tests\Chen\ChenTestCase;

class AnalyticsTest extends ChenTestCase
{
    private function seed(User $user): void
    {
        $exp = Category::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'name' => 'Makan']);
        $inc = Category::factory()->income()->create(['chen_user_id' => $user->id, 'name' => 'Gaji']);

        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'income', 'fin_category_id' => $inc->id, 'date' => '2026-06-01', 'amount' => 10000000]);
        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'fin_category_id' => $exp->id, 'date' => '2026-06-05', 'amount' => 3000000]);
        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'fin_category_id' => $exp->id, 'date' => '2026-06-10', 'amount' => 1000000]);
    }

    public function test_month_totals_and_saving(): void
    {
        $user = User::factory()->create();
        $this->seed($user);

        $summary = app(Analytics::class)->monthSummary($user->id, Carbon::parse('2026-06-15'));

        $this->assertEquals(10000000, $summary['income']);
        $this->assertEquals(4000000, $summary['expense']);
        $this->assertEquals(6000000, $summary['saving']); // income - expense
    }

    public function test_expense_by_category_groups_and_sums(): void
    {
        $user = User::factory()->create();
        $this->seed($user);

        $breakdown = app(Analytics::class)->expenseByCategory($user->id, Carbon::parse('2026-06-15'));

        $this->assertCount(1, $breakdown);
        $this->assertSame('Makan', $breakdown[0]['name']);
        $this->assertEquals(4000000, $breakdown[0]['total']);
    }

    public function test_savings_trend_returns_six_months(): void
    {
        $user = User::factory()->create();
        $this->seed($user);

        $trend = app(Analytics::class)->savingsTrend($user->id, Carbon::parse('2026-06-15'));

        $this->assertCount(6, $trend);
        $this->assertSame('2026-06', $trend[5]['month']);
        $this->assertEquals(6000000, $trend[5]['saving']);
        $this->assertEquals(0, $trend[0]['saving']); // Jan 2026 had nothing
    }

    public function test_dashboard_renders_and_triggers_recurring_catchup(): void
    {
        $user = User::factory()->create();
        $this->seed($user);

        $this->actingAs($user, 'chen')
            ->get($this->chenUrl('/finance'))
            ->assertOk()
            ->assertSee('Tabungan'); // savings card label
    }

    public function test_analytics_scoped_per_user(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->seed($other);

        $summary = app(Analytics::class)->monthSummary($me->id, Carbon::parse('2026-06-15'));

        $this->assertEquals(0, $summary['income']);
        $this->assertEquals(0, $summary['expense']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit --filter AnalyticsTest`
Expected: FAIL (Analytics class not found).

- [ ] **Step 3: Implement the analytics service**

`app/Chen/Modules/Finance/Services/Analytics.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Services;

use App\Chen\Modules\Finance\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class Analytics
{
    /** @return array{income: float, expense: float, saving: float} */
    public function monthSummary(int $userId, CarbonInterface $month): array
    {
        $base = Transaction::where('chen_user_id', $userId)
            ->whereYear('date', $month->year)->whereMonth('date', $month->month);

        $income = (float) (clone $base)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $base)->where('type', 'expense')->sum('amount');

        return ['income' => $income, 'expense' => $expense, 'saving' => $income - $expense];
    }

    /** @return array<int, array{name: string, color: string, total: float}> */
    public function expenseByCategory(int $userId, CarbonInterface $month, string $type = 'expense'): array
    {
        return Transaction::query()
            ->join('fin_categories', 'fin_categories.id', '=', 'fin_transactions.fin_category_id')
            ->where('fin_transactions.chen_user_id', $userId)
            ->where('fin_transactions.type', $type)
            ->whereYear('fin_transactions.date', $month->year)
            ->whereMonth('fin_transactions.date', $month->month)
            ->groupBy('fin_categories.id', 'fin_categories.name', 'fin_categories.color')
            ->orderByDesc('total')
            ->get([
                'fin_categories.name',
                'fin_categories.color',
                \DB::raw('SUM(fin_transactions.amount) as total'),
            ])
            ->map(fn ($r) => ['name' => $r->name, 'color' => $r->color, 'total' => (float) $r->total])
            ->all();
    }

    /** @return array<int, array{month: string, income: float, expense: float, saving: float}> */
    public function savingsTrend(int $userId, CarbonInterface $asOf, int $months = 6): array
    {
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = $asOf->copy()->startOfMonth()->subMonths($i);
            $summary = $this->monthSummary($userId, $m);
            $out[] = [
                'month' => $m->format('Y-m'),
                'income' => $summary['income'],
                'expense' => $summary['expense'],
                'saving' => $summary['saving'],
            ];
        }

        return $out;
    }

    /** @return array{per_day: float, per_txn: float} */
    public function expenseAverages(int $userId, CarbonInterface $month): array
    {
        $base = Transaction::where('chen_user_id', $userId)->where('type', 'expense')
            ->whereYear('date', $month->year)->whereMonth('date', $month->month);

        $total = (float) (clone $base)->sum('amount');
        $count = (int) (clone $base)->count('id');
        $daysInMonth = (int) $month->copy()->daysInMonth;

        return [
            'per_day' => $daysInMonth ? round($total / $daysInMonth, 2) : 0.0,
            'per_txn' => $count ? round($total / $count, 2) : 0.0,
        ];
    }
}
```
> Add `use Illuminate\Support\Facades\DB;` if your style forbids the leading-backslash `\DB::raw`. Either form works.

- [ ] **Step 4: Update the dashboard controller**

Replace `app/Chen/Modules/Finance/Controllers/DashboardController.php`:
```php
<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\FinanceSetting;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\Analytics;
use App\Chen\Modules\Finance\Services\RecurringGenerator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Analytics $analytics, RecurringGenerator $generator)
    {
        $generator->run(); // idempotent catch-up so dashboards reflect due recurring rows

        $uid = Auth::guard('chen')->id();
        $now = Carbon::now();

        $summary = $analytics->monthSummary($uid, $now);
        $byCategory = $analytics->expenseByCategory($uid, $now);
        $trend = $analytics->savingsTrend($uid, $now);
        $averages = $analytics->expenseAverages($uid, $now);
        $setting = FinanceSetting::firstOrNew(['chen_user_id' => $uid]);
        $recent = Transaction::with('category')->where('chen_user_id', $uid)
            ->orderByDesc('date')->orderByDesc('id')->limit(5)->get();

        return view('finance::dashboard', compact(
            'summary', 'byCategory', 'trend', 'averages', 'setting', 'recent'
        ));
    }
}
```

- [ ] **Step 5: Replace the dashboard view**

Replace `app/Chen/Modules/Finance/Views/dashboard.blade.php`:
```blade
@extends('chen::layout')
@section('title', 'Finance — Chen')
@section('heading', 'Finance')
@push('head')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush
@section('content')
@php
    $cur = $setting->currency ?? 'IDR';
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp
<div class="space-y-5">
    {{-- Cards --}}
    <div class="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Pemasukan (bln ini)</p>
            <p class="text-lg font-semibold text-emerald-600">{{ $cur }} {{ $fmt($summary['income']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Pengeluaran (bln ini)</p>
            <p class="text-lg font-semibold text-rose-600">{{ $cur }} {{ $fmt($summary['expense']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Tabungan (bln ini)</p>
            <p class="text-lg font-semibold {{ $summary['saving'] >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                {{ $cur }} {{ $fmt($summary['saving']) }}
            </p>
            @if (!is_null($setting->monthly_savings_target) && $setting->monthly_savings_target > 0)
                @php($pct = min(100, round($summary['saving'] / $setting->monthly_savings_target * 100)))
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ max(0, $pct) }}%"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $pct }}% dari target {{ $fmt($setting->monthly_savings_target) }}</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Rata-rata / hari</p>
            <p class="text-lg font-semibold">{{ $cur }} {{ $fmt($averages['per_day']) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Per transaksi: {{ $fmt($averages['per_txn']) }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid gap-3 lg:grid-cols-2">
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-500 mb-2">Tabungan 6 bulan</h2>
            <div id="savingsChart"></div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-500 mb-2">Pengeluaran per kategori (bln ini)</h2>
            <div id="categoryChart"></div>
            @if (empty($byCategory))
                <p class="text-sm text-slate-400 text-center py-6">Belum ada pengeluaran bulan ini.</p>
            @endif
        </div>
    </div>

    {{-- Recent --}}
    <div class="rounded-2xl bg-white border border-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-500 mb-2">Transaksi terbaru</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($recent as $t)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $t->date->format('d M') }} · {{ $t->category->name ?? '—' }}</span>
                    <span class="{{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-800' }}">
                        {{ $t->type === 'income' ? '+' : '−' }} {{ $fmt($t->amount) }}
                    </span>
                </li>
            @empty
                <li class="py-4 text-center text-slate-400">Belum ada transaksi.</li>
            @endforelse
        </ul>
    </div>
</div>

@push('scripts')
<script>
    const trend = @json($trend);
    new ApexCharts(document.querySelector('#savingsChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [
            { name: 'Pemasukan', data: trend.map(t => t.income) },
            { name: 'Pengeluaran', data: trend.map(t => t.expense) },
            { name: 'Tabungan', data: trend.map(t => t.saving) },
        ],
        colors: ['#10b981', '#f43f5e', '#0f172a'],
        xaxis: { categories: trend.map(t => t.month) },
        legend: { position: 'top' },
        dataLabels: { enabled: false },
    }).render();

    const byCat = @json($byCategory);
    if (byCat.length) {
        new ApexCharts(document.querySelector('#categoryChart'), {
            chart: { type: 'donut', height: 260 },
            series: byCat.map(c => c.total),
            labels: byCat.map(c => c.name),
            colors: byCat.map(c => c.color),
            legend: { position: 'bottom' },
        }).render();
    }
</script>
@endpush
@endsection
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php vendor/bin/phpunit --filter AnalyticsTest`
Expected: PASS (all assertions).

- [ ] **Step 7: Run the full Chen suite**

Run: `php vendor/bin/phpunit tests/Feature/Chen`
Expected: PASS (all Chen tests across Tasks 1–12).

- [ ] **Step 8: Commit**

```bash
git add app/Chen/Modules/Finance/Services/Analytics.php app/Chen/Modules/Finance/Controllers/DashboardController.php app/Chen/Modules/Finance/Views/dashboard.blade.php tests/Feature/Chen/Finance/AnalyticsTest.php
git commit -m "feat(finance): dashboard analytics (cards, savings trend, category donut) + recurring catch-up"
```

---

### Task 13: Documentation — deployment & local setup notes

**Files:**
- Create: `docs/chen/README.md`

- [ ] **Step 1: Write the doc**

`docs/chen/README.md`:
```markdown
# Chen — personal app platform

Lives inside the posni Laravel project; served on its own subdomain. First module: Finance.

## Local setup
1. Add to posni `.env`: `CHEN_DOMAIN=posni.test` (defaults to `posni.test` if unset).
2. Add a hosts entry: `127.0.0.1 chen.posni.test`.
3. Run migrations: `php artisan migrate` (Chen migrations live in `database/migrations/chen/` and are auto-loaded).
4. Create an account: `php artisan chen:user you@example.com`.
5. Visit `http://chen.posni.test/`.

## Production
- DNS: add an A/CNAME record for `chen.<domain>` to the same server.
- Web server: add a server-block/vhost for `chen.<domain>` with the same document root (`public/`) as posni.
- Set `CHEN_DOMAIN=<your-domain>` in production `.env`.
- Cron: ensure Laravel's scheduler runs (`* * * * * php artisan schedule:run`). The recurring
  generator also catches up on dashboard load, so missing cron only delays generation until next visit.

## Adding a new module
1. Create `app/Chen/Modules/<Name>/module.php` returning `['key','label','icon','order','enabled'=>true]`.
2. Add `routes.php` (auto-included under `/<key>` with route-name prefix `chen.<key>.`).
3. Put Blade views in `app/Chen/Modules/<Name>/Views` (namespace `<key>::`).
4. The sidebar nav links to `chen.<key>.dashboard` — define that route.
```

- [ ] **Step 2: Commit**

```bash
git add docs/chen/README.md
git commit -m "docs(chen): local setup, production deploy, and module-authoring notes"
```

---

## Self-review notes (addressed)

- **Spec coverage:** subdomain (Task 1/3), separate guard + seeded accounts (Tasks 2/4), fresh UI (Task 3 layout + Tailwind CDN), module system (Tasks 1/5), categories (Task 7), settings (Task 11), transactions incl. income (Task 8), recurring expense+income (Tasks 9/10), dashboard with category donut + averages + monthly savings (Task 12). All mapped.
- **Isolation:** existing posni files modified only at `config/app.php` (1 line), `config/auth.php` (additive guard/provider), `app/Http/Middleware/Authenticate.php` (additive redirect branch). No edits to `web.php`, `api.php`, the `web` guard, or posni's asset pipeline.
- **Naming consistency:** route names `chen.finance.<resource>.<action>`; nav links to `chen.finance.dashboard` (defined in Task 5). Tables `chen_*` / `fin_*`. Generator method `run(?CarbonInterface)` used identically in tests, command, and dashboard.
- **Test isolation:** all tests extend `ChenTestCase`, which switches to sqlite `:memory:` and migrates only `database/migrations/chen` — shared MySQL is never touched. Time-dependent logic (recurring, analytics) takes an injected `$asOf`/`$month` so tests are deterministic.
```
