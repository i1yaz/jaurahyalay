<?php

namespace App\Http\Controllers\ClubAdmin;

use App\User;
use App\Models\Admin\Player;
use App\Models\Admin\Tournament;
use App\Services\WebsiteService;
use App\Services\TournamentService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\validateTournament;

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

        $tournaments = Tournament::where('club_id', Auth::user()->club_id)
        ->orderBy('show','desc')->orderBy('sort','asc')
        ->latest()
        ->paginate($records);
        return view('admin.club_admin.tournament.index', compact('tournaments', 'page', 'records'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $players = Player::where('club_id',Auth::user()->club_id)->orWhere('club_id', 0)->get();
        $admins = User::whereNot('id',1)->Where('created_by',Auth::id())->get();
        return view('admin.club_admin.tournament.create', compact('players','admins'));
    }

    public function store(validateTournament $request)
    {
        $request->merge(['club_id' => Auth::user()->club_id]);
        $request->merge(['status' => 'off']);
        $tournament = (new TournamentService())->storeTournament($request);
        (new TournamentService())->storePoster($request, $tournament);
        (new TournamentService())->syncTournamentModerator($request, $tournament);
        $this->websiteService->flushCache();
        return redirect('club/admin/tournament')->with('success', 'Tournament has been added!');
    }

    public function show(Tournament $tournament)
    {
        $tournament = Tournament::with('flyingDays')->where('id', $tournament->id)->first();
        $days = $tournament->flyingDays;
        return view('admin.club_admin.tournament.show', compact('tournament', 'days'));
    }

    public function edit(Tournament $tournament)
    {
        $tournament = Tournament::with('flyingDays')->where('id', $tournament->id)->first();
        $players = Player::where('club_id',Auth::user()->club_id)->orWhere('club_id', 0)->get();
        $days = $tournament->flyingDays;
        $prizes = $tournament->tournamentPrize;
        $admins = User::whereNot('id',1)->Where('created_by',Auth::id())->get();
        $tournamentAdmins = (new TournamentService())->getTournamentAdmins($tournament);
        return view('admin.club_admin.tournament.edit', compact('tournament', 'days', 'players',  'prizes','admins','tournamentAdmins'));
    }

    public function update(validateTournament $request, Tournament $tournament)
    {
        $request->merge(['club_id' => $tournament->club_id]);
        $request->merge(['status' => $tournament->status==1 ? 'on' : 'off']);
        $tournament = (new TournamentService())->updateTournament($request, $tournament);
        (new TournamentService())->storePoster($request, $tournament,'update');
        (new TournamentService())->syncTournamentModerator($request, $tournament);
        $this->websiteService->flushCache();
        return redirect('club/admin/tournament')->with('success', 'Tournament has been updated!');
    }

    public function destroy(Tournament $tournament)
    {
        if ($tournament->club_id === Auth::user()->club_id) {
            $tournament->delete();
            $this->websiteService->flushCache();
            return redirect()->back()->with('success', 'Tournament has been deleted!');
        } else {
            return redirect()->back()->withErrors('Something is wrong!');
        }
    }
}
