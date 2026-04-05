<?php

use App\Models\Admin\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

function getFirstWinnerLastWinners($tournament, $resultDate, $players)
{
    try {

        $FirstLastWinnerSettings =  Cache::remember('FirstLastWinnerSettings', now()->addMinutes(60), function () use ($tournament) {

            return Setting::where('group_type', Setting::FIRST_WINNER_LAST_WINNER_GROUP)->where('type', $tournament->pigeons)->get();
        });

        $firsWinnerCondition = $FirstLastWinnerSettings->where('key', 'first_' . $tournament->pigeons)->first()->value ?? 1;
        $lastWinnerCondition = $FirstLastWinnerSettings->where('key', 'last_' . $tournament->pigeons)->first()->value ?? 2;

        $firstWinner =  Cache::remember('firstWinner_' . $tournament->id . '_' . $resultDate . '_' . $firsWinnerCondition, now()->addMinutes(60), function () use ($tournament, $resultDate, $firsWinnerCondition) {
            return  DB::table('results as r1')
                ->join(DB::raw("(
                SELECT player_id
                FROM results
                WHERE time_in_seconds > 0
                AND tournament_id = {$tournament->id}
                AND `date` = '{$resultDate}'
                GROUP BY player_id
                HAVING COUNT(*) >= {$firsWinnerCondition}
            ) as qualified"), 'r1.player_id', '=', 'qualified.player_id')
                ->where('r1.pigeon_number', 1)
                ->where('r1.time_in_seconds', '>', 0)
                ->where('r1.tournament_id', $tournament->id)
                ->where('r1.date', $resultDate)
                ->orderByDesc('r1.time_in_seconds')
                ->select('r1.player_id', 'r1.time_in_seconds', 'r1.pigeon_time')
                ->first();
        });

        $lastWinner =  Cache::remember('lastWinner' . $tournament->id . '_' . $resultDate . '_' . $lastWinnerCondition, now()->addMinutes(60), function () use ($tournament, $resultDate, $lastWinnerCondition) {
            return DB::table('results as r2')
                ->join(DB::raw("(
        SELECT player_id
        FROM results
        WHERE time_in_seconds > 0
          AND tournament_id = {$tournament->id}
          AND `date` = '{$resultDate}'
        GROUP BY player_id
        HAVING COUNT(*) >= {$lastWinnerCondition}
    ) as qualified"), 'r2.player_id', '=', 'qualified.player_id')
                ->where('r2.pigeon_number', '!=', 1)
                ->where('r2.time_in_seconds', '>', 0)
                ->where('r2.tournament_id', $tournament->id)
                ->where('r2.date', $resultDate)
                ->orderByDesc('r2.time_in_seconds')
                ->select('r2.player_id', 'r2.time_in_seconds', 'r2.pigeon_time')
                ->first();
        });

        $firstWinnerPlayer = $tournament->players->where('id', $firstWinner->player_id ?? 0)->first();
        $lastWinnerPlayer = $tournament->players->where('id', $lastWinner->player_id ?? 0)->first();
        $firstWinnerPlayerName = $firstWinnerPlayer->name ?? '';
        $lastWinnerPlayerName = $lastWinnerPlayer->name ?? '';
        $firstWinnerPigeonTime = $firstWinner->pigeon_time ?? '';
        $lastWinnerPigeonTime = $lastWinner->pigeon_time ?? '';


        return "<div class='alert alert-info pigeon-winner-alert' role='alert'><strong>First Winner : <strong>{$firstWinnerPlayerName}</strong> &nbsp;&nbsp;&nbsp;&nbsp; Time <span href='#' class='custom_span'>{$firstWinnerPigeonTime}</span><strong></strong></strong></div>
                <div class='alert alert-info pigeon-winner-alert' role='alert'><strong>Last Winner : <strong>{$lastWinnerPlayerName}</strong> &nbsp;&nbsp;&nbsp;&nbsp; Time <span href='#' class='custom_span'>{$lastWinnerPigeonTime}</span><strong></strong></strong></div>
        ";
    } catch (\Throwable $th) {
        throw $th;
        return "<div class='alert alert-info pigeon-winner-alert' role='alert'><strong>First Winner : <strong></strong></strong></div>
                <div class='alert alert-info pigeon-winner-alert' role='alert'><strong>Last Winner : <strong></strong></strong></div>";
    }
}

function getStoragePrefix()
{
    return parse_url(config('app.url'), PHP_URL_HOST);
}
