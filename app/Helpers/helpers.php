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


        $firstProfilePic = '';
        if ($firstWinnerPlayer) {
            $picSrc = $firstWinnerPlayer->poster 
                ? asset('website/profiles/' . $firstWinnerPlayer->poster) 
                : (config('settings.profile_pic_type') === 'circle' 
                    ? asset('website/profiles/profile.png') 
                    : asset('website/profiles/profile-square.png'));
            
            $imgClass = config('settings.profile_pic_type') === 'circle' ? 'rounded-circle' : 'rounded';
            $firstProfilePic = "<img src='{$picSrc}' alt='{$firstWinnerPlayerName}' class='{$imgClass} mr-3' style='width: 50px; height: 50px; object-fit: cover;'>";
        }

        $lastProfilePic = '';
        if ($lastWinnerPlayer) {
            $picSrc = $lastWinnerPlayer->poster 
                ? asset('website/profiles/' . $lastWinnerPlayer->poster) 
                : (config('settings.profile_pic_type') === 'circle' 
                    ? asset('website/profiles/profile.png') 
                    : asset('website/profiles/profile-square.png'));
            
            $imgClass = config('settings.profile_pic_type') === 'circle' ? 'rounded-circle' : 'rounded';
            $lastProfilePic = "<img src='{$picSrc}' alt='{$lastWinnerPlayerName}' class='{$imgClass} mr-3' style='width: 50px; height: 50px; object-fit: cover;'>";
        }

        $cards = [];

        if ($firstWinnerPlayerName) {
            $cards[] = "<div class='col-6 mb-3'>
                <div class='card pigeon-winner-card first-winner-card h-100'>
                    <div class='card-header text-white font-weight-bold d-flex align-items-center' style='background-color: #EA5252; border: none; padding: 10px 15px;'>
                        <i class='fas fa-trophy mr-2 winner-icon'></i>First Winner
                    </div>
                    <div class='card-body d-flex align-items-center justify-content-between p-3'>
                        <div class='d-flex align-items-center'>
                            {$firstProfilePic}
                            <div>
                                <span class='winner-name font-weight-bold' style='font-size: 1.15rem; color: var(--text-dark);'>{$firstWinnerPlayerName}</span>
                            </div>
                        </div>
                        <div class='text-right'>
                            <span class='text-muted small d-block font-weight-bold'>TIME</span>
                            <span class='badge custom-time-badge'>{$firstWinnerPigeonTime}</span>
                        </div>
                    </div>
                </div>
            </div>";
        }

        if ($lastWinnerPlayerName) {
            $cards[] = "<div class='col-6 mb-3'>
                <div class='card pigeon-winner-card last-winner-card h-100'>
                    <div class='card-header text-white font-weight-bold d-flex align-items-center' style='background-color: #5B7E3C; border: none; padding: 10px 15px;'>
                        <i class='fas fa-award mr-2 winner-icon'></i>Last Winner
                    </div>
                    <div class='card-body d-flex align-items-center justify-content-between p-3'>
                        <div class='d-flex align-items-center'>
                            {$lastProfilePic}
                            <div>
                                <span class='winner-name font-weight-bold' style='font-size: 1.15rem; color: var(--text-dark);'>{$lastWinnerPlayerName}</span>
                            </div>
                        </div>
                        <div class='text-right'>
                            <span class='text-muted small d-block font-weight-bold'>TIME</span>
                            <span class='badge custom-time-badge'>{$lastWinnerPigeonTime}</span>
                        </div>
                    </div>
                </div>
            </div>";
        }

        if (empty($cards)) {
            return '';
        }

        // If only one card is active, let it span 12 columns
        if (count($cards) === 1) {
            $cards[0] = str_replace("col-6", "col-12", $cards[0]);
        }

        return "<div class='row winner-cards-wrapper'>" . implode('', $cards) . "</div>";
    } catch (\Throwable $th) {
        throw $th;
        return "<div class='row winner-cards-wrapper'>
                    <div class='col-6 mb-3'>
                        <div class='card pigeon-winner-card first-winner-card h-100'>
                            <div class='card-header text-white font-weight-bold d-flex align-items-center' style='background-color: #EA5252; border: none; padding: 10px 15px;'>
                                <i class='fas fa-trophy mr-2 winner-icon'></i>First Winner
                            </div>
                            <div class='card-body d-flex align-items-center justify-content-between p-3'>
                                <div class='d-flex align-items-center'>
                                    <span class='winner-name d-block'></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='col-6 mb-3'>
                        <div class='card pigeon-winner-card last-winner-card h-100'>
                            <div class='card-header text-white font-weight-bold d-flex align-items-center' style='background-color: #5B7E3C; border: none; padding: 10px 15px;'>
                                <i class='fas fa-award mr-2 winner-icon'></i>Last Winner
                            </div>
                            <div class='card-body d-flex align-items-center justify-content-between p-3'>
                                <div class='d-flex align-items-center'>
                                    <span class='winner-name d-block'></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>";
    }
}

function getStoragePrefix()
{
    return parse_url(config('app.url'), PHP_URL_HOST);
}
