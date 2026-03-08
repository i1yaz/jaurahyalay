<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Tournament;
use App\Services\WebsiteService;
use App\Services\TournamentService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\validateTournament;
use Illuminate\Support\Facades\Auth;

class TournamentController extends Controller
{
    // Middleware for Admin
    protected $websiteService;

    public function __construct(WebsiteService $websiteService)
    {
        $this->middleware('auth');
        $this->websiteService = $websiteService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page = request()->query('page');
        $page = ($page === null) ? 1 : $page;
        $records = 20;

        $tournaments = Tournament::orderBy('show','desc')->orderBy('sort','asc')->latest()->paginate($records);
        return view('admin.tournament.index', compact('tournaments', 'page', 'records'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $players = (new TournamentService())->getAllPlayers();
        $clubs = (new TournamentService())->getAllClubs();
        $admins = (new TournamentService())->getAdmins();
        return view('admin.tournament.create', compact('players', 'clubs','admins'));
    }

    public function store(validateTournament $request)
    {
        $tournament = (new TournamentService())->storeTournament($request);
        (new TournamentService())->storePoster($request, $tournament);
        (new TournamentService())->syncTournamentModerator($request, $tournament);
        $this->websiteService->flushCache();
        return redirect('admin/tournament')->with('success', 'Tournament has been added!');
    }

    public function show(Tournament $tournament)
    {
        $tournament = Tournament::with('flyingDays')->where('id', $tournament->id)->first();
        $days = $tournament->flyingDays;
        return view('admin.tournament.show', compact('tournament', 'days'));
    }

    public function edit(Tournament $tournament)
    {
        $tournament = Tournament::with('flyingDays')->where('id', $tournament->id)->first();
        $players = (new TournamentService())->getAllPlayers();
        $clubs = (new TournamentService())->getAllClubs();
        $days = $tournament->flyingDays;
        $prizes = $tournament->tournamentPrize;
        $admins = (new TournamentService())->getAdmins();
        $tournamentAdmins = (new TournamentService())->getTournamentAdmins($tournament);
        return view('admin.tournament.edit', compact('tournament', 'days', 'players', 'clubs', 'prizes','admins','tournamentAdmins'));
    }

    public function update(validateTournament $request, Tournament $tournament)
    {
        $tournament = (new TournamentService())->updateTournament($request, $tournament);
        (new TournamentService())->storePoster($request, $tournament,'update');
        (new TournamentService())->syncTournamentModerator($request, $tournament);
        $this->websiteService->flushCache();
        return redirect('admin/tournament')->with('success', 'Tournament has been updated!');
    }

    public function destroy(Tournament $tournament)
    {
        if ($tournament->delete()) {
            $this->websiteService->flushCache();
            return redirect()->back()->with('success', 'Tournament has been deleted!');
        } else {
            return redirect()->back()->withErrors('Something is wrong!');
        }
    }

    public function activate(Tournament $tournament){
        if(Auth::user()->super_admin !== 1){
            return redirect()->back()->withErrors('You are not authorized to change tournament status!');
        }
        $tournament->status = !$tournament->status;
        if ($tournament->save()) {
            $this->websiteService->flushCache();
            return redirect()->back()->with('success', 'Tournament status has been updated!');
        } else {
            return redirect()->back()->withErrors('Something is wrong!');
        }
    }
}
