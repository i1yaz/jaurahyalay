<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Tournament;
use Illuminate\Http\Request;
use App\Services\WebsiteService;
use App\Services\TournamentService;
use App\Http\Controllers\Controller;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{

    protected $websiteService;

    public function __construct(WebsiteService $websiteService)
    {
        $this->middleware('auth');
        $this->websiteService = $websiteService;
    }

    public function index()
    {
        $page = request()->query('page');
        $page = ($page === null) ? 1 : $page;
        $records = 20;
        $tournamentModerator =  (new TournamentService())->getTournamentManagingByThisPlayer();
        $tournaments = (new TournamentService())->getActiveTournament($records);
        return view('admin.result.index', compact('tournaments', 'tournamentModerator', 'page', 'records'));
    }

    public function refresh()
    {
        if (Auth::user()->super_admin) {
            $this->websiteService->flushCache();
            return redirect('admin/result')->with('success', 'System refreshed!');
        }
        abort(403);
    }


    public function edit($tournament_id, $date = null)
    {
        $response = (new TournamentService())->canEditThisTournament($tournament_id);
        set_time_limit(300);
        if ($response) {
            $tournament = Tournament::find($tournament_id);
            $date = ($date) ? $date : $this->getEditDefaultDate($tournament);
            $tournamentResult = (new TournamentService())->getActiveTournamentForResult($tournament->id, $date);
            $updateDate = (isset($date)) ? $date : $tournament->start_date;
            return view('admin.result.edit', compact('tournament', 'updateDate', 'tournamentResult'));
        }
        return redirect()->back()->withErrors('Sorry You don\'t have permission!');
    }

    public function time(Request $request)
    {
        $addedBy = Auth::id();
        $response = (new ResultService())->canEditThisResult($request);
        if ($response) {

            $result = (new ResultService())->updatePlayerTime($request);
            $result = str_replace(':', '', $result);
            //Split string into an array.  Each element is 2 chars
            $chunks = str_split($result, 2);
            //Convert array to string.  Each element separated by the given separator.
            $result = implode(':', $chunks);
            // try {
            //     $data = explode('_', $request->pk);
            //     $this->websiteService->flushCache($data[0],$data[1],end($data));
            //     $time = $request->value;
            // }catch (\Exception $e){
            //     return response()->json($e->getMessage());
            // }
            return response()->json($result);
        }
        return response()->json('Sorry You don\'t have permission!');
    }
    public function updateResult(Request $request)
    {
        
        $response = (new ResultService())->canEditThisResult($request);
 
        if ($response) {
            $date = $request->value;
            
            (new ResultService())->bulkRecalculateForTournament(
                $request->tournament_id,
                $date,
                $request->club_id
            );

            return redirect()->back()->with('success', 'Time has been updated!');
        }
        return redirect()->back()->withErrors('You dont\'t have Permissions!');
    }

    private function getEditDefaultDate($tournament)
    {
        $flyingDays = $tournament->flyingDays()->pluck('date')->sort()->values()->toArray();
        $now = date("Y-m-d");
        if (in_array($now, $flyingDays, true)) {
            return $now;
        }

        $currentDate = strtotime($now);
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

        if ($nextDate === null) {
            return end($flyingDays) ?: $tournament->start_date;
        }
        if ($prevDate === null) {
            return $tournament->start_date;
        }
        return date("Y-m-d", $prevDate);
    }
}
