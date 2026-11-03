<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username', 'password', 'name', 'role', 'divisi', 'nip', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = [
        'normalized_role',
        'is_admin_warehouse',
        'is_auditor',
        'is_requester_division',
        'can_manage_warehouse',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function borrowingLogs(): HasMany
    {
        return $this->hasMany(BorrowingLog::class, 'handled_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Normalisasi role menjadi 3 standar RBAC PT Besmindo Materi Sewatama:
     * - 'admin_warehouse'
     * - 'requester_division'
     * - 'auditor'
     */
    public function getNormalizedRoleAttribute(): string
    {
        $raw = strtolower(str_replace([' ', '-'], '_', $this->role ?? ''));

        if (str_contains($raw, 'admin') || str_contains($raw, 'staff') || str_contains($raw, 'petugas') || $raw === 'admin_warehouse') {
            return 'admin_warehouse';
        }

        if (str_contains($raw, 'audit') || $raw === 'auditor') {
            return 'auditor';
        }

        if (str_contains($raw, 'request') || str_contains($raw, 'divisi') || str_contains($raw, 'pemohon') || $raw === 'requester_division') {
            return 'requester_division';
        }

        return 'requester_division';
    }

    public function getIsAdminWarehouseAttribute(): bool
    {
        return $this->normalized_role === 'admin_warehouse';
    }

    public function getIsAuditorAttribute(): bool
    {
        return $this->normalized_role === 'auditor';
    }

    public function getIsRequesterDivisionAttribute(): bool
    {
        return $this->normalized_role === 'requester_division';
    }

    public function getCanManageWarehouseAttribute(): bool
    {
        return $this->is_admin_warehouse;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin_warehouse;
    }

    public function isStaff(): bool
    {
        return $this->is_admin_warehouse;
    }

    public function isAuditor(): bool
    {
        return $this->is_auditor;
    }

    public function isRequesterDivision(): bool
    {
        return $this->is_requester_division;
    }

    public function canManageWarehouse(): bool
    {
        return $this->is_admin_warehouse;
    }

    public function canVerifyPhysical(): bool
    {
        return $this->is_admin_warehouse;
    }

    public function canMutate(): bool
    {
        return !$this->is_auditor;
    }

    /**
     * Memeriksa apakah user berhak mengedit dokumen PB tertentu:
     * - Admin Warehouse: Boleh mengedit semua dokumen
     * - Auditor: Tidak boleh mengedit dokumen apapun (Read-Only)
     * - Requester Division: Hanya boleh mengedit dokumen divisinya sendiri / dokumen yang dia buat
     */
    public function canEditDocument(Document $doc): bool
    {
        if ($this->is_auditor) {
            return false;
        }

        if ($this->is_admin_warehouse) {
            return true;
        }

        if ($this->is_requester_division) {
            // Jika dokumen dibuat oleh user ini
            if ($doc->user_id && $doc->user_id === $this->id) {
                return true;
            }

            // Jika divisi user cocok dengan bagian dokumen
            if ($this->divisi) {
                $userDiv = strtolower(trim($this->divisi));
                $docBagian = strtolower(trim($doc->bagian ?? ''));
                if ($userDiv === $docBagian || str_contains($docBagian, $userDiv) || str_contains($userDiv, $docBagian)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
