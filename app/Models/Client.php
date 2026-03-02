<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Apartment;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'mrn',
        'carelon_id',
        'medicaid_id',
        'starting_date',
        'reactivation_date',
        'discharge_date',
        'redetermination_date',
        'redetermination_remarks',
        'eligibility',
        'redetermination_last_checked',
        'guest',
        // 'levelOfCare',
        'ssn',
        'email',
        'phone',
        'status',
        'address',
        'notes',
        'evs_data',
        'consent',
        'profile_photo',
        'dropbox_id',
    ];

    // Automatically generate sequential MRN if it's not set
    protected static function booted()
    {
        static::creating(function ($client) {
            if (! $client->mrn) {
                $client->mrn = self::generateSequentialMRN();
            }
        });
    }

    public static function generateSequentialMRN()
    {
        // Get the maximum MRN in the database
        $lastClient = self::latest('mrn')->first();

        return $lastClient ? $lastClient->mrn + 1 : 1; // Start MRN from 1 if none exists
    }

    // public function programs()
    // {
    //     return $this->belongsToMany(Program::class, 'client_program')
    //                 ->withPivot('start_date', 'end_date') // Include additional fields
    //                 ->withTimestamps();
    // }

    public function apartmentHistory()
    {
        return $this->hasMany(ClientApartment::class)->orderBy('start_date', 'asc');
    }

    public function apartment()
    {
        return $this->hasOneThrough(Apartment::class, ClientApartment::class, 'client_id', 'id', 'id', 'apartment_id')
            ->whereDate('client_apartments.start_date', '<=', now()->toDateString())
            ->whereNull('client_apartments.end_date');
    }

    // Access house via apartment relationship
    public function getHouseAttribute()
    {
        return $this->apartment ? $this->apartment->house : null;
    }

    public function consents()
    {
        return $this->hasMany(Consent::class);
    }

    public function authorizations()
    {
        return $this->hasMany(Authorization::class);
    }

    public function levelOfCareHistory()
    {
        return $this->hasMany(ClientLevelOfCares::class)->orderBy('start_date', 'asc');
    }

    public function hospitalizations()
    {
        return $this->hasMany(ClientHospitalization::class);
    }

    public function currentHospitalization()
    {
        $today = now()->toDateString();

        return $this->hospitalizations()
            ->where('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->first();
    }

    public function isHospitalizedOn($date)
    {
        return $this->hospitalizations()
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->exists();
    }

    public function getLevelOfCareOnDate($date)
    {
        if ($this->isHospitalizedOn($date)) {
            return 'Hospitalized';
        }

        $levelOfCareHistory = $this->levelOfCareHistory()
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();

        return $levelOfCareHistory?->levelOfCare?->level_of_care;
    }

    public function getLevelOfCareOnDateWithoutHospitalization($date)
    {
        $levelOfCareHistory = $this->levelOfCareHistory()
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();

        return $levelOfCareHistory?->levelOfCare?->level_of_care;
    }

    public function getLatestLevelOfCare()
    {
        $levelOfCareHistory = $this->levelOfCareHistory()->orderBy('start_date', 'desc')->first();

        return $levelOfCareHistory?->levelOfCare?->level_of_care;
    }

    public function hasPendingAuthorizationOnOrAfter(?string $date): bool
    {
        if (! $date) {
            return $this->authorizations()->whereNull('auth_ending_date')->exists();
        }

        return $this->authorizations()
            ->where(function ($query) use ($date) {
                $query->whereNull('auth_ending_date')
                    ->orWhereDate('auth_ending_date', '>=', $date);
            })
            ->exists();
    }

    public function getCurrentLevelOfCare()
    {
        return $this->getLevelOfCareOnDateWithoutHospitalization(now()->toDateString());
    }

    public function authToReleaseInfos()
    {
        return $this->hasMany(AuthToReleaseInfo::class);
    }

    public function counselorHistory()
    {
        return $this->hasMany(ClientCounselor::class)->orderBy('start_date', 'asc');
    }

    public function counselor()
    {
        return $this->hasOneThrough(User::class, ClientCounselor::class, 'client_id', 'id', 'id', 'counselor_id')
            ->whereDate('client_counselors.start_date', '<=', now()->toDateString())
            ->whereNull('client_counselors.end_date');
    }

    public function peerHistory()
    {
        return $this->hasMany(ClientPeer::class)->orderBy('start_date', 'asc');
    }

    public function peer()
    {
        return $this->hasOneThrough(User::class, ClientPeer::class, 'client_id', 'id', 'id', 'peer_id')
            ->whereDate('client_peers.start_date', '<=', now()->toDateString())
            ->whereNull('client_peers.end_date');
    }

    public function peerGroupHistory()
    {
        return $this->hasMany(ClientPeerGroup::class)->orderBy('start_date', 'asc');
    }

    public function peerGroup()
    {
        return $this->hasOneThrough(PeerGroup::class, ClientPeerGroup::class, 'client_id', 'id', 'id', 'peer_group_id')
            ->whereDate('client_peer_groups.start_date', '<=', now()->toDateString())
            ->whereNull('client_peer_groups.end_date');
    }

    public function clientGroupHistory()
    {
        return $this->hasMany(ClientClientGroup::class)->orderBy('start_date', 'asc');
    }



    public function getCounselorOnDate(string $date): ?User
    {
        return $this->counselorHistory()
            ->with('assignment')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first()?->assignment;
    }

    public function getPeerOnDate(string $date): ?User
    {
        return $this->peerHistory()
            ->with('assignment')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first()?->assignment;
    }

    public function getPeerGroupOnDate(string $date): ?PeerGroup
    {
        return $this->peerGroupHistory()
            ->with('assignment')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first()?->assignment;
    }

    public function getApartmentOnDate(string $date): ?Apartment
    {
        return $this->apartmentHistory()
            ->with('assignment.house')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first()?->assignment;
    }

    public function getClientGroupOnDate(string $date): ?ClientGroup
    {
        $history = $this->clientGroupHistory()
            ->with('assignment')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();

        return $history?->assignment;
    }


    public function getDisplayCounselor(string $date): ?User
    {
        return $this->getCounselorOnDate($date)
            ?? $this->counselorHistory()->with('assignment')->whereNull('end_date')->orderByDesc('start_date')->first()?->assignment
            ?? $this->counselorHistory()->with('assignment')->orderByDesc('start_date')->first()?->assignment;
    }

    public function getDisplayPeer(string $date): ?User
    {
        return $this->getPeerOnDate($date)
            ?? $this->peerHistory()->with('assignment')->whereNull('end_date')->orderByDesc('start_date')->first()?->assignment
            ?? $this->peerHistory()->with('assignment')->orderByDesc('start_date')->first()?->assignment;
    }

    public function getDisplayPeerGroup(string $date): ?PeerGroup
    {
        return $this->getPeerGroupOnDate($date)
            ?? $this->peerGroupHistory()->with('assignment')->whereNull('end_date')->orderByDesc('start_date')->first()?->assignment
            ?? $this->peerGroupHistory()->with('assignment')->orderByDesc('start_date')->first()?->assignment;
    }

    public function getDisplayApartment(string $date): ?Apartment
    {
        return $this->getApartmentOnDate($date)
            ?? $this->apartmentHistory()->with('assignment.house')->whereNull('end_date')->orderByDesc('start_date')->first()?->assignment
            ?? $this->apartmentHistory()->with('assignment.house')->orderByDesc('start_date')->first()?->assignment;
    }

    public function getDisplayClientGroup(string $date): ?ClientGroup
    {
        return $this->getClientGroupOnDate($date)
            ?? $this->clientGroupHistory()->with('assignment')->whereNull('end_date')->orderByDesc('start_date')->first()?->assignment
            ?? $this->clientGroupHistory()->with('assignment')->orderByDesc('start_date')->first()?->assignment;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active'); // Adjust based on your status field
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function dropbox(): BelongsTo
    {
        return $this->belongsTo(Dropbox::class);
    }
}
