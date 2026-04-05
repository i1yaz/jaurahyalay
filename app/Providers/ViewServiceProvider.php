<?php

namespace App\Providers;

use App\Services\WebsiteService;
use Carbon\Carbon;
use Dykyi\DBFactory;
use Dykyi\Driver\RedisDB;
use Dykyi\VisitorsCounter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        View::composer('*', function ($view)  {
            $result['activeClubs'] = [];
            $result['activeTournaments'] = [];
            $result['activeNews'] = [];
            $result['sliders'] = [];
            $result['activeNavbarTournaments'] = [];
            
            $result = Cache::store('remember_forever_cache_store')->remember('shared-data', now()->addMinutes(1440), function () {
                $data = [];
                $data['activeClubs'] = (new WebsiteService())->getAllActiveClubs();
                $data['activeNews'] = (new WebsiteService())->getActiveNews();
                $data['activeTournaments'] = (new WebsiteService())->getActiveTournamentForWebsite();
                $data['activeNavbarTournaments'] = (new WebsiteService())->getActiveTournamentForWebsite(true);
                $data['sliders'] = (new WebsiteService())->getAllSliders();
                return $data;
            });
            $view->with([
                'activeClubs' => $result['activeClubs'],
                'activeTournaments' => $result['activeTournaments'],
                'activeNavbarTournaments' => $result['activeNavbarTournaments'],
                'activeNews' => $result['activeNews'],
                'sliders' => $result['sliders'],
            ]);
        });
    }

}
