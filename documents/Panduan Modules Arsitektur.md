# Panduan Module Architecture - Project Fix

> **Last Updated**: 25 November 2025  
> **Status**: Core Monolithic dengan Support Module (nwidart/laravel-modules)

## 📋 Daftar Isi

1. [Arsitektur Overview](#arsitektur-overview)
2. [Core vs Module Decision](#core-vs-module-decision)
3. [Layer Architecture Pattern](#layer-architecture-pattern)
4. [Implementasi Core Feature](#implementasi-core-feature)
5. [Implementasi Module Feature](#implementasi-module-feature)
6. [Best Practices](#best-practices)
7. [Common Issues](#common-issues)

---

## 🎯 Arsitektur Overview

### Current Architecture

Project ini menggunakan **Core Monolithic Architecture** dengan support untuk modular features:
- **Core**: Fitur fundamental (Auth, User, Role, Permission, Settings)
- **Module Ready**: Menggunakan `nwidart/laravel-modules` untuk future modules

### Technology Stack

```json
{
  "framework": "Laravel 12.x",
  "php": "^8.2",
  "packages": {
    "nwidart/laravel-modules": "8.6",
    "spatie/laravel-permission": "^6.0"
  },
  "frontend": "Vanilla CSS + JS (No Bootstrap/jQuery)"
}
```

### Design Principles

1. **Repository Pattern**: Abstraksi data access
2. **Service Layer**: Business logic terpisah
3. **Policy-Based Authorization**: Single source of truth
4. **Observer Pattern**: Audit logging otomatis
5. **Form Request Validation**: Input validation terpisah

---

## 🤔 Core vs Module Decision

### ✅ Implement as CORE

- Fitur fundamental sistem
- Dependency banyak fitur lain  
- Tightly coupled dengan auth/authorization
- Configuration/System level

**Contoh**: Auth, User Management, Role, Permission, Settings, Profile, Notification

### ✅ Implement as MODULE

- Domain-specific feature
- Dapat standalone/independent
- Potential untuk di-enable/disable

**Contoh**: Prasarana, Sarana, Kepegawaian, Peminjaman, Pelaporan

---

## 🏗️ Layer Architecture Pattern

### 1. Request Flow

```
HTTP Request
    ↓
Route (web.php)
    ↓
Middleware (auth, permission)
    ↓
Controller (handle request)
    ↓
Form Request (validation)
    ↓
Policy (authorization)
    ↓
Service (business logic)
    ↓
Repository (data access)
    ↓
Model (Eloquent)
    ↓
Observer (logging, events)
    ↓
Response
```

### 2. Layer Responsibilities

| Layer | Responsibility | Example |
|-------|----------------|---------|
| **Route** | URL mapping | `Route::resource('user-management', UserManagementController::class)` |
| **Middleware** | Request filtering | `auth`, `user.not.blocked`, `profile.completed` |
| **Controller** | Handle HTTP, delegate to service | `$this->userService->createUser($request->validated())` |
| **Form Request** | Input validation | `StoreUserRequest`, `UpdateUserRequest` |
| **Policy** | Authorization logic | `UserPolicy::create()`, `UserPolicy::update()` |
| **Service** | Business logic, orchestration | `UserService::createUser()` |
| **Repository** | Database queries | `UserRepository::findById()` |
| **Model** | Data representation | `User`, `Role`, `Permission` |
| **Observer** | Side effects (logging, events) | `UserObserver::created()` |

---

## 📝 Implementasi Core Feature

### Step 1: Planning & Structure

**Checklist Before Start:**
- [ ] Tentukan nama fitur dan resources
- [ ] Define permissions needed
- [ ] Design database schema
- [ ] List required validations
- [ ] Plan authorization rules

### Step 2: Database Layer

#### A. Migration

```php
// database/migrations/xxxx_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique();
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
    $table->enum('user_type', ['mahasiswa', 'staff']);
    $table->timestamps();
});
```

#### B. Seeder

```php
// database/seeders/RolePermissionSeeder.php
Permission::create(['name' => 'user.manage', 'group' => 'user']);
Permission::create(['name' => 'user.view', 'group' => 'user']);

$adminRole = Role::where('name', 'Admin Sarpras')->first();
$adminRole->givePermissionTo(['user.manage', 'user.view']);
```

### Step 3: Model Layer

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $fillable = [
        'name', 'username', 'email', 'password', 
        'status', 'user_type'
    ];

    protected $hidden = ['password', 'remember_token'];

    // Scopes
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $search) {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%");
        });
    }

    // Relations
    public function role() {
        return $this->belongsTo(Role::class);
    }
}
```

### Step 4: Repository Layer

#### A. Interface

```php
// app/Repositories/Interfaces/UserRepositoryInterface.php
namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
```

#### B. Implementation

```php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User {
        return User::with(['roles'])->find($id);
    }

    public function create(array $data): User {
        return User::create($data);
    }

    public function update(User $user, array $data): User {
        $user->fill($data)->save();
        return $user->fresh(['roles']);
    }

    public function delete(User $user): bool {
        return (bool) $user->delete();
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator {
        $query = User::query()->with(['roles']);
        
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        
        return $query->paginate($perPage);
    }
}
```

### Step 5: Service Layer

```php
// app/Services/UserService.php
namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\DatabaseManager;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DatabaseManager $database
    ) {}

    public function createUser(array $data): User
    {
        return $this->database->transaction(function () use ($data) {
            $user = $this->userRepository->create($data);
            return $user->fresh(['roles']);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return $this->database->transaction(function () use ($user, $data) {
            return $this->userRepository->update($user, $data);
        });
    }

    public function deleteUser(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw new RuntimeException('Tidak dapat menghapus akun sendiri.');
        }
        
        $this->database->transaction(function () use ($user) {
            $user->syncRoles([]);
            $this->userRepository->delete($user);
        });
    }
}
```

### Step 6: Form Request Validation

```php
// app/Http/Requests/User/StoreUserRequest.php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Delegate to policy, always return true
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'user_type' => ['required', 'in:mahasiswa,staff'],
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama harus diisi.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }
}
```

### Step 7: Policy (Authorization)

```php
// app/Policies/UserPolicy.php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    protected function canManageUsers(User $user): bool
    {
        return $user->hasPermissionTo('user.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function view(User $user, User $target): bool
    {
        // User bisa lihat profil sendiri
        if ($user->id === $target->id) {
            return true;
        }
        return $this->canManageUsers($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $target): bool
    {
        // User bisa edit profil sendiri
        if ($user->id === $target->id) {
            return true;
        }
        return $this->canManageUsers($user);
    }

    public function delete(User $user, User $target): bool
    {
        // Tidak bisa hapus diri sendiri
        if ($user->id === $target->id) {
            return false;
        }
        return $this->canManageUsers($user);
    }
}
```

### Step 8: Controller

```php
// app/Http/Controllers/UserManagementController.php
namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
        // Auto-authorize all resource methods
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role_id', 'status']);
        $users = $this->userService->getUsers($filters, 15);
        
        return view('users.index', compact('users', 'filters'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());
        
        return redirect()
            ->route('user-management.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());
        
        return redirect()
            ->route('user-management.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);
        
        return redirect()
            ->route('user-management.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
```

### Step 9: Observer (Audit Logging)

```php
// app/Observers/UserObserver.php
namespace App\Observers;

use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Events\UserDeleted;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        UserCreated::dispatch($user);
        // Log audit
    }

    public function updated(User $user): void
    {
        $changes = [
            'before' => $user->getOriginal(),
            'after' => $user->getAttributes(),
        ];
        UserUpdated::dispatch($user, $changes);
    }

    public function deleted(User $user): void
    {
        UserDeleted::dispatch($user->id, $user->email);
    }
}
```

### Step 10: Service Provider Registration

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Repository Interface to Implementation
        $this->app->bind(
            UserRepositoryInterface::class, 
            UserRepository::class
        );
    }

    public function boot(): void
    {
        // Register Observer
        User::observe(UserObserver::class);
    }
}
```

### Step 11: Routes

```php
// routes/web.php
use App\Http\Controllers\UserManagementController;

Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::resource('user-management', UserManagementController::class)
        ->parameters(['user-management' => 'user'])
        ->except(['destroy']);
    
    Route::delete('user-management/{user}', [
        UserManagementController::class, 'destroy'
    ])->name('user-management.destroy');
});
```

**CRITICAL**: Jika route name berbeda dengan model singular, HARUS gunakan `->parameters()`!

### Step 12: Menu Registration

```php
// database/seeders/MenuSeeder.php
Menu::create([
    'label' => 'Manajemen User',
    'route' => 'user-management.index',
    'icon' => 'heroicon-o-users',
    'permission' => 'user.manage',
    'order' => 2,
    'is_active' => true,
]);
```

---

## 📦 Implementasi Module Feature

### Module Overview

Laravel Modules (nwidart/laravel-modules) memungkinkan kita membangun fitur sebagai module terpisah yang:
- **Independent**: Memiliki struktur sendiri (controllers, models, views, routes)
- **Reusable**: Dapat digunakan di project lain
- **Maintainable**: Isolasi code per domain
- **Enable/Disable**: Dapat diaktifkan/nonaktifkan

### Module Structure

```
Modules/
└── PrasaranaManagement/
    ├── Config/
    │   └── config.php              # Module configuration
    ├── Console/
    │   └── Commands/               # Artisan commands
    ├── Database/
    │   ├── Migrations/             # Module migrations
    │   ├── Seeders/                # Module seeders
    │   └── factories/              # Model factories
    ├── Entities/                   # Models (bisa pakai Models/)
    │   ├── Gedung.php
    │   └── Ruangan.php
    ├── Http/
    │   ├── Controllers/
    │   │   └── PrasaranaController.php
    │   ├── Middleware/
    │   └── Requests/
    │       ├── StoreGedungRequest.php
    │       └── UpdateGedungRequest.php
    ├── Providers/
    │   ├── PrasaranaManagementServiceProvider.php
    │   └── RouteServiceProvider.php
    ├── Repositories/               # Custom (not auto-generated)
    │   ├── Interfaces/
    │   │   └── GedungRepositoryInterface.php
    │   └── GedungRepository.php
    ├── Resources/
    │   ├── assets/                 # JS, CSS
    │   └── views/                  # Blade templates
    │       └── prasarana/
    │           ├── index.blade.php
    │           └── create.blade.php
    ├── Routes/
    │   ├── web.php                 # Web routes
    │   └── api.php                 # API routes
    ├── Services/                   # Custom (not auto-generated)
    │   └── PrasaranaService.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    ├── composer.json               # Module dependencies
    └── module.json                 # Module metadata
```

### Step 1: Create New Module

```bash
# Create module
php artisan module:make PrasaranaManagement

# Generate components
php artisan module:make-model Gedung PrasaranaManagement
php artisan module:make-controller PrasaranaController PrasaranaManagement
php artisan module:make-request StoreGedungRequest PrasaranaManagement
php artisan module:make-migration create_gedungs_table PrasaranaManagement
php artisan module:make-seeder GedungSeeder PrasaranaManagement
```

### Step 2: Module Configuration (module.json)

```json
{
    "name": "PrasaranaManagement",
    "alias": "prasaranamanagement",
    "description": "Module untuk mengelola prasarana (gedung, ruangan)",
    "keywords": ["prasarana", "gedung", "ruangan", "management"],
    "priority": 0,
    "providers": [
        "Modules\\PrasaranaManagement\\Providers\\PrasaranaManagementServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

### Step 3: Module Models

```php
// Modules/PrasaranaManagement/Entities/Gedung.php
namespace Modules\PrasaranaManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gedung extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode', 'nama', 'lokasi', 'jumlah_lantai', 
        'tahun_dibangun', 'status', 'keterangan'
    ];

    protected $casts = [
        'jumlah_lantai' => 'integer',
        'tahun_dibangun' => 'integer',
    ];

    // Relations
    public function ruangans()
    {
        return $this->hasMany(Ruangan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('kode', 'like', "%{$search}%")
              ->orWhere('nama', 'like', "%{$search}%")
              ->orWhere('lokasi', 'like', "%{$search}%");
        });
    }
}
```

### Step 4: Module Repository

```php
// Modules/PrasaranaManagement/Repositories/Interfaces/GedungRepositoryInterface.php
namespace Modules\PrasaranaManagement\Repositories\Interfaces;

use Modules\PrasaranaManagement\Entities\Gedung;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GedungRepositoryInterface
{
    public function findById(int $id): ?Gedung;
    public function create(array $data): Gedung;
    public function update(Gedung $gedung, array $data): Gedung;
    public function delete(Gedung $gedung): bool;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
```

```php
// Modules/PrasaranaManagement/Repositories/GedungRepository.php
namespace Modules\PrasaranaManagement\Repositories;

use Modules\PrasaranaManagement\Entities\Gedung;
use Modules\PrasaranaManagement\Repositories\Interfaces\GedungRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GedungRepository implements GedungRepositoryInterface
{
    public function findById(int $id): ?Gedung
    {
        return Gedung::with(['ruangans'])->find($id);
    }

    public function create(array $data): Gedung
    {
        return Gedung::create($data);
    }

    public function update(Gedung $gedung, array $data): Gedung
    {
        $gedung->fill($data)->save();
        return $gedung->fresh(['ruangans']);
    }

    public function delete(Gedung $gedung): bool
    {
        return (bool) $gedung->delete();
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Gedung::query()->with(['ruangans']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }
}
```

### Step 5: Module Service

```php
// Modules/PrasaranaManagement/Services/PrasaranaService.php
namespace Modules\PrasaranaManagement\Services;

use Modules\PrasaranaManagement\Entities\Gedung;
use Modules\PrasaranaManagement\Repositories\Interfaces\GedungRepositoryInterface;
use Illuminate\Database\DatabaseManager;

class PrasaranaService
{
    public function __construct(
        private readonly GedungRepositoryInterface $gedungRepository,
        private readonly DatabaseManager $database
    ) {}

    public function createGedung(array $data): Gedung
    {
        return $this->database->transaction(function () use ($data) {
            return $this->gedungRepository->create($data);
        });
    }

    public function updateGedung(Gedung $gedung, array $data): Gedung
    {
        return $this->database->transaction(function () use ($gedung, $data) {
            return $this->gedungRepository->update($gedung, $data);
        });
    }

    public function deleteGedung(Gedung $gedung): void
    {
        // Check if gedung has ruangans
        if ($gedung->ruangans()->count() > 0) {
            throw new \RuntimeException('Gedung masih memiliki ruangan, tidak dapat dihapus.');
        }

        $this->database->transaction(function () use ($gedung) {
            $this->gedungRepository->delete($gedung);
        });
    }
}
```

### Step 6: Module Controller

```php
// Modules/PrasaranaManagement/Http/Controllers/PrasaranaController.php
namespace Modules\PrasaranaManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PrasaranaManagement\Entities\Gedung;
use Modules\PrasaranaManagement\Http\Requests\StoreGedungRequest;
use Modules\PrasaranaManagement\Http\Requests\UpdateGedungRequest;
use Modules\PrasaranaManagement\Services\PrasaranaService;
use Illuminate\Http\Request;

class PrasaranaController extends Controller
{
    public function __construct(
        private readonly PrasaranaService $prasaranaService
    ) {
        $this->middleware('auth');
        $this->middleware('can:prasarana.manage');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);
        $gedungs = $this->prasaranaService->getGedungs($filters, 15);

        return view('prasaranamanagement::prasarana.index', compact('gedungs', 'filters'));
    }

    public function create()
    {
        return view('prasaranamanagement::prasarana.create');
    }

    public function store(StoreGedungRequest $request)
    {
        $gedung = $this->prasaranaService->createGedung($request->validated());

        return redirect()
            ->route('prasarana.index')
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function show(Gedung $gedung)
    {
        $gedung->load(['ruangans']);
        return view('prasaranamanagement::prasarana.show', compact('gedung'));
    }

    public function edit(Gedung $gedung)
    {
        return view('prasaranamanagement::prasarana.edit', compact('gedung'));
    }

    public function update(UpdateGedungRequest $request, Gedung $gedung)
    {
        $this->prasaranaService->updateGedung($gedung, $request->validated());

        return redirect()
            ->route('prasarana.index')
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(Gedung $gedung)
    {
        try {
            $this->prasaranaService->deleteGedung($gedung);
            return redirect()
                ->route('prasarana.index')
                ->with('success', 'Gedung berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('prasarana.show', $gedung)
                ->withErrors($e->getMessage());
        }
    }
}
```

### Step 7: Module Routes

```php
// Modules/PrasaranaManagement/Routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\PrasaranaManagement\Http\Controllers\PrasaranaController;

Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::resource('prasarana', PrasaranaController::class);
});
```

### Step 8: Module Service Provider

```php
// Modules/PrasaranaManagement/Providers/PrasaranaManagementServiceProvider.php
namespace Modules\PrasaranaManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PrasaranaManagement\Repositories\GedungRepository;
use Modules\PrasaranaManagement\Repositories\Interfaces\GedungRepositoryInterface;

class PrasaranaManagementServiceProvider extends ServiceProvider
{
    protected $moduleName = 'PrasaranaManagement';
    protected $moduleNameLower = 'prasaranamanagement';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Bind Repository
        $this->app->bind(
            GedungRepositoryInterface::class,
            GedungRepository::class
        );

        // Singleton Service
        $this->app->singleton(\Modules\PrasaranaManagement\Services\PrasaranaService::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), 
            $this->moduleNameLower
        );
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]), 
            $this->moduleNameLower
        );
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'Resources/lang'), 
                $this->moduleNameLower
            );
        }
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
```

### Step 9: Module Views

```blade
{{-- Modules/PrasaranaManagement/Resources/views/prasarana/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen Prasarana')

@section('content')
<div class="container">
    <h1>Daftar Gedung</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('prasarana.create') }}" class="btn btn-primary">
        Tambah Gedung
    </a>

    <table class="table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Lokasi</th>
                <th>Jumlah Lantai</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gedungs as $gedung)
            <tr>
                <td>{{ $gedung->kode }}</td>
                <td>{{ $gedung->nama }}</td>
                <td>{{ $gedung->lokasi }}</td>
                <td>{{ $gedung->jumlah_lantai }}</td>
                <td>{{ $gedung->status }}</td>
                <td>
                    <a href="{{ route('prasarana.show', $gedung) }}">Detail</a>
                    <a href="{{ route('prasarana.edit', $gedung) }}">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $gedungs->links() }}
</div>
@endsection
```

### Step 10: Module Migration

```php
// Modules/PrasaranaManagement/Database/Migrations/xxxx_create_gedungs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gedungs', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('lokasi');
            $table->integer('jumlah_lantai')->default(1);
            $table->integer('tahun_dibangun')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'renovasi'])->default('aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gedungs');
    }
};
```

### Step 11: Module Commands

```bash
# Run module migrations
php artisan module:migrate PrasaranaManagement

# Run module seeders
php artisan module:seed PrasaranaManagement

# Rollback module migrations
php artisan module:migrate-rollback PrasaranaManagement

# Enable/Disable module
php artisan module:enable PrasaranaManagement
php artisan module:disable PrasaranaManagement

# List all modules
php artisan module:list

# Publish module assets
php artisan module:publish PrasaranaManagement
```

### Step 12: Module Menu Integration

```php
// Core database/seeders/MenuSeeder.php
Menu::create([
    'label' => 'Prasarana',
    'route' => 'prasarana.index',
    'icon' => 'heroicon-o-building-office',
    'permission' => 'prasarana.manage',
    'order' => 10,
    'is_active' => true,
]);
```

### Communication Between Modules & Core

**Accessing Core Models from Module:**
```php
// In Module
use App\Models\User;

$user = User::find(1);
```

**Accessing Module Models from Core:**
```php
// In Core
use Modules\PrasaranaManagement\Entities\Gedung;

$gedung = Gedung::find(1);
```

**Events & Listeners:**
```php
// Module dispatches event
event(new GedungCreated($gedung));

// Core listens to module event
Event::listen(GedungCreated::class, SendNotificationToAdmin::class);
```

---

## ✅ Best Practices

### 1. Naming Conventions

```
Controller:     {Resource}ManagementController (e.g., UserManagementController)
Service:        {Resource}Service (e.g., UserService)
Repository:     {Resource}Repository (e.g., UserRepository)
Interface:      {Resource}RepositoryInterface
Policy:         {Resource}Policy (e.g., UserPolicy)
Request:        {Action}{Resource}Request (e.g., StoreUserRequest)
Observer:       {Resource}Observer (e.g., UserObserver)
Model:          {Resource} (singular, e.g., User)
Table:          {resources} (plural, e.g., users)
Permission:     {resource}.{action} (e.g., user.manage, user.view)
Route:          {resource}-management (e.g., user-management)
```

### 2. Repository Pattern

**DO:**
```php
// ✅ Return types yang jelas
public function findById(int $id): ?User

// ✅ Use interfaces
public function __construct(UserRepositoryInterface $repository)

// ✅ Keep it simple, hanya data access
public function getAll(array $filters = []): LengthAwarePaginator
```

**DON'T:**
```php
// ❌ Business logic di repository
public function createUserWithRole(array $data, Role $role)

// ❌ No return type
public function findById($id)

// ❌ Concrete implementation di constructor
public function __construct(UserRepository $repository)
```

### 3. Service Layer

**DO:**
```php
// ✅ Transaction untuk multiple operations
public function createUser(array $data): User {
    return $this->database->transaction(function () use ($data) {
        $user = $this->repository->create($data);
        $this->assignRole($user, $data['role_id']);
        return $user;
    });
}

// ✅ Validation di service untuk business rules
if ($user->id === Auth::id()) {
    throw new RuntimeException('Tidak bisa hapus diri sendiri.');
}
```

**DON'T:**
```php
// ❌ Direct query di service
public function getUsers() {
    return User::where('status', 'active')->get(); // Use repository!
}

// ❌ No transaction untuk multiple operations
public function createUser(array $data): User {
    $user = $this->repository->create($data);
    $this->assignRole($user, $data['role_id']); // Race condition risk!
    return $user;
}
```

### 4. Controller Layer

**DO:**
```php
// ✅ Thin controller, delegate to service
public function store(StoreUserRequest $request) {
    $user = $this->userService->createUser($request->validated());
    return redirect()->route('users.index')->with('success', 'Berhasil');
}

// ✅ Use authorizeResource for resource controller
public function __construct(UserService $service) {
    $this->authorizeResource(User::class, 'user');
}
```

**DON'T:**
```php
// ❌ Business logic di controller
public function store(Request $request) {
    $user = User::create($request->all());
    $role = Role::find($request->role_id);
    $user->assignRole($role);
    // ... more logic
}

// ❌ Manual authorize di setiap method
public function index() {
    $this->authorize('viewAny', User::class);
    // ...
}
```

### 5. Policy Guidelines

**DO:**
```php
// ✅ Helper method untuk reusable check
protected function canManageUsers(User $user): bool {
    return $user->hasPermissionTo('user.manage');
}

// ✅ Self-access exemption
public function view(User $user, User $target): bool {
    if ($user->id === $target->id) return true;
    return $this->canManageUsers($user);
}

// ✅ Prevent dangerous self-action
public function delete(User $user, User $target): bool {
    if ($user->id === $target->id) return false; // Critical!
    return $this->canManageUsers($user);
}
```

**DON'T:**
```php
// ❌ Role check di policy (use permission!)
public function create(User $user): bool {
    return $user->hasRole('Admin'); // Bad!
}

// ❌ Allow self-delete
public function delete(User $user, User $target): bool {
    return $this->canManageUsers($user); // Dangerous!
}
```

### 6. Form Request

**DO:**
```php
// ✅ Always return true di authorize()
public function authorize(): bool {
    return true; // Delegate to policy
}

// ✅ Clear validation rules
public function rules(): array {
    return [
        'email' => ['required', 'email', 'unique:users'],
    ];
}

// ✅ Custom messages
public function messages(): array {
    return ['email.unique' => 'Email sudah digunakan.'];
}
```

**DON'T:**
```php
// ❌ Authorization di FormRequest
public function authorize(): bool {
    return $this->user()->hasPermissionTo('user.create'); // Use Policy!
}

// ❌ Business logic di rules
public function rules(): array {
    if ($this->user()->isAdmin()) { // Wrong place!
        return ['name' => 'required'];
    }
}
```

### 7. Observer Pattern

**DO:**
```php
// ✅ Only side effects (logging, events)
public function created(User $user): void {
    UserCreated::dispatch($user);
    $this->logAudit('created', $user);
}

// ✅ Remove sensitive data before logging
private function withoutSensitiveData(array $data): array {
    unset($data['password'], $data['remember_token']);
    return $data;
}
```

**DON'T:**
```php
// ❌ Business logic di observer
public function created(User $user): void {
    $user->assignRole('Default User'); // Use service!
}

// ❌ Database queries di observer
public function updated(User $user): void {
    User::where('manager_id', $user->id)->update(...); // Wrong!
}
```

### 8. Permission Naming

**Pattern**: `{resource}.{action}`

```php
// Core permissions
'user.manage'       // Manage user page access
'user.view'         // View individual user
'role.manage'       // Manage role page access
'permission.manage' // Manage permission page access

// Feature permissions
'prasarana.manage'  // Manage prasarana
'sarana.view'       // View sarana
'peminjaman.create' // Create peminjaman
'laporan.export'    // Export laporan
```

**Assignment**:
```php
// Admin Sarpras gets all *.manage permissions
$adminRole->givePermissionTo([
    'user.manage', 'role.manage', 'permission.manage',
    'prasarana.manage', 'sarana.manage'
]);

// User Peminjam gets limited permissions
$peminjamRole->givePermissionTo([
    'peminjaman.create', 'peminjaman.view'
]);
```

### 9. Module Development Best Practices

**DO:**
```php
// ✅ Follow same layer architecture as Core
Module/
  ├── Entities/ (Models)
  ├── Repositories/ (Data access)
  ├── Services/ (Business logic)
  ├── Http/Controllers/ (Request handling)

// ✅ Use module namespace untuk views
return view('prasaranamanagement::prasarana.index');

// ✅ Bind repository di ModuleServiceProvider
$this->app->bind(
    GedungRepositoryInterface::class,
    GedungRepository::class
);

// ✅ Load migrations dari module
$this->loadMigrationsFrom(
    module_path($this->moduleName, 'Database/Migrations')
);

// ✅ Use Core models/services when needed
use App\Models\User;
use App\Services\NotificationService;
```

**DON'T:**
```php
// ❌ Duplicate Core functionality
// Jangan buat UserService di module, use dari Core!

// ❌ Hard-coded view paths
return view('Modules.PrasaranaManagement.Resources.views.index');

// ❌ Direct query di controller
$gedungs = Gedung::all(); // Use service/repository!

// ❌ Module dependencies antar module
// Module A jangan depend on Module B
// Gunakan Core sebagai mediator atau Events
```

**Module Isolation Rules:**
- Module BOLEH depend on Core
- Module JANGAN depend on Module lain
- Communication antar module via Events/Core Services
- Shared functionality → pindah ke Core

---

## ⚠️ Common Issues

### Issue 1: "This action is unauthorized" di show/edit/update/delete

**Cause**: Route parameter name mismatch dengan `authorizeResource()`

```php
// ❌ WRONG
Route::resource('user-management', UserManagementController::class);
// Ini create parameter {user_management}, tapi policy expect {user}

// ✅ CORRECT
Route::resource('user-management', UserManagementController::class)
    ->parameters(['user-management' => 'user']);
```

**Solution**: Selalu gunakan `->parameters()` jika route name ≠ model singular!

### Issue 2: N+1 Query Problem

**Cause**: Eager loading tidak digunakan

```php
// ❌ WRONG (N+1 queries)
$users = User::all();
foreach ($users as $user) {
    echo $user->role->name; // Query per iteration!
}

// ✅ CORRECT
$users = User::with(['role'])->get();
```

**Solution**: Selalu gunakan `with()` di repository untuk relations yang sering dipakai.

### Issue 3: Mass Assignment Vulnerability

**Cause**: `$fillable` tidak di-set atau menggunakan `$guarded = []`

```php
// ❌ WRONG
class User extends Model {
    protected $guarded = []; // Dangerous!
}

// ✅ CORRECT
class User extends Model {
    protected $fillable = ['name', 'email', 'password'];
}
```

### Issue 4: Transaction Tidak Digunakan

**Cause**: Multiple database operations tanpa transaction

```php
// ❌ WRONG
public function createUser(array $data): User {
    $user = $this->repository->create($data);
    $user->assignRole($data['role']);
    return $user; // If assignRole fails, user already created!
}

// ✅ CORRECT
public function createUser(array $data): User {
    return $this->database->transaction(function () use ($data) {
        $user = $this->repository->create($data);
        $user->assignRole($data['role']);
        return $user;
    });
}
```

### Issue 5: Circular Dependency

**Cause**: Service A inject Service B, Service B inject Service A

```php
// ❌ WRONG
class UserService {
    public function __construct(RoleService $roleService) {}
}
class RoleService {
    public function __construct(UserService $userService) {} // Circular!
}

// ✅ CORRECT - Use repository or extract common logic
class UserService {
    public function __construct(
        UserRepositoryInterface $userRepository,
        RoleRepositoryInterface $roleRepository
    ) {}
}
```

### Issue 6: Cache Tidak Di-clear

**Cause**: Data berubah tapi cache tidak di-update

```php
// ✅ SOLUTION - Clear cache di model events
class Menu extends Model {
    protected static function booted() {
        static::saved(function () {
            Cache::forget('sidebar_menus');
        });
    }
}
```

### Issue 7: Module Not Loading

**Cause**: Module provider tidak terdaftar atau module disabled

```bash
# Check module status
php artisan module:list

# Enable module
php artisan module:enable PrasaranaManagement

# Clear cache
php artisan config:clear
php artisan cache:clear
```

**Solution**: Pastikan module.json memiliki provider yang benar dan module dalam status enabled.

### Issue 8: Module Views Not Found

**Cause**: View namespace tidak terdaftar

```php
// ❌ WRONG
return view('prasarana.index'); // Core namespace

// ✅ CORRECT
return view('prasaranamanagement::prasarana.index'); // Module namespace
```

**Solution**: Selalu gunakan module namespace `{moduleNameLower}::` untuk views di module.

### Issue 9: Module Migration Not Running

**Cause**: Menggunakan `php artisan migrate` instead of `module:migrate`

```bash
# ❌ WRONG - Hanya run Core migrations
php artisan migrate

# ✅ CORRECT - Run specific module migrations
php artisan module:migrate PrasaranaManagement

# ✅ CORRECT - Run all modules migrations
php artisan module:migrate
```

---

## 🧪 Testing Checklist

### Functional Testing

- [ ] CRUD operations berfungsi
- [ ] Validation rules berjalan
- [ ] Authorization policies berfungsi
- [ ] Error handling works
- [ ] Flash messages muncul

### Authorization Testing

- [ ] User tanpa permission tidak bisa akses
- [ ] User dengan permission bisa akses
- [ ] Self-access works (view/edit own profile)
- [ ] Self-delete/block prevented
- [ ] Protected resources (Super Admin role) cannot be deleted

### Data Integrity Testing

- [ ] Transaction rollback on error
- [ ] Cascade delete works
- [ ] Unique constraints enforced
- [ ] Foreign key constraints enforced

### Performance Testing

- [ ] No N+1 queries
- [ ] Pagination works
- [ ] Cache implemented where needed
- [ ] Database indexes created

---

## 📚 Additional Resources

### File Templates

- **Policy Template**: `docs/POLICY_GUIDELINES.md`
- **Core Architecture**: `documents/core arsitektur.md`
- **Permission Pattern**: `documents/feature-details/permission-pattern-user-manage.md`

### Command Reference

```bash
# Generate files (if using artisan make commands)
php artisan make:controller UserManagementController --resource
php artisan make:request StoreUserRequest
php artisan make:policy UserPolicy --model=User
php artisan make:observer UserObserver --model=User
php artisan make:migration create_users_table

# Run tests
php artisan test
php artisan test --filter UserManagementTest

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## 📝 Quick Reference Checklist

Ketika membuat fitur baru, ikuti checklist ini:

**Planning Phase:**
- [ ] Define feature scope
- [ ] List required permissions
- [ ] Design database schema
- [ ] Plan validation rules

**Implementation Phase:**
- [ ] Create migration
- [ ] Create/update model with relations
- [ ] Create repository interface
- [ ] Create repository implementation
- [ ] Create service class
- [ ] Create form requests
- [ ] Create policy
- [ ] Create controller
- [ ] Create observer (if needed)
- [ ] Register bindings di AppServiceProvider
- [ ] Create routes with proper parameters
- [ ] Create menu entry
- [ ] Create views

**Quality Assurance:**
- [ ] Test all CRUD operations
- [ ] Test authorization rules
- [ ] Test validation rules
- [ ] Check for N+1 queries
- [ ] Verify transactions work
- [ ] Test error handling

---

**Document Version**: 1.0  
**Last Updated**: 25 November 2025  
**Maintained By**: Development Team

