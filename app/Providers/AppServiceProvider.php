<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Apartment;
use App\Models\AuthLineOfService;
use App\Models\AuthToReleaseInfo;
use App\Models\Authorization;
use App\Models\Check;
use App\Models\Claim;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Consent;
use App\Models\Dropbox;
use App\Models\GroupNote;
use App\Models\House;
use App\Models\LevelOfCare;
use App\Models\MedicalContact;
use App\Models\PeerGroup;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = AuditObserver::class;

        House::observe($observer);
        Apartment::observe($observer);
        Client::observe($observer);
        ClientGroup::observe($observer);
        PeerGroup::observe($observer);
        MedicalContact::observe($observer);
        AuthToReleaseInfo::observe($observer);
        Authorization::observe($observer);
        AuthLineOfService::observe($observer);
        Consent::observe($observer);
        GroupNote::observe($observer);
        Attendance::observe($observer);
        Dropbox::observe($observer);
        User::observe($observer);
        LevelOfCare::observe($observer);
        Claim::observe($observer);
        Check::observe($observer);
    }
}
