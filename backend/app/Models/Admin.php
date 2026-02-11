<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function getPermissionGroups(){
        $permission_group = Permission::select('group_name as name')->groupBy('group_name')->where('guard_name','admin')->get();
        return $permission_group;
    }
    public static function getPermissionsByGroupName($name){
        $permissions = Permission::where('group_name',$name)->where('guard_name','admin')->get();
        return $permissions;
    }

    public static function roleHasPermissions($role ,$permissions){
        $hasPermission = true;
        foreach($permissions as $permission){
            if(!$role->hasPermissionTo($permission->name)){
                $hasPermission=false;
                return $hasPermission;
            }
        }
        return $hasPermission;
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'admin_id');
    }

    public function complains()
    {
        return $this->hasMany(Complain::class, 'admin_id');
    }

    /**
     * Whether this admin should see the full admin menu (categories, banners, users, vendors, settings, etc.).
     * Checks both capitalised (DB originals) and lowercase role names for safety.
     */
    public function isFullAdmin(): bool
    {
        if (!$this->exists) {
            return false;
        }
        // Match both DB originals (Superadmin, Manager) and lowercase variants (superadmin, admin, manager)
        $fullRoles = ['Superadmin', 'superadmin', 'admin', 'Admin', 'Manager', 'manager'];
        foreach ($fullRoles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        // Legacy: no role assigned => treat as full admin (e.g. seeded super admin)
        return $this->roles()->count() === 0;
    }

    /**
     * Whether this admin is a shop (store) admin with limited menu (Accounts, etc.).
     */
    public function isShopAdmin(): bool
    {
        return $this->exists && $this->hasRole('Shop');
    }
}