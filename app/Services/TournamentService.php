<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use App\Models\Admin\Club;
use App\Models\Admin\News;
use Illuminate\Support\Str;
use App\Models\Admin\Player;
use App\Models\Admin\Result;
use App\Models\Admin\Slider;
use Illuminate\Http\Request;
use App\Models\Admin\Setting;
use App\Models\Admin\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\TournamentPrize;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Models\Admin\TournamentFlyingDay;

class TournamentService
{
    private const BATCH_SIZE = 1000;

    public function updateTournament(Request $request, Tournament $tournament): Tournament
    {
        return DB::transaction(function () use ($request, $tournament) {
            try {
                // Validate and prepare data
                $updateData = $this->prepareUpdateData($request);

                // Handle show homepage logic
                $this->handleShowHomePage($request);

                // Load tournament with relationships once
                $tournament = $this->loadTournamentWithRelations($tournament->id);

                // Sync dates and clean up results
                $this->syncTournamentDates($tournament, $request->date ?? []);

                // Update tournament attributes
                $tournament->update($updateData);

                // Update related entities
                $this->updateTournamentDays($tournament, $request->date ?? []);
                $this->updateTournamentPrizes($tournament, $request->prize ?? []);
                $this->syncTournamentPlayers($tournament, $request->players ?? []);

                // Refresh tournament to get updated relationships
                $tournament->refresh();

                // Update tournament-related data
                $this->updateTournamentPlayerData($tournament);
                
                return $tournament;
            } catch (\Exception $e) {
                Log::error('Tournament update failed: ' . $e->getMessage(), [
                    'tournament_id' => $tournament->id,
                    'request_data' => $request->all()
                ]);
                throw $e;
            }
        });
    }

    private function prepareUpdateData(Request $request): array
    {
        return [
            'name' => $request->name,
            'club_id' => $request->club,
            'days' => $request->days,
            'status' => $request->status === 'on',
            'show' => $request->show === 'on',
            'pigeons' => $request->pigeons,
            'start_date' => $request->date[0] ?? null,
            'start_time' => $request->time,
            'sort' => $request->sort ?? 0,
            'supporter' => $request->supporter,
            'type' => $request->type,
            'public_hide' => $request->public_hide === 'on',
        ];
    }

    private function handleShowHomePage(Request $request): void
    {
        if ($request->show === 'on') {
            Tournament::query()->update(['show' => false]);
        }
    }

    private function loadTournamentWithRelations(int $tournamentId): Tournament
    {
        return Tournament::with(['flyingDays', 'players', 'tournamentPrize'])
            ->findOrFail($tournamentId);
    }

    private function syncTournamentDates(Tournament $tournament, array $newDates): void
    {
        $currentDates = $tournament->flyingDays->pluck('date')->toArray();
        $datesToRemove = array_diff($currentDates, $newDates);

        if (!empty($datesToRemove)) {
            // Use bulk delete for better performance
            Result::where('tournament_id', $tournament->id)
                ->whereIn('date', $datesToRemove)
                ->delete();
        }
    }

    private function updateTournamentDays(Tournament $tournament, array $dates): void
    {
        if (empty($dates)) {
            $tournament->flyingDays()->delete();
            return;
        }

        // Use bulk operations for better performance
        $tournament->flyingDays()->delete();

        $flyingDays = collect($dates)->map(function ($date) use ($tournament) {
            return [
                'tournament_id' => $tournament->id,
                'date' => $date,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        })->toArray();

        if (!empty($flyingDays)) {
            TournamentFlyingDay::insert($flyingDays);
        }
    }

    private function updateTournamentPrizes(Tournament $tournament, array $prizes): void
    {
        // Always delete existing prizes
        $tournament->tournamentPrize()->delete();

        if (empty($prizes)) {
            return;
        }

        $prizeData = collect($prizes)->map(function ($prize, $position) use ($tournament) {
            return [
                'tournament_id' => $tournament->id,
                'name' => $prize,
                'position' => (string) $position,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        })->toArray();

        TournamentPrize::insert($prizeData);
    }

    private function syncTournamentPlayers(Tournament $tournament, array $playerIds): void
    {
        $currentPlayerIds = $tournament->players->pluck('id')->toArray();
        $removedPlayerIds = array_diff($currentPlayerIds, $playerIds);

        // Sync players
        $tournament->players()->sync($playerIds);

        // Clean up data for removed players
        if (!empty($removedPlayerIds)) {
            $this->cleanupRemovedPlayersData($tournament->id, $removedPlayerIds);
        }
    }

    private function cleanupRemovedPlayersData(int $tournamentId, array $removedPlayerIds): void
    {
        // Use bulk delete operations
        Result::where('tournament_id', $tournamentId)
            ->whereIn('player_id', $removedPlayerIds)
            ->delete();

        DB::table('player_tournament_total')
            ->where('tournament_id', $tournamentId)
            ->whereIn('player_id', $removedPlayerIds)
            ->delete();
    }

    private function updateTournamentPlayerData(Tournament $tournament): void
    {
        $this->updateTournamentPlayerStartTimes($tournament);
        $this->updatePlayerTournamentTotals($tournament);
    }

    private function updateTournamentPlayerStartTimes(Tournament $tournament): void
    {
        $pigeonCount = $tournament->pigeons ?? 0;

        if ($pigeonCount <= 0 || $tournament->flyingDays->isEmpty() || $tournament->players->isEmpty()) {
            return;
        }

        // Clean up existing records
        Result::where('tournament_id', $tournament->id)
            ->where('pigeon_total', 0)
            ->whereNull('pigeon_time')
            ->delete();

        $resultData = [];
        $now = Carbon::now();
        $playerWithChangedStartTime = DB::table('results')->select(['player_id','start_time'])->where('tournament_id', $tournament->id)
            ->where('start_time','!=' ,$tournament->start_time)
            ->groupBy('player_id')
            ->pluck( 'start_time','player_id')
            ->toArray();

        foreach ($tournament->flyingDays as $day) {
            foreach ($tournament->players as $player) {
                $startTime = ( isset($playerWithChangedStartTime[$player->id])  && $playerWithChangedStartTime[$player->id] != null)
                    ? $playerWithChangedStartTime[$player->id] 
                    : $tournament->start_time;
                    

                for ($i = 1; $i <= $pigeonCount; $i++) {
                    $resultData[] = [
                        'player_id' => $player->id,
                        'tournament_id' => $tournament->id,
                        'pigeon_number' => $i,
                        'start_time' => $startTime,
                        'date' => $day->date,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        // Process in batches to avoid memory issues
        $uniqueColumns = ['player_id', 'tournament_id', 'date', 'pigeon_number'];
        $updateColumns = ['pigeon_number', 'date', 'start_time'];

        foreach (array_chunk($resultData, self::BATCH_SIZE) as $batch) {
            DB::table('results')->upsert($batch, $uniqueColumns, $updateColumns);
        }
    }

    private function updatePlayerTournamentTotals(Tournament $tournament): void
    {
        $dates = $tournament->flyingDays->pluck('date')->toArray();
        $playerIds = $tournament->players->pluck('id')->toArray();

        if (empty($dates) || empty($playerIds)) {
            return;
        }

        // Clean up existing records
        DB::table('player_tournament_total')
            ->where('tournament_id', $tournament->id)
            ->where('landed', 0)
            ->where('total', 0)
            ->delete();

        $totalData = [];
        foreach ($dates as $date) {
            foreach ($playerIds as $playerId) {
                $totalData[] = [
                    'tournament_id' => $tournament->id,
                    'date' => $date,
                    'player_id' => $playerId,
                ];
            }
        }

        if (!empty($totalData)) {
            $uniqueColumns = ['player_id', 'tournament_id', 'date'];

            foreach (array_chunk($totalData, self::BATCH_SIZE) as $batch) {
                DB::table('player_tournament_total')->upsert(
                    $batch,
                    $uniqueColumns,
                    $uniqueColumns
                );
            }
        }
    }

    public function storeTournament(Request $request): Tournament
    {
        return DB::transaction(function () use ($request) {
            try {
                // Handle show homepage logic
                $this->handleShowHomePage($request);

                // Validate and prepare data
                $tournamentData = $this->prepareCreateData($request);

                // Create tournament
                $tournament = Tournament::create($tournamentData);

                // Update related entities
                $this->updateTournamentDays($tournament, $request->date ?? []);
                $this->updateTournamentPrizes($tournament, $request->prize ?? []);

                // Sync players
                $tournament->players()->sync($request->players ?? []);

                // Load tournament with relationships
                $tournament = $this->loadTournamentWithRelations($tournament->id);

                // Update tournament-related data
                $this->updateTournamentPlayerData($tournament);

                return $tournament;
            } catch (\Exception $e) {
                Log::error('Tournament creation failed: ' . $e->getMessage(), [
                    'request_data' => $request->all()
                ]);
                throw $e;
            }
        });
    }

    private function prepareCreateData(Request $request): array
    {
        return [
            'name' => $request->name,
            'club_id' => $request->club,
            'poster' => null,
            'days' => $request->days,
            'status' => $request->status === 'on',
            'show' => $request->show === 'on',
            'pigeons' => $request->pigeons,
            'start_date' => $request->date[0] ?? null,
            'start_time' => $request->time,
            'supporter' => $request->supporter,
            'type' => $request->type,
            'sort' => $request->sort ?? 0,
            'public_hide' => $request->public_hide === 'on',
        ];
    }

    // Additional helper methods for better code organization
    private function validateTournamentData(Request $request): void
    {
        // Add validation logic here if needed
        // This could include business rule validations
    }

    private function logTournamentUpdate(Tournament $tournament, array $changes): void
    {
        Log::info('Tournament updated successfully', [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'changes' => $changes
        ]);
    }



    public function storePoster(Request $request, Tournament $tournament, $type = 'create'): void
    {
        if ($request->hasFile('poster')) {
            $filename = Str::random(40);
            $prefix = getStoragePrefix();
            if ('update' == $type  && !is_null($tournament->poster)) {

                if (Storage::disk('r2')->exists("$prefix/uploads/{$tournament->poster}")) {
                    Storage::disk('r2')->delete("$prefix/uploads/{$tournament->poster}");
                }
            }
            $image = Image::make($request->poster)
                ->encode('webp', 80)
                ->resize(1280, 250);


            Storage::disk('r2')->put("{$prefix}/uploads/{$filename}.webp", $image->stream());
            $tournament->poster = "$filename.webp";
            $tournament->save();
        }
    }
    public function syncTournamentModerator($request, $tournament)
    {
        if (is_array($request->tournament_admins)) {
            foreach ($request->tournament_admins as $admin) {
                DB::table('tournament_moderator')->where(['tournament_id' => $tournament->id])->delete();
                DB::table('tournament_moderator')->insert([
                    'user_id' => $admin,
                    'tournament_id' => $tournament->id,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]);
            }
        }
    }
    public function getTournamentAdmins($tournament)
    {
        return DB::table('tournament_moderator')
            ->where('tournament_id', $tournament->id)
            ->pluck('user_id')
            ->toArray();
    }

    public function getAllPlayers()
    {
        return Player::all();
    }
    public function getAllClubs()
    {
        return Club::all();
    }
    public function getAllSliders()
    {
        return Slider::get();
    }
    public function getAllUsers($records)
    {
        return User::paginate($records);
    }
    public function getAllNews()
    {
        return News::get();
    }
    public function storeNews(Request $request): bool
    {
        News::create([
            'name' => $request->name,
            'show' => $request->status == "true",
        ]);
        return true;
    }
    public function updateNews(Request $request, $news): bool
    {
        $news = News::find($news->id);
        $news->name = $request->name;
        $news->show = $request->status == "true";
        $news->update();
        return true;
    }
    public function getAdmins()
    {
        return User::whereNot('id', 1)->get();
    }
    public function getSortedClub($records)
    {
        return Club::orderBy('sort')->paginate($records);
    }
    public function storeClub(Request $request): bool
    {
        Club::create([
            'name' => $request->name,
            'owner' => $request->owner,
            'phone' => $request->phone,
            'city' => $request->city,
            'sort' => $request->sort??0,
            'status' => $request->status == "true",
            'poster' => $request->poster,
        ]);
        return true;
    }
    public function storePlayer(Request $request): Player
    {
        return $this->savePlayer($request);
    }
    public function updatePlayer(Request $request, Player $player): Player
    {
        return $this->savePlayer($request,$player->id);
    }
    private function savePlayer($request,int $id=0):Player
    {
        $player = Player::updateOrCreateInstance($id);
        $player->name = $request->name;
        $player->phone = $request->phone;
        $player->city = $request->city;
        $player->province = $request->province;
        $player->save();
        return $player;
    }
    public function updateClub(Request $request, $club): bool
    {
        $club = Club::find($club->id);
        $club->name = $request->name;
        $club->owner = $request->owner;
        $club->phone = $request->phone;
        $club->city = $request->city;
        $club->sort = $request->sort??0;
        $club->status = $request->status == "true";
        $club->poster = $request->poster;
        $club->update();
        return true;
    }
    public function storeSliders(Request $request)
    {
        $prefix = getStoragePrefix();
        $sliders = Slider::all();
        // Delete old files from R2
        foreach ($sliders as $slider) {
            if (Storage::disk('r2')->exists("$prefix/website/sliders/{$slider->slider}")) {
                $result = Storage::disk('r2')->delete("$prefix/website/sliders/{$slider->slider}");
                if ($result) {
                    $slider->delete();
                }
            }else{
                $slider->delete();
            }
        }
        
        foreach ($request->file('sliders') as $sliderImage) {
            $filename = Str::random(40) . '.webp';

            $image = Image::make($sliderImage)
                ->encode('webp')
                ->resize(1280, 250);
            Storage::disk('r2')->put(
                "$prefix/website/sliders/{$filename}", 
                $image->stream()
            );
            
            // Save to database
            $sliderInstance = new Slider();
            $sliderInstance->slider = $filename;
            $sliderInstance->save();
        }
        
        return true;
    }
    public function storePlayerPicture($request, Player $player, $type = 'create')
    {
        if ($request->has('profile_64') && !is_null($request->profile_64)) {
            $filename = Str::random(40);
            $prefix = getStoragePrefix();
            if ($type === 'update') {
                Storage::disk('r2')->delete("$prefix/website/profiles/{$player->poster}");
            }

            // Create the image in memory
            $image = Image::make($request->profile_64)
                ->encode('webp', 80);
            Storage::disk('r2')->put("{$prefix}/website/profiles/{$filename}.webp", $image->stream());

            $player->poster = "$filename.webp";
            $player->save();
        }
    }
    public function getTournamentManagingByThisPlayer()
    {
        return DB::table('tournament_moderator')->where('user_id', Auth::id())->get();
    }
    public function getActiveTournament($records)
    {
        $query = Tournament::with('players')
            ->where('status', true)
            ->orderBy('show', 'desc');

        if (Auth::user()->club_id > 0) {
            $query->where('club_id', Auth::user()->club_id);
        }

        return $query->paginate($records);
    }

    public function canEditThisTournament($tournament_id)
    {
        $user = Auth::user();

        if ($user->super_admin) {
            return true;
        }

        if ($user->club_id > 0) {
            return Tournament::where('id', $tournament_id)
                ->where('club_id', $user->club_id)
                ->where('status', true)
                ->exists();
        }

        return DB::table('tournament_moderator')
            ->where('user_id', $user->id)
            ->where('tournament_id', $tournament_id)
            ->exists();
    }
    public function getActiveTournamentForResult($tournament_id, $date)
    {
        return DB::table('results')
            ->where('tournament_id', $tournament_id)
            ->where('date', $date)
            ->orderBy('player_id')
            ->get()->groupBy('player_id');
    }
}
