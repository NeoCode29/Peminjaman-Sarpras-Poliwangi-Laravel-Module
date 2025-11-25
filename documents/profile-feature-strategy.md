# Strategi Implementasi Fitur Profile Management

## 📋 Executive Summary

Dokumen ini menguraikan strategi migrasi dan implementasi fitur **Profile Management** dari `project_baru` ke `project_fix` dengan mengikuti arsitektur berlapis (Controller → Service → Repository → Model) yang sudah diterapkan pada fitur Auth & OAuth.

---

## 🎯 Tujuan

1. Implementasi fitur profile lengkap di `project_fix` core monolithic
2. Mengatasi missing component SSO flow (ProfileController yang belum ada)
3. Memastikan konsistensi arsitektur dengan Auth implementation
4. Menyediakan UI berbasis komponen blade yang sudah dibuat

---

## 🔍 Analisis Fitur Profile di project_baru

### Fitur Yang Tersedia

#### 1. **Profile Setup** (Kelengkapan Profil)
- **Route**: `GET /setup`, `POST /setup`
- **Fungsi**: Melengkapi data profil setelah registrasi/SSO login
- **Field Required**:
  - **Mahasiswa**: phone, jurusan_id, prodi_id
  - **Staff**: phone, unit_id, position_id, nip (optional)
- **Business Logic**:
  - Readonly: name, email, username
  - Auto-extract angkatan dari NIM
  - Create Student/StaffEmployee record
  - Mark `profile_completed = true`
  - Redirect ke dashboard setelah selesai

#### 2. **Profile Show** (Lihat Profil)
- **Route**: `GET /profile`
- **Fungsi**: Menampilkan informasi profil lengkap
- **Data Display**: User basic info + Student/StaffEmployee data + Role

#### 3. **Profile Edit** (Edit Profil)
- **Route**: `GET /profile/edit`, `PUT /profile`
- **Fungsi**: Update informasi profil
- **Editable**: name, email, phone, jurusan, prodi, unit, position, nip
- **Business Logic**: Validasi + Update User + Update Student/StaffEmployee

#### 4. **Change Password** (Ganti Password)
- **Route**: `GET /profile/password/edit`, `PUT /profile/password`
- **Fungsi**: Ganti password untuk user non-SSO
- **Validasi**: 
  - Current password check
  - Password min 8 char, mixed case, numbers
  - Confirmation match
- **Business Logic**: Blokir SSO user dari ganti password

#### 5. **AJAX Endpoint**
- **Route**: `GET /profile/get-prodis`
- **Fungsi**: Fetch Prodi berdasarkan Jurusan ID (dynamic dropdown)

---

## 🏗️ Arsitektur Target (project_fix)

```
┌─────────────────────────────────────────────────────────┐
│                    ProfileController                     │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐│
│  │    setup    │  │     show     │  │   edit/update   ││
│  │ completeSetup│  │              │  │  changePassword ││
│  └──────┬──────┘  └──────┬───────┘  └────────┬────────┘│
└─────────┼─────────────────┼───────────────────┼─────────┘
          │                 │                   │
          ▼                 ▼                   ▼
┌─────────────────────────────────────────────────────────┐
│                    ProfileService                        │
│  - completeProfileSetup($user, $data)                   │
│  - updateProfile($user, $data)                          │
│  - updatePassword($user, $currentPassword, $newPassword)│
│  - getProfileData($user)                                │
│  - dispatchAuditEvent()                                 │
└─────────┬───────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────┐
│              StudentRepository / StaffRepository         │
│  - create($attributes)                                  │
│  - findByUserId($userId)                                │
│  - update($model, $attributes)                          │
└─────────┬───────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────┐
│           Student / StaffEmployee Models                 │
│  - Relations: user, jurusan, prodi, unit, position      │
└─────────────────────────────────────────────────────────┘
```

---

## 📦 Komponen Yang Sudah Ada di project_fix

### ✅ Models
- `User` - dengan method `isProfileCompleted()`, `markProfileCompleted()`, `isSsoUser()`, `updatePassword()`, dll
- `Student` - relasi user, jurusan, prodi
- `StaffEmployee` - relasi user, unit, position
- `Jurusan`, `Prodi`, `Unit`, `Position`

### ✅ Infrastructure
- Migration `profile_completed` field di users table
- Middleware `EnsureProfileCompleted` (sudah dibuat)
- Route placeholder di `web.php` (referensi ProfileController belum ada)

### ✅ Blade Components
- `<x-card>` dengan varian centered
- `<x-input.text>`, `<x-input.select>`, `<x-input.checkbox>`
- `<x-button>` dengan variant dan icon

### ❌ Yang Belum Ada
- `ProfileController`
- `ProfileService`
- `StudentRepository` & `StaffRepository`
- `ProfileRequest` (form validation)
- View: `profile/setup.blade.php`, `profile/show.blade.php`, `profile/edit.blade.php`, `profile/change-password.blade.php`

---

## 🎯 Fase Implementasi

### **Fase 1: Repository Layer**
**Durasi**: 30-45 menit

#### 1.1 StudentRepository
```php
// app/Repositories/Interfaces/StudentRepositoryInterface.php
- findByUserId(int $userId): ?Student
- create(array $attributes): Student
- update(Student $student, array $attributes): Student
```

#### 1.2 StaffEmployeeRepository
```php
// app/Repositories/Interfaces/StaffEmployeeRepositoryInterface.php
- findByUserId(int $userId): ?StaffEmployee
- create(array $attributes): StaffEmployee
- update(StaffEmployee $staff, array $attributes): StaffEmployee
```

#### 1.3 Binding di AppServiceProvider
```php
$this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
$this->app->bind(StaffEmployeeRepositoryInterface::class, StaffEmployeeRepository::class);
```

---

### **Fase 2: Service Layer**
**Durasi**: 1-1.5 jam

#### 2.1 ProfileService
```php
// app/Services/ProfileService.php

class ProfileService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly StaffEmployeeRepositoryInterface $staffRepository,
        private readonly DatabaseManager $database,
    ) {}

    public function completeProfileSetup(User $user, array $data): User
    {
        // Transaction
        // Validate user belum complete
        // Update phone
        // Create Student/StaffEmployee
        // Mark profile completed
        // Dispatch audit event
    }

    public function updateProfile(User $user, array $data): User
    {
        // Transaction
        // Update User basic info
        // Update Student/StaffEmployee
        // Dispatch audit event
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        // Check if SSO user
        // Verify current password
        // Update password
        // Dispatch audit event
    }

    public function getProfileData(User $user): array
    {
        // Load relations
        // Return structured data
    }
}
```

**Fitur**:
- Transaction untuk data integrity
- Audit logging via `UserAuditLogged` event
- Business rules enforcement (SSO user tidak bisa ganti password)
- Auto-extract angkatan dari NIM

---

### **Fase 3: Request Validation Layer**
**Durasi**: 30 menit

#### 3.1 ProfileSetupRequest
```php
// app/Http/Requests/Profile/ProfileSetupRequest.php
- phone: required, string, min:10, max:15
- [mahasiswa] jurusan_id: required, exists:jurusan,id
- [mahasiswa] prodi_id: required, exists:prodi,id
- [staff] unit_id: required, exists:units,id
- [staff] position_id: required, exists:positions,id
- [staff] nip: nullable, string, unique:staff_employees
```

#### 3.2 ProfileUpdateRequest
```php
// app/Http/Requests/Profile/ProfileUpdateRequest.php
- name: required, string, max:255
- email: required, email, unique:users,email,{user_id}
- phone: required, string, min:10, max:15
- dynamic fields based on user_type
```

#### 3.3 ChangePasswordRequest
```php
// app/Http/Requests/Profile/ChangePasswordRequest.php
- current_password: required, string
- password: required, confirmed, Password::min(8)->mixedCase()->numbers()
```

---

### **Fase 4: Controller Layer**
**Durasi**: 1 jam

#### 4.1 ProfileController
```php
// app/Http/Controllers/ProfileController.php

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function setup(): View
    public function completeSetup(ProfileSetupRequest $request): RedirectResponse
    public function show(): View
    public function edit(): View
    public function update(ProfileUpdateRequest $request): RedirectResponse
    public function changePassword(): View
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    public function getProdisByJurusan(Request $request): JsonResponse
}
```

**Responsibilities**:
- Validasi via FormRequest
- Delegasi ke ProfileService
- Handle response dan redirect
- Flash messages

---

### **Fase 5: Routes & Config**
**Durasi**: 15 menit

#### 5.1 Update routes/web.php
```php
// Profile Setup (tanpa middleware profile.completed)
Route::middleware(['auth', 'user.not.blocked'])->group(function () {
    Route::get('/setup', [ProfileController::class, 'setup'])->name('profile.setup');
    Route::post('/setup', [ProfileController::class, 'completeSetup'])->name('profile.complete-setup');
    Route::get('/profile/get-prodis', [ProfileController::class, 'getProdisByJurusan'])->name('profile.get-prodis');
});

// Profile Management (dengan middleware profile.completed)
Route::middleware(['auth', 'user.not.blocked', 'profile.completed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password/edit', [ProfileController::class, 'changePassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});
```

#### 5.2 Update config/services.php
```php
'oauth_server' => [
    'sso_enable' => env('SSO_ENABLE', false),
    'provider' => env('OAUTH_PROVIDER', 'poliwangi'), // ✅ TAMBAH INI
    'client_id' => env('OAUTH_SERVER_ID'),
    // ... rest
],
```

---

### **Fase 6: View Layer**
**Durasi**: 2-3 jam

#### 6.1 profile/setup.blade.php
- Card centered dengan header "Lengkapi Profil"
- Form dengan readonly name, email
- Input phone (editable)
- Conditional: Mahasiswa → Jurusan + Prodi, Staff → Unit + Position + NIP
- Dynamic Prodi dropdown via AJAX
- Submit button

#### 6.2 profile/show.blade.php
- Layout dengan sidebar (navigation)
- Card profil dengan avatar placeholder
- Display info: name, email, phone, user_type
- Conditional data display (Student/Staff info)
- Button: Edit Profil, Change Password (jika bukan SSO)

#### 6.3 profile/edit.blade.php
- Similar layout dengan show
- Form editable: name, email, phone
- Conditional editable fields (Student/Staff)
- Submit + Cancel button

#### 6.4 profile/change-password.blade.php
- Card dengan form password
- Input: current_password, password, password_confirmation
- Toggle visibility password
- Submit button

#### 6.5 profile/layout.blade.php (shared)
- Sidebar navigation: Profile, Edit, Change Password
- Responsive layout
- Breadcrumb

---

## 🔧 Konfigurasi Tambahan

### Environment Variables (.env.example)
```env
# OAuth / SSO Settings
SSO_ENABLE=false
OAUTH_PROVIDER=poliwangi  # ✅ TAMBAH
OAUTH_SERVER_URI=
OAUTH_SERVER_ID=
OAUTH_SERVER_SECRET=
OAUTH_SERVER_REDIRECT_URI=${APP_URL}/oauth/callback
OAUTH_SERVER_LOGOUT_URI=
```

---

## 🧪 Testing Strategy

### Unit Tests
- `ProfileServiceTest`: completeProfileSetup, updateProfile, updatePassword
- `StudentRepositoryTest`: CRUD operations
- `StaffEmployeeRepositoryTest`: CRUD operations

### Feature Tests
- `ProfileSetupTest`: 
  - mahasiswa dapat complete setup
  - staff dapat complete setup
  - redirect jika sudah completed
- `ProfileManagementTest`:
  - show profile
  - update profile
  - change password (local user)
  - block SSO user dari change password
- `AjaxTest`: getProdisByJurusan endpoint

### Manual Testing Checklist
- [ ] SSO login → redirect ke setup
- [ ] Complete setup mahasiswa → mark completed
- [ ] Complete setup staff → mark completed
- [ ] Edit profile → data tersimpan
- [ ] Change password local user → berhasil
- [ ] Change password SSO user → ditolak
- [ ] Dynamic Prodi dropdown berfungsi
- [ ] Middleware profile.completed berfungsi

---

## ⚠️ Risiko & Mitigasi

### Risiko 1: Data Integrity saat Create Student/Staff
**Mitigasi**: Gunakan database transaction di ProfileService

### Risiko 2: SSO User Masuk Tanpa Data Lengkap
**Mitigasi**: 
- Middleware `EnsureProfileCompleted` redirect ke setup
- OAuthController callback redirect ke setup jika `!profile_completed`

### Risiko 3: Conflict dengan Existing User Data
**Mitigasi**: Validasi unique constraints (email, nip)

### Risiko 4: Missing Jurusan/Prodi/Unit/Position Master Data
**Mitigasi**: 
- Seeder untuk master data
- Soft validation dengan helpful error message

---

## 📊 Dependency & Prerequisites

### Database
- ✅ `users` table dengan `profile_completed`, `profile_completed_at`
- ✅ `students` table
- ✅ `staff_employees` table
- ✅ `jurusan`, `prodi`, `units`, `positions` tables

### Code
- ✅ User Model dengan helper methods
- ✅ Student, StaffEmployee models
- ✅ Middleware `EnsureProfileCompleted`
- ✅ Blade components

### Configuration
- ❌ Config `oauth_server.provider` (perlu ditambahkan)
- ❌ ProfileController, ProfileService, Repositories (perlu dibuat)

---

## 🎨 UI/UX Considerations

### Konsistensi Styling
- Gunakan komponen blade yang sudah ada (`<x-card>`, `<x-input.*>`, `<x-button>`)
- Font, color, spacing mengikuti login/register page
- Responsive design untuk mobile

### User Flow
```
SSO Login → OAuthCallback 
   ↓
Profile Incomplete? 
   ↓ YES
Setup Form → Complete → Dashboard
   ↓ NO
Dashboard
```

### Accessibility
- Label jelas untuk setiap input
- Error message inline di bawah input
- Focus state pada form elements
- Keyboard navigation support

---

## 📝 Checklist Implementasi

### Preparation
- [ ] Backup database
- [ ] Review existing User model methods
- [ ] Pastikan master data tersedia (Jurusan, Prodi, Unit, Position)

### Development
- [ ] **Fase 1**: Repository layer (Student, StaffEmployee)
- [ ] **Fase 2**: ProfileService implementation
- [ ] **Fase 3**: Form Request validation classes
- [ ] **Fase 4**: ProfileController implementation
- [ ] **Fase 5**: Routes update + config update
- [ ] **Fase 6**: Blade views (setup, show, edit, change-password)

### Quality Assurance
- [ ] Unit tests untuk Service & Repository
- [ ] Feature tests untuk Profile flow
- [ ] Manual testing checklist
- [ ] Code review

### Deployment
- [ ] Run migrations (jika ada perubahan)
- [ ] Update .env.example
- [ ] Seed master data jika perlu
- [ ] Deploy & smoke test

---

## 🚀 Estimasi Waktu

| Fase | Estimasi | Prioritas |
|------|----------|-----------|
| Fase 1: Repository | 30-45 menit | HIGH |
| Fase 2: Service | 1-1.5 jam | HIGH |
| Fase 3: Request | 30 menit | MEDIUM |
| Fase 4: Controller | 1 jam | HIGH |
| Fase 5: Routes/Config | 15 menit | HIGH |
| Fase 6: Views | 2-3 jam | HIGH |
| Testing | 1-2 jam | MEDIUM |
| **Total** | **6-9 jam** | |

---

## 📌 Next Steps

1. **Review strategi** dengan user
2. **Mulai Fase 1** jika disetujui
3. **Implementasi bertahap** dengan testing di setiap fase
4. **Update dokumentasi** setelah selesai

---

## 🔗 Referensi

- `project_baru/app/Http/Controllers/ProfileController.php` - Source logic
- `project_fix/documents/core arsitektur.md` - Arsitektur berlapis
- `project_fix/app/Services/AuthService.php` - Pattern reference
- `project_fix/resources/views/components/` - UI components

---

**Dokumen dibuat**: {{ date }}  
**Status**: DRAFT - Menunggu Approval  
**Penulis**: Cascade AI Assistant
