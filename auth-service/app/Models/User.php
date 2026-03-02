<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * All available permissions (business user level).
     */
    public const PERMISSIONS = [
        'view_transactions',
        'create_settlement',
        'view_settlements',
        'wallet_transfer',
        'create_payout',
        'approve_payout',
        'add_user',
        'view_users',
        'view_account_info',
        'view_settings',
    ];

    /**
     * All available admin-level permissions (admin dashboard modules).
     */
    public const ADMIN_PERMISSIONS = [
        'admin_overview'      => 'Overview & Stats',
        'admin_accounts'      => 'Accounts & KYC',
        'admin_transactions'  => 'View Transactions',
        'admin_wallets'       => 'View Wallets',
        'admin_settlements'   => 'Approve Settlements',
        'admin_charges'       => 'Manage Charges',
        'admin_ip_whitelist'  => 'IP Whitelist',
        'admin_transfers'     => 'Approve Transfers',
        'admin_users'         => 'Users & Reset Password',
        'admin_reversals'     => 'Reversals',
        'admin_operators'     => 'Operators & API',
        'admin_payments'      => 'Payment Requests',
    ];

    protected $fillable = [
        'firstname',
        'lastname',
        'name',
        'email',
        'password',
        'account_id',
        'role',
        'permissions',
        'is_banned',
        'banned_at',
        'ban_reason',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    /**
     * Check if user has a specific permission.
     * Owner always has all permissions.
     * Super admin always has all permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner' || $this->role === 'super_admin') {
            return true;
        }
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if user has a specific admin permission.
     * Super admin always has all admin permissions.
     * admin_user checks permissions array.
     */
    public function hasAdminPermission(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }
        if ($this->role === 'admin_user') {
            return in_array($permission, $this->permissions ?? []);
        }
        return false;
    }

    /**
     * Get the user's effective permissions.
     */
    public function getEffectivePermissions(): array
    {
        if ($this->role === 'owner' || $this->role === 'super_admin') {
            return self::PERMISSIONS;
        }
        return $this->permissions ?? [];
    }

    /**
     * Get the user's effective admin permissions.
     */
    public function getEffectiveAdminPermissions(): array
    {
        if ($this->role === 'super_admin') {
            return array_keys(self::ADMIN_PERMISSIONS);
        }
        if ($this->role === 'admin_user') {
            return $this->permissions ?? [];
        }
        return [];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user can access the admin dashboard.
     */
    public function isAdminLevel(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_user']);
    }
}
