<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Models\Agence\Agence;
use App\Models\Property\Propertie;
use App\Models\Property\Save;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'name',
        'surname',
        'phone',
        'role',
        'status',
        'newsletter',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $permission_agent = [
        'can add property',
        'can edit property',
        'can delete property',
        'can edit agent',
        'can delete agent',
    ];
    protected $permission_admin = [
        'can add property',
        'can edit property',
        'can deleted property',
        'can add admin',
        'can add agent',
        'can add client',
        'can edit agent',
        'can edit client',
        'can edit admin',
        'can delete agent',
        'can delete client',
        'can delete admin',
    ];
    protected $permission_client = [
        'can edit client',
        'can delete client',
    ];

    function hasRole($role)
    {
        return $this->role == $role;
    }

    protected function role()
    {
        return $this->role;
    }

    function hasAgentPermissions($permission)
    {
        if ($this->role == 'agent') {
            foreach ($this->permission_agent as $key => $value) {
                if ($value == $permission) return true;
            }
            return false;
        }
        return false;
    }

    function hasAdminPermissions($permission)
    {
        if ($this->role == 'admin') {
            foreach ($this->permission_admin as $key => $value) {
                if ($value == $permission) return true;
            }
            return false;
        }
        return false;
    }

    function hasClientPermissions($permission)
    {
        if ($this->role == 'client') {
            foreach ($this->permission_client as $key => $value) {
                if ($value == $permission) return true;
            }
            return false;
        }
        return false;
    }
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function agence()
    {
        return $this->hasOne(Agence::class);
    }

    public function properties_saved()
    {
        return $this->belongsToMany(Propertie::class, 'saves', 'user_id', 'property_id');
    }

    public function has_saved($property_id)
    {
        try {
            $save = Save::where(['user_id' => $this->id, 'property_id' => $property_id])->firstOrfail();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
