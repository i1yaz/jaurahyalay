<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Admin\Club;
use App\Models\Admin\Tournament;
use App\Services\WebsiteService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\View as FacadeView;

class WebsiteController extends Controller
{
    protected $websiteService;

    public function __construct(WebsiteService $websiteService)
    {
        $this->websiteService = $websiteService;
    }

    public function index()
    {
        $title = 'Home';
        $firstActiveTournament = $this->websiteService->getFirstActiveTournamentForIndex();

        $resultDate = $this->isTodayFlyingDay($firstActiveTournament);
        if ($firstActiveTournament != null && $resultDate != 'total') {
            $tournament = $this->websiteService->getTournamentResultByDateForIndex($firstActiveTournament, $resultDate);
            $sortedResultAndPlayers = $this->websiteService->getSortedResultByDate($firstActiveTournament, $resultDate);
            $players = $tournament->tournamentResult->groupBy('player_id');
            $IndexView = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        } elseif ($firstActiveTournament != null && $resultDate == 'total') {
            $sortedResultAndPlayers = $this->websiteService->getTournamentTotal($firstActiveTournament);
            $players = $this->websiteService->getTournamentTotalByDays($firstActiveTournament);
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
        $tournaments = $this->websiteService->getAllTournamentsOfThisClub($club);
        $tournamentsPositions = $this->websiteService->getAllClubTournamentsWithPrizes($tournaments);
        $clubResultIndexView = (string) FacadeView::make('website.club.index', compact('club', 'tournaments', 'tournamentsPositions', 'title'));

        return $clubResultIndexView;
    }

    public function tournamentDateResult($tournament, $date)
    {
        $tournament = Tournament::where('id', $tournament)->first();
        if (!$tournament) {
            abort(404);
        }

        $title = $tournament->name;

        if (!in_array($date, ['total', 'double-stamp-total'])) {
            set_time_limit(300);
            $resultDate = $date;
            $tournament = $this->websiteService->getTournamentResultByDateForIndex($tournament, $resultDate);
            $sortedResultAndPlayers = $this->websiteService->getSortedResultByDate($tournament, $resultDate);
            $players = $tournament->tournamentResult->groupBy('player_id');
            $view = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        } elseif ($date === 'double-stamp-total') {
            if (!$tournament->allow_double_stamp) {
                abort(404);
            }
            set_time_limit(300);
            $sortedResultAndPlayers = $this->websiteService->getTournamentDoubleStampTotal($tournament);
            $players = $this->websiteService->getTournamentDoubleStampTotalByDays($tournament);
            $resultDate = $date;
            $view = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        } else {
            set_time_limit(300);
            $sortedResultAndPlayers = $this->websiteService->getTournamentTotal($tournament);
            $players = $this->websiteService->getTournamentTotalByDays($tournament);
            $resultDate = $date;
            $view = (string) FacadeView::make('website.index', compact('tournament', 'resultDate', 'players', 'sortedResultAndPlayers', 'title'));
        }

        return $view;
    }

    public function loadTournament($tournament_id)
    {
        $tournament = Tournament::where('id', $tournament_id)->first();
        if (!$tournament) {
            abort(404);
        }

        $resultDate = $this->isTodayFlyingDay($tournament);

        return $this->tournamentDateResult($tournament->id, $resultDate);
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
        $this->websiteService->flushCache();
        dd('System has been updated');
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
