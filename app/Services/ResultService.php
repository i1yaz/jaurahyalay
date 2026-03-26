<?php

namespace App\Services;

use App\Models\Admin\Result;
use App\Models\Admin\Setting;
use App\Models\Admin\Tournament;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultService
{
    private const PIGEON_TYPE = 'pigeon';
    private const START_TYPE = 'start';
    private const AUTO_UPDATE_TIME_KEY = 'auto_update_time';
    private const TIME_PADDING_LENGTH = 6;
    private const EMPTY_TIME_VALUES = ['00:00:00', '000000', '0000', '00:00', '00', null];
    protected $websiteService;
    protected $pendingCacheFlushes = [];

    public function __construct()
    {
        $this->websiteService = new WebsiteService();
    }

    /**
     * Update player time based on request data
     */
    public function updatePlayerTime(Request $request): string
    {
        $parsedData = $this->parseRequestData($request->pk);
        $autoUpdateEnabled = $this->isAutoUpdateEnabled();
        
        $requestData = [
            'name' => $request->name,
            'value' => $request->value
        ];

        $result = DB::transaction(function () use ($request, $requestData, $parsedData, $autoUpdateEnabled) {
            return match ($request->name) {
                self::PIGEON_TYPE => $this->handlePigeonTimeUpdate($requestData, $parsedData, $autoUpdateEnabled),
                self::START_TYPE => $this->handleStartTimeUpdate($requestData, $parsedData, $autoUpdateEnabled),
                default => 'nothing'
            };
        });

        $this->executePendingCacheFlushes();

        return $result;
    }

    /**
     * Parse request primary key into structured data
     */
    private function parseRequestData(string $primaryKey): array
    {
        return explode('_', $primaryKey);
    }

    /**
     * Check if auto update is enabled
     */
    private function isAutoUpdateEnabled(): bool
    {
        $setting = Setting::where('key', self::AUTO_UPDATE_TIME_KEY)->first();
        return $setting && $setting->value == 1;
    }

    /**
     * Handle pigeon time update with auto-update logic
     */
    private function handlePigeonTimeUpdate(array $requestData, array $parsedData, bool $autoUpdateEnabled): string
    {
        if ($autoUpdateEnabled) {
            $this->updatePlayerTournamentPigeonTimes($requestData, $parsedData);
        } else {
            $this->updatePigeonTime($requestData, $parsedData);
        }

        return $requestData['value'];
    }

    /**
     * Handle start time update with auto-update logic
     */
    private function handleStartTimeUpdate(array $requestData, array $parsedData, bool $autoUpdateEnabled): string
    {
        if ($autoUpdateEnabled) {
            $this->updateAllTournamentStartTimes($requestData, $parsedData);
        } else {
            $this->updateStartTime($requestData, $parsedData);
        }

        return $requestData['value'];
    }

    /**
 * Update pigeon times across all tournaments when auto-update is enabled
 */
private function updatePlayerTournamentPigeonTimes(array $requestData, array $parsedData): void
{
    [$tournamentId, $date, $playerId, $pigeonNumber] = $parsedData;

    $tournaments = \Illuminate\Support\Facades\DB::table('player_tournament')
        ->join('tournaments', 'player_tournament.tournament_id', '=', 'tournaments.id')
        ->join('tournament_flying_days', 'tournaments.id', '=', 'tournament_flying_days.tournament_id')
        ->where('player_tournament.player_id', $playerId)
        ->where('tournament_flying_days.date', $date)
        ->select(['tournaments.id as tournament_id', 'tournaments.club_id'])
        ->get();

    foreach ($tournaments as $tournament) {
        $modifiedData = $parsedData;
        $modifiedData[0] = $tournament->tournament_id;
        $modifiedData[count($parsedData)-1] = $tournament->club_id; // Always last element in parsedData for pigeons
        $this->updatePigeonTime($requestData, $modifiedData);
    }
}

/**
 * Update start times across all tournaments when auto-update is enabled
 */
private function updateAllTournamentStartTimes(array $requestData, array $parsedData): void
{
    [$tournamentId, $date, $playerId] = $parsedData;

    $tournaments = \Illuminate\Support\Facades\DB::table('player_tournament')
        ->join('tournaments', 'player_tournament.tournament_id', '=', 'tournaments.id')
        ->join('tournament_flying_days', 'tournaments.id', '=', 'tournament_flying_days.tournament_id')
        ->where('player_tournament.player_id', $playerId)
        ->where('tournament_flying_days.date', $date)
        ->select(['tournaments.id as tournament_id', 'tournaments.club_id'])
        ->get();

    foreach ($tournaments as $tournament) {
        $modifiedData = $parsedData;
        $modifiedData[0] = $tournament->tournament_id;
        $modifiedData[count($parsedData)-1] = $tournament->club_id; // Always last element in parsedData for start times
        $this->updateStartTime($requestData, $modifiedData);
    }
}


    /**
     * Update pigeon time for a specific result
     */
    private function updatePigeonTime(array $requestData, array $parsedData): string
    {
        [$tournamentId, $date, $playerId, $pigeonNumber,$club_id] = $parsedData;
        $formattedTime = $this->formatTimeValue($requestData['value']);

        $result = $this->findResult($tournamentId, $date, $playerId, $pigeonNumber);

        if ($result) {
            $this->updateExistingPigeonResult($result, $formattedTime);
        } else {
            $this->createNewPigeonResult($tournamentId, $date, $playerId, $pigeonNumber, $formattedTime);
        }

        $this->updatePlayerTournamentTotal($tournamentId, $date, $playerId);
        $this->deferCacheFlush($tournamentId, $date, $club_id);
        return $requestData['value'];
    }

    /**
     * Update start time for a specific result
     */
    public function updateStartTime(array $requestData, array $parsedData): string
    {
        [$tournamentId, $date, $playerId,$club_id] = $parsedData;
        $formattedTime = $this->formatTimeValue($requestData['value']);

        $result = $this->findPlayerTournamentResult($tournamentId, $date, $playerId);

        if ($result) {
            $this->updateAllPigeonTimesAfterStartTimeChange($formattedTime, $parsedData);
        } else {
            $this->createInitialStartTimeResult($tournamentId, $date, $playerId, $formattedTime);
        }

        $this->updatePlayerTournamentTotal($tournamentId, $date, $playerId);
        $this->deferCacheFlush($tournamentId, $date, $club_id);
        return $requestData['value'];
    }

    /**
     * Format time value by removing colons and padding with zeros
     */
    private function formatTimeValue(string $timeValue): string
    {
        return str_pad(str_replace(':', '', $timeValue), self::TIME_PADDING_LENGTH, '0');
    }

    /**
     * Find a specific result by tournament, date, player, and pigeon
     */
    private function findResult(string $tournamentId, string $date, string $playerId, string $pigeonNumber): ?Result
    {
        return Result::where('tournament_id', $tournamentId)
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->where('pigeon_number', $pigeonNumber)
            ->first();
    }

    /**
     * Find a player's tournament result
     */
    private function findPlayerTournamentResult(string $tournamentId, string $date, string $playerId): ?Result
    {
        return Result::where('tournament_id', $tournamentId)
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->first();
    }

    /**
     * Update existing pigeon result
     */
    private function updateExistingPigeonResult(Result $result, string $formattedTime): void
    {
        $totalTime = $this->calculateTotalTime($result->start_time, $formattedTime);
        
        $result->update([
            'pigeon_time' => $formattedTime,
            'pigeon_total' => $totalTime,
            'time_in_seconds' => $totalTime
        ]);
    }

    /**
     * Create new pigeon result
     */
    private function createNewPigeonResult(string $tournamentId, string $date, string $playerId, string $pigeonNumber, string $formattedTime): void
    {
        $playerResult = $this->findPlayerTournamentResult($tournamentId, $date, $playerId);
        $startTime = $playerResult ? $playerResult->start_time : null;
        $totalTime = $this->calculateTotalTime($startTime, $formattedTime);

        Result::create([
            'player_id' => $playerId,
            'tournament_id' => $tournamentId,
            'date' => $date,
            'pigeon_number' => $pigeonNumber,
            'start_time' => $startTime,
            'pigeon_time' => $formattedTime,
            'pigeon_total' => $totalTime,
            'time_in_seconds' => $totalTime
        ]);
    }

    /**
     * Create initial start time result
     */
    private function createInitialStartTimeResult(string $tournamentId, string $date, string $playerId, string $formattedTime): void
    {
        Result::create([
            'player_id' => $playerId,
            'tournament_id' => $tournamentId,
            'date' => $date,
            'start_time' => $formattedTime,
            'pigeon_number' => 1
        ]);
    }

    /**
     * Calculate total time between start and pigeon time
     */
    private function calculateTotalTime(?string $startTime, ?string $pigeonTime): int|string
    {
        if ($this->isValidTime($pigeonTime) && $startTime) {
            
            return Carbon::parse($startTime)->diffInSeconds(Carbon::parse($pigeonTime));
        }

        return 0;
    }

    /**
     * Check if time value is valid (not empty or zero)
     */
    private function isValidTime(?string $time): bool
    {
        return !in_array($time, self::EMPTY_TIME_VALUES, true);
    }

    /**
     * Update player tournament total statistics
     */
    private function updatePlayerTournamentTotal(string $tournamentId, string $date, string $playerId): void
    {
        $tournament = Tournament::find($tournamentId);
        $results = $this->getPlayerTournamentResults($tournamentId, $date, $playerId);
        $validResults = $this->filterValidResults($results);
        $landedCount = $validResults->count();

        $processedResults = $this->applySupporterLogic($tournament, $landedCount, $validResults);

        // Double stamp logic
        $doubleStampResults = $validResults->filter(fn($r) => $r->is_double_stamp == 1 || $r->is_double_stamp === true);
        $doubleStampLanded = $doubleStampResults->count();
        $doubleStampTotal = $doubleStampResults->sum('pigeon_total');

        $this->updatePlayerTournamentTotalRecord($tournamentId, $date, $playerId, $landedCount, $processedResults, $doubleStampLanded, $doubleStampTotal);
    }

    /**
     * Get all results for a player in a tournament
     */
    private function getPlayerTournamentResults(string $tournamentId, string $date, string $playerId): Collection
    {
        return Result::where('tournament_id', $tournamentId)
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->get();
    }

    /**
     * Filter out invalid time results
     */
    private function filterValidResults(Collection $results): Collection
    {
        return $results->reject(function (Result $result) {
            return in_array($result->pigeon_time, self::EMPTY_TIME_VALUES, true);
        });
    }

    /**
     * Apply supporter logic to results
     */
    private function applySupporterLogic(Tournament $tournament, int $landedCount, Collection $results, bool $persist = true): Collection
    {
        if ($tournament->supporter > 0 && $landedCount > ($tournament->pigeons - $tournament->supporter)) {
            return $this->processSupporterResults($tournament, $landedCount, $results, $persist);
        }

        return $results;
    }

    /**
 * Process results when supporter logic applies
 */
private function processSupporterResults(Tournament $tournament, int $landedCount, \Illuminate\Support\Collection $results, bool $persist = true): \Illuminate\Support\Collection
{
    // First, restore any previously dropped records to their true value
    if ($persist) {
        Result::whereIn('id', $results->pluck('id'))
            ->whereColumn('pigeon_total', '!=', 'time_in_seconds')
            ->update([
                'pigeon_total' => \Illuminate\Support\Facades\DB::raw('time_in_seconds')
            ]);
        foreach ($results as $result) {
            $result->pigeon_total = $result->time_in_seconds;
        }
    } else {
        foreach ($results as $result) {
            $result->pigeon_total = $result->time_in_seconds;
        }
    }

    $sortedResults = $results->sortBy('time_in_seconds')->values();
    $targetCount = $tournament->pigeons - $tournament->supporter;
    $excessCount = $landedCount - $targetCount;

    $resultsToZero = $sortedResults->take($excessCount);

    if ($resultsToZero->isNotEmpty()) {
        if ($persist) {
            Result::whereIn('id', $resultsToZero->pluck('id'))->update(['pigeon_total' => 0]);
        } else {
            foreach ($resultsToZero as $result) {
                $result->pigeon_total = 0;
            }
        }
        
        // Also update memory ref
        foreach ($results as $result) {
            if ($resultsToZero->contains('id', $result->id)) {
                $result->pigeon_total = 0;
            }
        }
    }

    return $results;
}

    /**
     * Update or insert player tournament total record
     */
    private function updatePlayerTournamentTotalRecord(string $tournamentId, string $date, string $playerId, int $landedCount, Collection $results, int $doubleStampLanded = 0, float $doubleStampTotal = 0): void
    {
        DB::table('player_tournament_total')
            ->updateOrInsert(
                [
                    'tournament_id' => $tournamentId,
                    'date' => $date,
                    'player_id' => $playerId
                ],
                [
                    'landed' => $landedCount,
                    'total' => $results->sum('pigeon_total'),
                    'double_stamp_landed' => $doubleStampLanded,
                    'double_stamp_total' => $doubleStampTotal
                ]
            );
    }

    /**
     * Update all pigeon times after start time change
     */
    private function updateAllPigeonTimesAfterStartTimeChange(string $newStartTime, array $parsedData): void
    {
        [$tournamentId, $date, $playerId] = $parsedData;

        $results = Result::where('tournament_id', $tournamentId)
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->get();

        $upsertData = [];
        
        foreach ($results as $result) {
            $newTotalTime = $this->calculateTotalTime($newStartTime, $result->pigeon_time);
            
            $upsertData[] = [
                'id' => $result->id,
                'player_id' => $result->player_id,
                'tournament_id' => $result->tournament_id,
                'date' => $result->date,
                'pigeon_number' => $result->pigeon_number,
                'start_time' => $newStartTime,
                'pigeon_time' => $result->pigeon_time,
                'pigeon_total' => $newTotalTime,
                'time_in_seconds' => $newTotalTime,
            ];
        }

        if (!empty($upsertData)) {
            // Because there's no native "batch update by ID" in Laravel without firing N queries, 
            // and upsert requires a unique index match. The unique index is [player_id, tournament_id, date, pigeon_number].
            Result::upsert(
                $upsertData,
                ['player_id', 'tournament_id', 'date', 'pigeon_number'],
                ['start_time', 'pigeon_total', 'time_in_seconds']
            );
        }
    }

    /**
     * Bulk recalculate all players for a specific tournament and date
     * Fixing N+1 queries when updating start time from auto-recovery
     */
    public function bulkRecalculateForTournament(string $tournamentId, string $date, string $clubId): void
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            return;
        }

        DB::transaction(function () use ($tournament, $tournamentId, $date) {
            // Fetch ALL results using a single JOIN to grab pigeon 1's start_time in the same query.
            // Also limit the SELECT scope to minimize RAM footprint.
            $allResults = Result::from('results as r1')
                ->join('results as r2', function ($join) use ($tournamentId, $date) {
                    $join->on('r1.player_id', '=', 'r2.player_id')
                         ->where('r2.tournament_id', $tournamentId)
                         ->where('r2.date', $date)
                         ->where('r2.pigeon_number', 1)
                         ->whereNotNull('r2.start_time');
                })
                ->where('r1.tournament_id', $tournamentId)
                ->where('r1.date', $date)
                ->select([
                    'r1.id',
                    'r1.player_id',
                    'r1.tournament_id',
                    'r1.date',
                    'r1.pigeon_number',
                    'r2.start_time as new_start_time',
                    'r1.start_time',
                    'r1.pigeon_time',
                    'r1.pigeon_total',
                    'r1.time_in_seconds'
                ])
                ->get()
                ->groupBy('player_id');

            $upsertData = [];
            $totalUpsertData = [];

            foreach ($allResults as $playerId => $playerResults) {
                // new_start_time is guaranteed to exist and be identical across the player's pigeons 
                // because it is mapped from the INNER JOIN on pigeon_number = 1.
                $newStartTime = $playerResults->first()->new_start_time;
                
                // First pass: update times
                foreach ($playerResults as $result) {
                    $newTotalTime = $this->calculateTotalTime($newStartTime, $result->pigeon_time);
                    
                    $result->start_time = $newStartTime;
                    $result->pigeon_total = $newTotalTime;
                    $result->time_in_seconds = $newTotalTime;

                    $upsertData[] = [
                        'id' => $result->id,
                        'player_id' => $result->player_id,
                        'tournament_id' => $result->tournament_id,
                        'date' => $result->date,
                        'pigeon_number' => $result->pigeon_number,
                        'start_time' => $newStartTime,
                        'pigeon_time' => $result->pigeon_time,
                        'pigeon_total' => $newTotalTime,
                        'time_in_seconds' => $newTotalTime,
                    ];
                }

                // Second pass: apply supporter logic directly on the collection without DB persisting
                $validResults = $this->filterValidResults($playerResults);
                $landedCount = $validResults->count();

                $processedResults = $this->applySupporterLogic($tournament, $landedCount, $validResults, false);

                // Double stamp logic
                $doubleStampResults = $validResults->filter(fn($r) => $r->is_double_stamp == 1 || $r->is_double_stamp === true);
                $doubleStampLanded = $doubleStampResults->count();
                $doubleStampTotal = $doubleStampResults->sum('pigeon_total');

                $totalUpsertData[] = [
                    'tournament_id' => $tournamentId,
                    'date' => $date,
                    'player_id' => $playerId,
                    'landed' => $landedCount,
                    'total' => $processedResults->sum('pigeon_total'),
                    'double_stamp_landed' => $doubleStampLanded,
                    'double_stamp_total' => $doubleStampTotal
                ];
                
                // Sync zeroed pigeon_totals back to upsertData
                // Since $playerResults objects were updated in applySupporterLogic, we just need to recreate the upsert payload
                // Let's clear the previous entries for this player and rebuild them after supporter logic
                $upsertData = array_filter($upsertData, function ($item) use ($playerId) {
                    return $item['player_id'] !== $playerId;
                });
                
                foreach ($playerResults as $result) {
                    $upsertData[] = [
                        'id' => $result->id,
                        'player_id' => $result->player_id,
                        'tournament_id' => $result->tournament_id,
                        'date' => $result->date,
                        'pigeon_number' => $result->pigeon_number,
                        'start_time' => $newStartTime,
                        'pigeon_time' => $result->pigeon_time,
                        'pigeon_total' => $result->pigeon_total,
                        'time_in_seconds' => $result->time_in_seconds,
                    ];
                }
            }

            // Batch update all results
            if (!empty($upsertData)) {
                // Using chunks to avoid hitting MySQL placeholders limit if there are thousands of rows
                foreach (array_chunk($upsertData, 500) as $chunk) {
                    Result::upsert(
                        $chunk,
                        ['player_id', 'tournament_id', 'date', 'pigeon_number'],
                        ['start_time', 'pigeon_total', 'time_in_seconds']
                    );
                }
            }

            // Batch update totals
            if (!empty($totalUpsertData)) {
                foreach (array_chunk($totalUpsertData, 500) as $chunk) {
                    DB::table('player_tournament_total')->upsert(
                        $chunk,
                        ['player_id', 'tournament_id', 'date'],
                        ['landed', 'total', 'double_stamp_landed', 'double_stamp_total']
                    );
                }
            }
        });

        $this->deferCacheFlush($tournamentId, $date, $clubId);
        $this->executePendingCacheFlushes();
    }
    public function canEditThisResult($request)
    {
        $data = explode('_', $request->pk);
        $tournament_id =  $data[0];
        return (new TournamentService)->canEditThisTournament($tournament_id);
    }

    /**
     * Defer cache flushes to be executed all at once
     */
    public function deferCacheFlush(string $tournamentId, string $date, string $clubId): void
    {
        $key = "{$tournamentId}_{$date}_{$clubId}";
        if (!isset($this->pendingCacheFlushes[$key])) {
            $this->pendingCacheFlushes[$key] = [
                'tournament_id' => $tournamentId,
                'date' => $date,
                'club_id' => $clubId
            ];
        }
    }

    /**
     * Toggle double stamp status for a pigeon
     */
    public function toggleDoubleStamp(string $primaryKey): bool
    {
        $parsedData = explode('_', $primaryKey);
        if (count($parsedData) < 4) {
            return false;
        }

        [$tournamentId, $date, $playerId, $pigeonNumber] = $parsedData;

        $result = $this->findResult($tournamentId, $date, $playerId, $pigeonNumber);

        if ($result) {
            $result->is_double_stamp = !$result->is_double_stamp;
            $result->save();

            $clubId = end($parsedData);
            $this->deferCacheFlush($tournamentId, $date, $clubId);

            $this->updatePlayerTournamentTotal($tournamentId, $date, $playerId);

            $this->executePendingCacheFlushes();

            return $result->is_double_stamp;
        }

        return false;
    }

    /**
     * Execute any pending deferred cache flushes natively as a batch
     */
    public function executePendingCacheFlushes(): void
    {
        if (empty($this->pendingCacheFlushes)) {
            return;
        }

        $this->websiteService->flushCacheBatch(array_values($this->pendingCacheFlushes));
        $this->pendingCacheFlushes = [];
    }
}