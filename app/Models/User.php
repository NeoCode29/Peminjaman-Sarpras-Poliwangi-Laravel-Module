<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'address',
        'bio',
        'user_type',
        'status',
        'role_id',
        'profile_completed',
        'profile_completed_at',
        'blocked_until',
        'blocked_reason',
        'sso_id',
        'sso_provider',
        'sso_data',
        'last_sso_login',
        'last_login_at',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
        'login_count',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'blocked_until' => 'datetime',
            'last_sso_login' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_activity_at' => 'datetime',
            'profile_completed' => 'boolean',
            'sso_data' => 'array',
            'login_count' => 'integer',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi role tunggal pengguna.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    /**
     * Relasi data mahasiswa.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Relasi data pegawai/staff.
     */
    public function staffEmployee(): HasOne
    {
        return $this->hasOne(StaffEmployee::class);
    }

    /**
     * Relasi token OAuth pengguna.
     */
    public function token(): HasOne
    {
        return $this->hasOne(OAuthToken::class);
    }

    /**
     * Scope filter user aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope filter berdasarkan tipe pengguna.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope filter berdasarkan role ID.
     */
    public function scopeByRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope pencarian nama, username, atau email.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Cek apakah user berstatus diblokir.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked' || ($this->blocked_until && $this->blocked_until->isFuture());
    }

    /**
     * Cek apakah profil sudah lengkap.
     */
    public function isProfileCompleted(): bool
    {
        return $this->profile_completed === true && ! is_null($this->profile_completed_at);
    }

    /**
     * Cek apakah user aktif dan tidak diblokir.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isBlocked();
    }

    /**
     * Cek apakah user boleh login.
     */
    public function canLogin(): bool
    {
        return $this->isActive();
    }

    /**
     * Cek apakah profil perlu dilengkapi.
     */
    public function needsProfileCompletion(): bool
    {
        if ($this->profile_completed === false || $this->profile_completed === null) {
            return true;
        }

        return ! $this->isProfileCompleted();
    }

    /**
     * Tandai profil sudah lengkap.
     */
    public function markProfileCompleted(): void
    {
        $this->update([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        $this->refresh();
    }

    /**
     * Perbarui password dan catat waktu perubahan.
     */
    public function updatePassword(string $password): void
    {
        $this->update([
            'password' => $password,
            'password_changed_at' => now(),
        ]);
    }

    /**
     * Cek apakah user berasal dari SSO.
     */
    public function isSsoUser(): bool
    {
        return ! is_null($this->sso_id);
    }

    /**
     * Ambil display name role.
     */
    public function getRoleDisplayName(): string
    {
        return $this->role?->display_name ?? 'Tidak Ada Role';
    }

    /**
     * Ambil data spesifik user berdasarkan tipe.
     */
    public function getSpecificData(): ?object
    {
        return match ($this->user_type) {
            'mahasiswa' => $this->student,
            'staff' => $this->staffEmployee,
            default => null,
        };
    }

    /**
     * Cek apakah user memiliki data spesifik.
     */
    public function hasSpecificData(): bool
    {
        return $this->getSpecificData() !== null;
    }

    /**
     * Reset percobaan login gagal.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'blocked_reason' => null,
        ]);
    }

    /**
     * Tambah hitungan login berhasil.
     */
    public function incrementLoginCount(): void
    {
        $this->increment('login_count');
    }

    /**
     * Perbarui waktu aktivitas terakhir.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Tampilkan user type dalam bentuk label.
     */
    public function getUserTypeDisplayAttribute(): string
    {
        return match ($this->user_type) {
            'mahasiswa' => 'Mahasiswa',
            'staff' => 'Staff',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Tampilkan status dalam bentuk label.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'blocked' => 'Diblokir',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Cek apakah user bertipe mahasiswa.
     */
    public function isStudent(): bool
    {
        return $this->user_type === 'mahasiswa';
    }

    /**
     * Cek apakah user bertipe staff.
     */
    public function isEmployee(): bool
    {
        return $this->user_type === 'staff';
    }

    /**
     * Cek apakah user boleh dianggap admin.
     */
    public function isAdmin(): bool
    {
        return $this->isEmployee();
    }

    /**
     * Tambah jumlah percobaan login gagal dan kunci akun jika perlu.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(30),
                'blocked_reason' => 'Terlalu banyak percobaan login gagal',
                'status' => 'blocked',
            ]);
        }
    }
}
