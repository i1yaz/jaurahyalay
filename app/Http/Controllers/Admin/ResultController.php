<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Result;
use Illuminate\Http\Request;
use App\Models\Admin\Tournament;
use App\Services\WebsiteService;
use Illuminate\Support\Facades\DB;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ResultController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
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
            WebsiteService::flushCache();
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
            try {
                $data = explode('_', $request->pk);
                $tournament = Tournament::find($data[0]);
                $club_id = $tournament ? $tournament->club_id : null;
                $headers = WebsiteService::flushCache($data[0], $data[1], $club_id);
                $time = $request->value;
            }catch (\Exception $e){
                return response()->json($e->getMessage());
            }
            return response()->json($result)->withHeaders($headers);
        }
        return response()->json('Sorry You don\'t have permission!');
    }
    public function updateResult(Request $request)
    {

        $response = (new ResultService())->canEditThisResult($request);
 
        if ($response) {

            //0 => tournament_id, 1 => date, 2 => player_id, 3 => pigeon_no, 4 => club_id
            $players = DB::table('player_tournament')->select(['player_id'])->where('tournament_id', $request->tournament_id)->get()->toArray();
            if (empty($players)) {
                return redirect()->back()->withErrors('No player found!');
            }
            $date = $request->value;
            foreach ($players as $player) {
                $data[0] = $request->tournament_id;
                $data[1] = $date;
                $data[2] = $player->player_id;
                $request->merge(['pk' => $request->tournament_id . '_' . $date . '_' . $player->player_id]) ;

                $result = Result::select(['start_time'])->where('date', $date)->where('player_id', $player->player_id)->where('pigeon_number', 1)->first();
                $requestData = [
                    'name' => $request->name,
                    'value' => $result->start_time
                ];
                $request->merge(['value' => $result->start_time]);

                $result = (new ResultService())->updateStartTime($requestData,$data);
            }
            $headers = WebsiteService::flushCache($request->tournament_id,$date,$request->club_id);
            return redirect()->back()->with('success', 'Time has been updated!')->withHeaders($headers);
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
