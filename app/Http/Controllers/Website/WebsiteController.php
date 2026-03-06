<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Admin\Club;
use App\Models\Admin\Tournament;
use Facades\App\Services\WebsiteService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View as FacadeView;

class WebsiteController extends Controller
{
    public function index()
    {
        $title = 'Home';
        $firstActiveTournament = Cache::remember('firstActiveTournament', now()->addMinutes(60), function () {
            return WebsiteService::getFirstActiveTournamentForIndex();

        });

        $resultDate = $this->isTodayFlyingDay($firstActiveTournament);
        if ($firstActiveTournament != null && $resultDate != 'total') {
            $tournament = WebsiteService::getTournamentResultByDateForIndex($firstActiveTournament, $resultDate);
            $sortedResultAndPlayers = WebsiteService::getSortedResultByDate($firstActiveTournament, $resultDate);
            $players = $tournament->tournamentResult->groupBy('player_id');
            $IndexView = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        } elseif ($firstActiveTournament != null && $resultDate == 'total') {
            $sortedResultAndPlayers = WebsiteService::getTournamentTotal($firstActiveTournament);
            $players = WebsiteService::getTournamentTotalByDays($firstActiveTournament);
            $tournament = $firstActiveTournament;
            $IndexView = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        }

        return $IndexView;
    }

    public function clubResult(Club $club)
    {
        $page = request()->page;
        if (empty($page)) {
            $page = 1;
        }
        $title = $club->name;
        $tournaments = WebsiteService::getAllTournamentsOfThisClub($club);
        $tournamentsPositions = WebsiteService::getAllClubTournamentsWithPrizes($tournaments);
        $clubResultIndexView = (string) FacadeView::make('website.club.index', compact('club', 'tournaments', 'tournamentsPositions', 'title'));

        return $clubResultIndexView;
    }

    public function tournamentDateResult($club, $tournament, $date)
    {
        $tournament = Cache::store('remember_forever_cache_store')->remember('tournament-date-result-'.$tournament, now()->addMinutes(1440), function () use ($tournament) {
            return Tournament::where('id', $tournament)->first();
        });

        if ($tournament->club_id != $club && $club != 'default') {
            abort(404);
        }
        $title = $tournament->name;
        if ($club == 'default') {
            if ($date !== 'total') {
                set_time_limit(300);
                $resultDate = $date;
                $tournament = WebsiteService::getTournamentResultByDateForIndex($tournament, $resultDate);
                $sortedResultAndPlayers = WebsiteService::getSortedResultByDate($tournament, $resultDate);
                $players = $tournament->tournamentResult->groupBy('player_id');
                $total = (string) FacadeView::make('website.club.result', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
            } else {
                set_time_limit(300);
                $sortedResultAndPlayers = WebsiteService::getTournamentTotal($tournament);
                $players = WebsiteService::getTournamentTotalByDays($tournament);
                $resultDate = $date;
                $total = (string) FacadeView::make('website.club.result', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));

            }

            return $total;
        }

        if ($date !== 'total') {
            set_time_limit(300);
            $resultDate = $date;
            $tournament = WebsiteService::getTournamentResultByDateForIndex($tournament, $resultDate);
            $sortedResultAndPlayers = WebsiteService::getSortedResultByDate($tournament, $resultDate);
            $players = $tournament->tournamentResult->groupBy('player_id');
            $defaultTotal = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        } else {
            set_time_limit(300);
            $sortedResultAndPlayers = WebsiteService::getTournamentTotal($tournament);
            $players = WebsiteService::getTournamentTotalByDays($tournament);
            $resultDate = $date;
            $defaultTotal = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        }

        return $defaultTotal;
    }

    public function loadTournament($club_id, $tournament_id)
    {
        $tournament = Cache::store('remember_forever_cache_store')->remember('tournament-date-result-'.$tournament_id, now()->addMinutes(1440), function () use ($tournament_id) {
            return Tournament::where('id', $tournament_id)->first();
        });

        $resultDate = $this->isTodayFlyingDay($tournament);

        return $this->tournamentDateResult($club_id, $tournament->id, $resultDate);
    }

    public function weather()
    {
        $title = 'Weather';

        return view('website.weather', compact('title'));
    }

    public function contact()
    {
        $title = 'Contact Us';

        return view('website.contact', compact('title'));
    }

    public function isTodayFlyingDay($tournament)
    {
        $flyingDays = $tournament?->flyingDays();

        if (! $flyingDays) {

            return date('Y-m-d');
        }
        $now = date('Y-m-d');
        $flyingDays = $flyingDays->pluck('date')->ToArray();
        $match = in_array($now, $flyingDays);
        if ($match) {
            return $now;
        }
        $currentDate = strtotime(date('Y-m-d'));
        $prevDate = null;
        $nextDate = null;
        foreach ($flyingDays as $date) {
            $date = strtotime($date);
            if ($date < $currentDate) {
                $prevDate = $date;
            }
            if ($date > $currentDate) {
                $nextDate = $date;
                break;
            }
        }
        if ($nextDate == null) {
            return 'total';
        } elseif ($prevDate == null) {
            return $tournament->start_date;
        }

        return date('Y-m-d', $prevDate);
    }

    public function refresh()
    {
        opcache_reset();
        dd(WebsiteService::flushCache(), 'System has been updated');
    }

    /**
     * @throws GuzzleException
     */
    public function checkHeartbeat()
    {
        $client = new Client;
        $client->post('https://glitchtip.i1yas.top/api/0/organizations/my-organization/heartbeat_check/5fbdece5-0f1b-499d-ba51-2a058b3f91d6/');
    }
}
