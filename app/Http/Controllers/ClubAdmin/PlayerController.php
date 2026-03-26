<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Models\Admin\Club;
use App\Models\Admin\Player;
use Illuminate\Http\Request;
use App\Services\WebsiteService;
use App\Services\TournamentService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    // Middleware for Admin
    protected $websiteService;

    public function __construct(WebsiteService $websiteService)
    {
        $this->middleware('auth');
        $this->websiteService = $websiteService;
    }

    public function index()
    {
        return view('admin.club_admin.player.index');
    }

    public function create()
    {
        return view('admin.club_admin.player.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);
        $request->merge(['club' => Auth::user()->club_id]);
        $player = (new TournamentService())->storePlayer($request);
         (new TournamentService())->storePlayerPicture($request, $player);
         $this->websiteService->flushCache();
        if ($player) {
            return redirect('club/admin/player/create')->with('success', 'Player has been added!');
        } else {
            return redirect()->back()->withErrors('Something is wrong!');
        }
    }

    public function edit(Player $player)
    {
        return view('admin.club_admin.player.edit', compact('player'));
    }

    public function update(Request $request, Player $player)
    {

        $this->validate($request, [
            'name' => 'required'
        ]);
        $request->merge(['club' => Auth::user()->club_id]);
        $player = (new TournamentService())->updatePlayer($request, $player);
        (new TournamentService())->storePlayerPicture($request, $player,'update');
        $this->websiteService->flushCache();
        return redirect('club/admin/player')->with('success', 'Player has been updated!');
    }

    public function destroy(Player $player)
    { 
        if ($player->delete()) {
            $prefix = getStoragePrefix();
            Storage::disk('r2')->delete("$prefix/website/profiles/" . $player->poster);
            return redirect()->back()->with('success', 'Player has been deleted!');
        } else {
            return redirect()->back()->withErrors('Something is wrong!');
        }
    }
    public function getPlayers(Request $request)
    {
        $columns = ['id', 'id', 'name','club' , 'phone', 'city', 'province', 'id', 'id', 'id'];

        $clubId = Auth::user()->club_id;
        $totalData = Player::where('club_id', $clubId)->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $orderIndex = $request->input('order.0.column');
        $order = $columns[$orderIndex] ?? 'id';
        $dir = $request->input('order.0.dir');

        $query = Player::query()->where('club_id', $clubId);

        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'played') {
                $query->has('tournaments');
            } elseif ($request->status === 'not_played') {
                $query->doesntHave('tournaments');
            }
        }

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where('players.name', 'LIKE', "%{$search}%");

            $totalFiltered = $query->count();
        }

        $players = $query
            ->join('clubs', 'players.club_id', '=', 'clubs.id')
            ->select('players.*', 'clubs.name as club_name')
            ->withCount('tournaments')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($players as $index => $player) {
            $circleClass = '';
            $image = 'profile-square.png';
            if(config('settings.profile_pic_type')==='circle') {
                $circleClass = ' rounded-circle ';
                $image = 'profile.png';
            }
            $nestedData['checkbox'] = '<input type="checkbox" class="player-checkbox" value="'.$player->id.'">';
            $nestedData['name'] = '<img src="'.asset('website/profiles/' . ($player->poster ?? $image)).'" width="40" class="profileimg  '.$circleClass.'  lozad"> <b>' . $player->name . '</b>';
            $nestedData['club'] = $player->club_name;
            $nestedData['phone'] = $player->phone;
            $nestedData['city'] = $player->city;
            $nestedData['province'] = $player->province;
            $nestedData['tournaments'] = $player->tournaments_count > 0 
                ? '<div class="text-center"><a href="javascript:void(0)" class="view-tournaments" data-id="'.$player->id.'"><span class="fas fa-eye" style="font-size: 1.2rem;"></span></a></div>' 
                : '';
            $nestedData['edit'] = '<a href="'.route('club_admin.player.edit', $player->id).'"><span class="fas fa-edit"></span></a>';
            $nestedData['delete'] = '<form id="delete-form-'.$player->id.'" method="post" action="'.route('club_admin.player.destroy', $player->id).'" style="display:none">'.csrf_field().method_field('DELETE').'</form>
            <a href="#" onclick="if(confirm(\'Are you sure?\')){event.preventDefault(); document.getElementById(\'delete-form-'.$player->id.'\').submit();} else {event.preventDefault();}"><span class="fas fa-trash-alt"></span></a>';

            $data[] = $nestedData;
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No players selected.']);
        }

        $clubId = Auth::user()->club_id;

        DB::beginTransaction();
        try {
            // Only select players belonging to the club
            $players = Player::where('club_id', $clubId)->whereIn('id', $ids)->get();
            $prefix = getStoragePrefix();
            
            foreach ($players as $player) {
                // Delete image from storage
                if ($player->poster) {
                    Storage::disk('r2')->delete("$prefix/website/profiles/" . $player->poster);
                }
                
                // Delete related data (pivot records and results)
                $player->tournaments()->detach();
                $player->playerTournamentResult()->delete();
                
                // Delete player
                $player->delete();
            }

            DB::commit();
            $this->websiteService->flushCache();
            return response()->json(['success' => true, 'message' => 'Selected players deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function getPlayerTournaments(Player $player)
    {
        // Ensure the player belongs to the club
        if ($player->club_id != Auth::user()->club_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tournaments = $player->tournaments()->paginate(20);
        return response()->json($tournaments);
    }
}
