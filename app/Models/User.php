<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'email',
        'password',
        'pin',
        'role_id',
        'level_of_care',
        'avatar'
    ];

 public function avatarUrl(): ?string
{
    if (!empty($this->avatar) && Storage::disk('public')->exists($this->avatar)) {
        return Storage::url($this->avatar);
    }
    return null;
}

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin',
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
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function levelOfCare()
    {
        return $this->belongsTo(LevelOfCare::class, 'level_of_care');
    }

    public function hasPermission($permissionName)
    {
        if ($this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists()) {
            return true;
        }

        return $this->role?->permissions?->contains('name', $permissionName) ?? false;
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
