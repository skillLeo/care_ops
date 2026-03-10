<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'short_name',
        'email',
        'password',
        'pin',
        'role_id',
        'level_of_care',
        'avatar',
        'entra_oid',   // ✅ ye missing tha — isliye roles link nahi ho raha tha
    ];

    // ✅ Har baar user load ho, roles + permissions bhi saath load hon
    protected $with = ['roles.permissions', 'role.permissions'];

    public function avatarUrl(): ?string
    {
        if (!empty($this->avatar) && Storage::disk('public')->exists($this->avatar)) {
            return Storage::url($this->avatar);
        }
        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);  // ye hona chahiye
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care');
    }

    /**
     * ✅ Fixed: ab cached relations use karta hai, baar baar DB query nahi
     */
    public function hasPermission($permissionName): bool
    {
        // Many-to-many roles se check
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        // role_id (single role) se check — ye main source hai
        if ($this->role_id && $this->role instanceof \App\Models\Role) {
            return $this->role->permissions->contains('name', $permissionName);
        }

        return false;
    }

    public function groupNotes()
    {
        return $this->hasMany(GroupNote::class, 'counselor_id');
    }

    public function counselorAssignments()
    {
        return $this->hasMany(ClientCounselor::class, 'counselor_id');
    }

    public function peerAssignments()
    {
        return $this->hasMany(ClientPeer::class, 'peer_id');
    }

    public function clients()
    {
        return $this->hasManyThrough(Client::class, ClientCounselor::class, 'counselor_id', 'id', 'id', 'client_id')
            ->whereNull('client_counselors.end_date')
            ->where(function ($query) {
                $query->whereNull('discharge_date')
                    ->orWhereDate('discharge_date', '>=', Carbon::today());
            });
    }

    public function peerClients()
    {
        return $this->hasManyThrough(Client::class, ClientPeer::class, 'peer_id', 'id', 'id', 'client_id')
            ->whereNull('client_peers.end_date')
            ->where(function ($query) {
                $query->whereNull('discharge_date')
                    ->orWhereDate('discharge_date', '>=', Carbon::today());
            });
    }

    public function canDeleteRecords(): bool
    {
        $allowedEmails = config('pin.allowed_emails', []);
        return in_array($this->email, $allowedEmails, true);
    }

    public function bookingSlots()
    {
        return $this->hasMany(BookingSlot::class, 'booked_by');
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_user');
    }
}