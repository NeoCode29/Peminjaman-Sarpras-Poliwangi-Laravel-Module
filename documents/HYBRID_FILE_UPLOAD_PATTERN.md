# 🔄 Hybrid File Upload Pattern

## 📌 Overview

Pattern hybrid untuk file upload yang menggabungkan:
- **Shared Core Utility** (`FileUploadService`) untuk physical file handling
- **Module-Specific Implementation** (Repository, Policy, Observer, Service) untuk business logic

Pattern ini konsisten dengan **core arsitektur** sambil tetap **reusable** dan **scalable**.

---

## 🏗️ Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    CORE/SHARED (app/)                    │
│                                                           │
│  FileUploadService.php                                   │
│    ↓ Pure utility for physical file operations          │
│    • upload()          - Upload file ke storage          │
│    • deleteFile()      - Delete file dari storage        │
│    • getTemporaryUrl() - Generate signed URL             │
│    • cleanupTemp()     - Cleanup temp files              │
│                                                           │
│  FileUploadRequest.php  - Base validation                │
│  config/upload.php      - Configuration                  │
└─────────────────────────────────────────────────────────┘
                            ↓ Injected into
         ┌──────────────────┴──────────────────┐
         ↓                                      ↓
┌────────────────────────┐        ┌────────────────────────┐
│   MODULE: Sarpras      │        │  MODULE: Peminjaman    │
│                        │        │                        │
│  Controller            │        │  Controller            │
│      ↓                 │        │      ↓                 │
│  Service (inject       │        │  Service (inject       │
│    FileUploadService)  │        │    FileUploadService)  │
│      ↓                 │        │      ↓                 │
│  Repository            │        │  Repository            │
│      ↓                 │        │      ↓                 │
│  Model (Sarpras)       │        │  Model (Peminjaman)    │
│      ↓                 │        │      ↓                 │
│  Observer              │        │  Observer              │
│      ↓                 │        │      ↓                 │
│  Event → Listener      │        │  Event → Listener      │
│                        │        │                        │
│  Policy (authorization)│        │  Policy (authorization)│
│                        │        │                        │
│  Business Rules:       │        │  Business Rules:       │
│  • Max 5 foto/sarpras  │        │  • Butuh KTP upload    │
│  • Auto thumbnail      │        │  • Approval flow files │
│  • Public files        │        │  • Private files       │
└────────────────────────┘        └────────────────────────┘
```

---

## 📁 Directory Structure

```
app/                                # CORE/SHARED
├── Services/
│   └── FileUploadService.php      # ✅ Physical file operations only
├── Http/
│   └── Requests/
│       └── FileUploadRequest.php  # ✅ Base validation
└── Models/
    └── UploadedFile.php           # ⚠️ Optional shared model

Modules/Sarpras/                   # MODULE IMPLEMENTATION
├── Http/
│   ├── Controllers/
│   │   └── SarprasController.php
│   └── Requests/
│       ├── StoreSarprasRequest.php
│       └── UpdateSarprasRequest.php
├── Services/
│   └── SarprasService.php         # ✅ Inject FileUploadService
├── Repositories/
│   ├── Interfaces/
│   │   └── SarprasRepositoryInterface.php
│   └── SarprasRepository.php      # ✅ Handle DB operations
├── Policies/
│   └── SarprasPolicy.php          # ✅ Authorization
├── Observers/
│   └── SarprasObserver.php        # ✅ Audit logging
├── Events/
│   └── SarprasAuditLogged.php
├── Listeners/
│   └── StoreSarprasAudit.php
└── Models/
    └── Sarpras.php                # ✅ With files() polymorphic relation

Modules/Peminjaman/                # MODULE IMPLEMENTATION
├── Http/
│   └── Controllers/
│       └── PeminjamanController.php
├── Services/
│   └── PeminjamanService.php      # ✅ Inject FileUploadService
├── Repositories/
│   └── PeminjamanRepository.php
├── Policies/
│   └── PeminjamanPolicy.php
├── Observers/
│   └── PeminjamanObserver.php
└── Models/
    └── Peminjaman.php
```

---

## 🔑 Key Principles

### 1. **FileUploadService = Pure Utility**

**Responsibility:** HANYA physical file operations
- Upload file ke storage
- Delete file dari storage  
- Generate URLs
- File validation (MIME, size, etc.)

**TIDAK boleh:**
- ❌ Business logic (max files per item, etc.)
- ❌ Authorization checks
- ❌ Database operations
- ❌ Module-specific rules

```php
// ✅ GOOD - Pure utility
$result = $fileUploadService->upload($file, 'image', 'sarpras', 'public');

// ❌ BAD - Business logic in utility
$fileUploadService->uploadSarprasImage($sarpras, $file); // NO!
```

### 2. **Module Service = Business Logic**

**Responsibility:** Module-specific business logic
- Validasi jumlah file per item
- Transaksi database
- Koordinasi Repository + FileUploadService
- Module-specific rules

```php
// ✅ GOOD - Module service
class SarprasService
{
    public function __construct(
        private SarprasRepository $repository,
        private FileUploadService $fileUploadService // Inject utility
    ) {}
    
    public function createWithFiles(array $data, array $files): Sarpras
    {
        // Business logic: max 5 files
        if (count($files) > 5) {
            throw new Exception('Maksimal 5 foto per sarpras');
        }
        
        return $this->database->transaction(function () use ($data, $files) {
            $sarpras = $this->repository->create($data);
            
            foreach ($files as $file) {
                // Use utility for physical upload
                $result = $this->fileUploadService->upload(
                    $file, 'image', 'sarpras', 'public'
                );
                
                // Save metadata via repository
                $this->repository->attachFile($sarpras, $result);
            }
            
            return $sarpras;
        });
    }
}
```

### 3. **Repository = Data Access**

**Responsibility:** Database operations
- CRUD operations
- Query building
- File metadata handling
- Relations

```php
// ✅ GOOD - Repository
class SarprasRepository
{
    public function attachFile(Sarpras $sarpras, array $fileData): void
    {
        $sarpras->files()->create([
            'file_path' => $fileData['path'],
            'file_url' => $fileData['url'],
            'file_type' => 'image',
            'uploaded_by' => auth()->id(),
        ]);
    }
}
```

### 4. **Policy = Authorization**

**Responsibility:** Access control
- Who can upload files?
- Who can delete files?
- Module-specific permissions

```php
// ✅ GOOD - Policy
class SarprasPolicy
{
    public function uploadFiles(User $user, Sarpras $sarpras): bool
    {
        return $user->can('sarpras.manage') 
            && $sarpras->status !== 'archived';
    }
    
    public function deleteFiles(User $user, Sarpras $sarpras): bool
    {
        return $user->can('sarpras.manage') 
            || $user->id === $sarpras->created_by;
    }
}
```

### 5. **Observer = Audit Logging**

**Responsibility:** Event triggering
- Monitor model changes
- Dispatch audit events
- NO business logic

```php
// ✅ GOOD - Observer
class SarprasObserver
{
    public function created(Sarpras $sarpras): void
    {
        event(new SarprasAuditLogged(
            action: 'sarpras.created',
            sarpras: $sarpras,
            attributes: $sarpras->toArray(),
            performedBy: auth()->id(),
            metadata: [
                'files_count' => $sarpras->files()->count(),
            ]
        ));
    }
}
```

---

## 💼 Implementation Example: Sarpras Module

### Step 1: Model dengan Polymorphic Relation

```php
// Modules/Sarpras/Models/Sarpras.php
class Sarpras extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'nama_sarpras',
        'kode_sarpras',
        'kategori_id',
        'deskripsi',
        'jumlah_total',
        'status',
    ];
    
    /**
     * Polymorphic relation to uploaded files
     */
    public function files()
    {
        return $this->morphMany(UploadedFile::class, 'uploadable');
    }
    
    /**
     * Get main image (first uploaded file)
     */
    public function mainImage()
    {
        return $this->morphOne(UploadedFile::class, 'uploadable')
            ->where('file_type', 'image')
            ->oldest();
    }
}
```

### Step 2: Repository Interface

```php
// Modules/Sarpras/Repositories/Interfaces/SarprasRepositoryInterface.php
interface SarprasRepositoryInterface
{
    public function create(array $data): Sarpras;
    public function update(Sarpras $sarpras, array $data): Sarpras;
    public function delete(Sarpras $sarpras): bool;
    public function attachFile(Sarpras $sarpras, array $fileData): UploadedFile;
    public function detachFile(UploadedFile $file): bool;
    public function getAllWithFiles(array $filters = []): LengthAwarePaginator;
}
```

### Step 3: Repository Implementation

```php
// Modules/Sarpras/Repositories/SarprasRepository.php
class SarprasRepository implements SarprasRepositoryInterface
{
    public function create(array $data): Sarpras
    {
        return Sarpras::create($data);
    }
    
    public function attachFile(Sarpras $sarpras, array $fileData): UploadedFile
    {
        return $sarpras->files()->create([
            'user_id' => auth()->id(),
            'file_type' => $fileData['type'] ?? 'image',
            'category' => 'sarpras',
            'original_name' => $fileData['original_name'] ?? '',
            'stored_name' => $fileData['filename'],
            'file_path' => $fileData['path'],
            'mime_type' => $fileData['mime_type'] ?? 'image/jpeg',
            'size' => $fileData['size'] ?? 0,
            'disk' => $fileData['disk'] ?? 'public',
            'is_public' => true,
        ]);
    }
    
    public function detachFile(UploadedFile $file): bool
    {
        return $file->delete();
    }
    
    public function getAllWithFiles(array $filters = []): LengthAwarePaginator
    {
        return Sarpras::with(['files', 'kategori'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('nama_sarpras', 'like', "%{$search}%");
            })
            ->paginate($filters['per_page'] ?? 15);
    }
}
```

### Step 4: Service Layer

```php
// Modules/Sarpras/Services/SarprasService.php
class SarprasService
{
    public function __construct(
        private readonly SarprasRepository $repository,
        private readonly FileUploadService $fileUploadService,
        private readonly DatabaseManager $database,
    ) {}
    
    /**
     * Create sarpras dengan upload files
     */
    public function createWithFiles(array $data, array $files = []): Sarpras
    {
        // Validasi business rule
        if (count($files) > 5) {
            throw new InvalidArgumentException('Maksimal 5 foto per sarpras');
        }
        
        return $this->database->transaction(function () use ($data, $files) {
            // 1. Create sarpras
            $sarpras = $this->repository->create([
                'nama_sarpras' => $data['nama_sarpras'],
                'kode_sarpras' => $data['kode_sarpras'],
                'kategori_id' => $data['kategori_id'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'jumlah_total' => $data['jumlah_total'],
                'status' => 'tersedia',
            ]);
            
            // 2. Upload & attach files
            foreach ($files as $file) {
                // Use shared utility for physical upload
                $result = $this->fileUploadService->upload(
                    file: $file,
                    type: 'image',
                    category: 'sarpras',
                    disk: 'public',
                    options: [
                        'optimize' => true,
                        'thumbnail' => true,
                    ]
                );
                
                // Attach file metadata via repository
                $this->repository->attachFile($sarpras, [
                    'type' => 'image',
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $result['filename'],
                    'path' => $result['path'],
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => 'public',
                ]);
            }
            
            // Observer akan trigger audit log otomatis
            return $sarpras->fresh(['files', 'kategori']);
        });
    }
    
    /**
     * Update sarpras dengan optional new files
     */
    public function updateWithFiles(Sarpras $sarpras, array $data, array $newFiles = [], array $deleteFileIds = []): Sarpras
    {
        return $this->database->transaction(function () use ($sarpras, $data, $newFiles, $deleteFileIds) {
            // 1. Update sarpras data
            $sarpras = $this->repository->update($sarpras, $data);
            
            // 2. Delete old files if requested
            foreach ($deleteFileIds as $fileId) {
                $file = UploadedFile::findOrFail($fileId);
                
                // Delete physical file
                $this->fileUploadService->deleteFile($file->file_path, $file->disk);
                
                // Delete metadata
                $this->repository->detachFile($file);
            }
            
            // 3. Upload new files
            $currentFileCount = $sarpras->files()->count();
            if ($currentFileCount + count($newFiles) > 5) {
                throw new InvalidArgumentException('Total file tidak boleh lebih dari 5');
            }
            
            foreach ($newFiles as $file) {
                $result = $this->fileUploadService->upload(
                    $file, 'image', 'sarpras', 'public', ['optimize' => true]
                );
                
                $this->repository->attachFile($sarpras, [
                    'type' => 'image',
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $result['filename'],
                    'path' => $result['path'],
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => 'public',
                ]);
            }
            
            return $sarpras->fresh(['files', 'kategori']);
        });
    }
    
    /**
     * Delete sarpras beserta files
     */
    public function delete(Sarpras $sarpras): bool
    {
        return $this->database->transaction(function () use ($sarpras) {
            // Delete all files
            foreach ($sarpras->files as $file) {
                $this->fileUploadService->deleteFile($file->file_path, $file->disk);
                $this->repository->detachFile($file);
            }
            
            // Delete sarpras
            return $this->repository->delete($sarpras);
        });
    }
}
```

### Step 5: Controller

```php
// Modules/Sarpras/Http/Controllers/SarprasController.php
class SarprasController extends Controller
{
    public function __construct(
        private readonly SarprasService $service
    ) {
        $this->authorizeResource(Sarpras::class, 'sarpras');
    }
    
    public function store(StoreSarprasRequest $request)
    {
        $sarpras = $this->service->createWithFiles(
            data: $request->validated(),
            files: $request->file('files', [])
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Sarpras berhasil ditambahkan',
            'data' => $sarpras,
        ], 201);
    }
    
    public function update(UpdateSarprasRequest $request, Sarpras $sarpras)
    {
        $sarpras = $this->service->updateWithFiles(
            sarpras: $sarpras,
            data: $request->validated(),
            newFiles: $request->file('new_files', []),
            deleteFileIds: $request->input('delete_file_ids', [])
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Sarpras berhasil diupdate',
            'data' => $sarpras,
        ]);
    }
    
    public function destroy(Sarpras $sarpras)
    {
        $this->service->delete($sarpras);
        
        return response()->json([
            'success' => true,
            'message' => 'Sarpras berhasil dihapus',
        ]);
    }
}
```

### Step 6: Policy

```php
// Modules/Sarpras/Policies/SarprasPolicy.php
class SarprasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sarpras.manage') || $user->can('sarpras.view');
    }
    
    public function create(User $user): bool
    {
        return $user->can('sarpras.manage');
    }
    
    public function update(User $user, Sarpras $sarpras): bool
    {
        return $user->can('sarpras.manage');
    }
    
    public function delete(User $user, Sarpras $sarpras): bool
    {
        // Tidak bisa delete jika sedang dipinjam
        if ($sarpras->peminjaman()->whereIn('status', ['pending', 'approved'])->exists()) {
            return false;
        }
        
        return $user->can('sarpras.manage');
    }
    
    public function uploadFiles(User $user, Sarpras $sarpras): bool
    {
        return $user->can('sarpras.manage') 
            && $sarpras->status !== 'archived';
    }
    
    public function deleteFiles(User $user, Sarpras $sarpras): bool
    {
        return $user->can('sarpras.manage');
    }
}
```

### Step 7: Observer & Event

```php
// Modules/Sarpras/Observers/SarprasObserver.php
class SarprasObserver
{
    public function created(Sarpras $sarpras): void
    {
        event(new SarprasAuditLogged(
            action: 'sarpras.created',
            sarpras: $sarpras,
            attributes: $sarpras->toArray(),
            original: [],
            performedBy: auth()->id(),
            metadata: [
                'files_count' => $sarpras->files()->count(),
                'files' => $sarpras->files->pluck('file_path')->toArray(),
            ]
        ));
    }
    
    public function updated(Sarpras $sarpras): void
    {
        event(new SarprasAuditLogged(
            action: 'sarpras.updated',
            sarpras: $sarpras,
            attributes: $sarpras->toArray(),
            original: $sarpras->getOriginal(),
            performedBy: auth()->id(),
            metadata: [
                'changed_fields' => array_keys($sarpras->getDirty()),
            ]
        ));
    }
    
    public function deleted(Sarpras $sarpras): void
    {
        event(new SarprasAuditLogged(
            action: 'sarpras.deleted',
            sarpras: $sarpras,
            attributes: [],
            original: $sarpras->getOriginal(),
            performedBy: auth()->id(),
            metadata: [
                'deleted_files_count' => $sarpras->files()->count(),
            ]
        ));
    }
}
```

---

## ✅ Benefits

1. **Konsisten dengan Core Architecture** - Repository → Service → Controller flow
2. **Reusable** - FileUploadService digunakan semua module
3. **Testable** - Easy to mock dependencies
4. **Scalable** - Tambah module baru dengan pattern yang sama
5. **Maintainable** - Business logic terisolasi per module
6. **Auditable** - Observer + Event + Listener untuk audit trail

---

## 🧪 Testing Example

```php
// tests/Unit/Sarpras/SarprasServiceTest.php
class SarprasServiceTest extends TestCase
{
    public function test_create_sarpras_with_files()
    {
        // Mock dependencies
        $mockRepo = Mockery::mock(SarprasRepository::class);
        $mockFileService = Mockery::mock(FileUploadService::class);
        
        // Setup expectations
        $mockRepo->shouldReceive('create')->once()->andReturn(new Sarpras());
        $mockFileService->shouldReceive('upload')->times(2)->andReturn([
            'path' => 'sarpras/2025/11/uuid.jpg',
            'url' => 'http://domain.com/storage/sarpras/2025/11/uuid.jpg',
            'filename' => 'uuid.jpg',
        ]);
        
        $service = new SarprasService($mockRepo, $mockFileService, DB::getFacadeRoot());
        
        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
        ];
        
        $result = $service->createWithFiles(['nama_sarpras' => 'Test'], $files);
        
        $this->assertInstanceOf(Sarpras::class, $result);
    }
}
```

---

## 📚 Summary

**FileUploadService (Shared):**
- ✅ Pure utility
- ✅ Physical file operations only
- ✅ No business logic
- ✅ Reusable across all modules

**Module Implementation:**
- ✅ Repository - Data access
- ✅ Service - Business logic + inject FileUploadService
- ✅ Policy - Authorization
- ✅ Observer - Audit logging
- ✅ Controller - Orchestration

**Pattern ini memberikan:**
- Clean separation of concerns
- Easy to test
- Scalable architecture
- Consistent with core patterns

---

**Created**: 2025-11-23  
**Version**: 1.0.0  
**Pattern**: Hybrid File Upload with Modular Architecture
