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

        return match ($request->name) {
            self::PIGEON_TYPE => $this->handlePigeonTimeUpdate($requestData, $parsedData, $autoUpdateEnabled),
            self::START_TYPE => $this->handleStartTimeUpdate($requestData, $parsedData, $autoUpdateEnabled),
            default => 'nothing'
        };
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

        $tournaments = Result::select(['tournament_id','club_id'])
            ->join('tournaments', 'results.tournament_id', '=', 'tournaments.id')
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->where('pigeon_number', $pigeonNumber)
            ->get();

        foreach ($tournaments as $tournament) {
            $modifiedData = $parsedData;
            $modifiedData[0] = $tournament->tournament_id;
            $modifiedData[5] = $tournament->club_id;
            $this->updatePigeonTime($requestData, $modifiedData);
        }
    }

    /**
     * Update start times across all tournaments when auto-update is enabled
     */
    private function updateAllTournamentStartTimes(array $requestData, array $parsedData): void
    {
        [$tournamentId, $date, $playerId] = $parsedData;

        $tournaments = Result::select(['tournament_id','club_id'])
            ->join('tournaments','results.tournament_id','=','tournaments.id')
            ->where('date', $date)
            ->where('player_id', $playerId)
            ->get();

        foreach ($tournaments as $tournament) {
            $modifiedData = $parsedData;
            $modifiedData[0] = $tournament->tournament_id;
            $modifiedData[5] = $tournament->club_id;
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
        $this->websiteService->flushCache($tournamentId, $date, $club_id);
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
        $this->websiteService->flushCache($tournamentId, $date, $club_id);
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

        $this->updatePlayerTournamentTotalRecord($tournamentId, $date, $playerId, $landedCount, $processedResults);
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
    private function applySupporterLogic(Tournament $tournament, int $landedCount, Collection $results): Collection
    {
        if ($tournament->supporter > 0 && $landedCount > ($tournament->pigeons - $tournament->supporter)) {
            return $this->processSupporterResults($tournament, $landedCount, $results);
        }

        return $results;
    }

    /**
     * Process results when supporter logic applies
     */
    private function processSupporterResults(Tournament $tournament, int $landedCount, Collection $results): Collection
    {
        $sortedResults = $results->sortBy('pigeon_total');
        $targetCount = $tournament->pigeons - $tournament->supporter;
        $excessCount = $landedCount - $targetCount;

        $resultsToZero = $sortedResults->take($excessCount);

        foreach ($resultsToZero as $result) {
            Result::where('id', $result->id)->update(['pigeon_total' => 0]);
        }

        return $sortedResults->skip($excessCount);
    }

    /**
     * Update or insert player tournament total record
     */
    private function updatePlayerTournamentTotalRecord(string $tournamentId, string $date, string $playerId, int $landedCount, Collection $results): void
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
                    'total' => $results->sum('pigeon_total')
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

        foreach ($results as $result) {
            $newTotalTime = $this->calculateTotalTime($newStartTime, $result->pigeon_time);

            Result::where('id', $result->id)->update([
                'start_time' => $newStartTime,
                'pigeon_total' => $newTotalTime,
                'time_in_seconds' => $newTotalTime
            ]);
        }
    }
    public function canEditThisResult($request)
    {
        $data = explode('_', $request->pk);
        $tournament_id =  $data[0];
        return (new TournamentService)->canEditThisTournament($tournament_id);
    }
}