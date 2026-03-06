<?php

namespace App\Services;

use App\Models\Admin\Club;
use App\Models\Admin\News;
use App\Models\Admin\Result;
use App\Models\Admin\Slider;
use App\Models\Admin\Tournament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Litespeed\LSCache\LSCache;

class WebsiteService
{
    public function getAllActiveClubs(): Collection
    {
        return Club::where('status', true)
            ->where('id', '!=', 1)
            ->orderBy('sort')->get();
    }

    public function getAllInActiveClubs(): Collection
    {
        return Club::where('status', false)->get();
    }

    public function getActiveTournamentForWebsite(): Collection
    {
        return Tournament::where('show', true)->where('public_hide', false)->orderBy('sort')->get();
    }

    public function getAllTournamentsOfThisClub($club)
    {
        return Tournament::with('flyingDays')
            ->with('players')
            ->where('club_id', $club->id)
            ->where('public_hide', false)
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getActiveNews()
    {
        return News::where('show', true)->orderBy('created_at', 'ASC')->get();
    }

    public function getAllSliders()
    {
        return Slider::get();
    }

    public function getFirstActiveTournamentForIndex()
    {
        $tournament = Tournament::with('flyingDays')->where('show', true)->first();
        if ($tournament === null) {
            return Tournament::with('flyingDays')->latest()->first();
        }

        return $tournament;
    }

    public function getTournamentResultByDateForIndex(Tournament $tournament, $date)
    {
        return Tournament::where('id', $tournament->id)->with(['tournamentResult' => function ($query) use ($date) {
            $query->where('date', $date);
        }])->first();
    }

    public function getSortedResultByDate($tournament, $date)
    {
        return DB::query()
            ->from('results')
            ->selectRaw('player_id as player_id, SUM(pigeon_total) as total, tournament_id as tournament , date as date')
            ->where('tournament_id', $tournament->id)
            ->where('date', $date)
            ->groupBy('player_id')
            ->orderBy('total', 'desc')->get();
    }

    public function getTournamentTotal($tournament)
    {
        return DB::query()
            ->from('player_tournament_total')
            ->selectRaw('player_id as player_id, SUM(total) as total, tournament_id as tournament')
            ->where('tournament_id', $tournament->id)
            ->groupBy('player_id')
            ->orderBy('total', 'desc')
            ->get();
    }

    public function getTournamentTotalByDays($tournament)
    {
        return DB::table('player_tournament_total')->where('tournament_id', $tournament->id)->get()->groupBy('player_id');
    }

    public function getAllClubTournamentsWithPrizes($tournaments)
    {
        $data = [];
        foreach ($tournaments as $tournament) {
            $prizes = $tournament->tournamentPrize->toArray();
            $players = $tournament->players;
            $sortedPlayers = collect($this->getTournamentTotal($tournament));
            $sortedPlayerByPosition = $sortedPlayers->slice(0, count($prizes));
            foreach ($sortedPlayerByPosition as $key => $sortedPlayer) {
                $player = $players->where('id', $sortedPlayer->player_id)->first();
                $data[$tournament->id][$player->id] = [$player->name, $player->city, $sortedPlayer->total, $prizes[$key]['name']];
            }
        }

        return collect($data);
    }

    public static function flushCache($tournament_id = null, $date = null, $club_id = null)
    {
        if ($tournament_id && $date && $club_id) {

            // Clear specific Laravel backend caches so background builder doesn't fetch old DB results
            Cache::forget('firstActiveTournament');
            Cache::forget('shortPigeons-'.$tournament_id.'-'.$date);
            Cache::store('remember_forever_cache_store')->forget('tournament-date-result-'.$tournament_id);

            $routes = [];

            // Home page
            $routes[] = route('root');

            // Club index page
            $routes[] = route('result.club', ['club' => $club_id]);
            $routes[] = route('result.club', ['club' => 'default']);

            // Tournament load pages
            $routes[] = route('result.tournament', ['club_id' => $club_id, 'tournament_id' => $tournament_id]);
            $routes[] = route('result.tournament', ['club_id' => 'default', 'tournament_id' => $tournament_id]);

            // Tournament specific date pages
            $routes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => $date]);
            $routes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => $date]);

            // Tournament total pages
            $routes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => 'total']);
            $routes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => 'total']);

            $paths = array_map(function ($url) {
                $path = str_replace(url('/'), '', $url);

                return empty($path) ? '/' : $path;
            }, $routes);

            try {
                // Purge by tags instead of specific URIs to ensure LiteSpeed catches all variations
                LSCache::purgeTags(['home', 'club', 'tournament', 'date'], true);
            } catch (\Exception $e) {
                Cache::flush();
                LSCache::purgeAll();
            }

            self::storeUrlsForCacheClearing($routes);
        } else {
            Cache::flush();
            try {
                LSCache::purgeAll();
            } catch (\Exception $e) {
            }
            opcache_reset();
            Cache::store('remember_forever_cache_store')->flush();
        }
    }

    public function getPreviousDay($tournament, $resultDate)
    {

        try {
            return $tournament->flyingDays->filter(fn ($item) => $item->date < $resultDate)
                ->sortByDesc('date')
                ->first();
        } catch (\Throwable $th) {
            dump($th->getMessage());
        }

    }

    public function getPreviousDayShortPigeons($tournament, $previousDay)
    {
        try {
            if (empty($previousDay) || $tournament->type === 'OPEN') {
                return null;
            }

            return Cache::remember('shortPigeons-'.$tournament->id.'-'.$previousDay->date, now()->addMinutes(60), function () use ($tournament, $previousDay) {
                return Result::where(function ($query) {
                    $query->whereNull('pigeon_time')
                        ->orWhere('pigeon_time', '00:00:00');
                })
                    ->where('date', $previousDay->date)
                    ->where('tournament_id', $tournament->id)
                    ->get();

            });
        } catch (\Throwable $th) {
            dump($th->getMessage());
        }

    }

    /**
     * Store URLs in Redis for cache clearing
     * This method handles duplicates and stores URLs without Laravel's prefix
     *
     * @param  array  $urls  Array of URLs to be cached for clearing
     * @param  string  $redisKey  The Redis key to store URLs (default: 'cache_clear_urls')
     * @return int|void Number of URLs actually added (excluding duplicates)
     */
    public static function storeUrlsForCacheClearing(array $urls, string $redisKey = 'cache_clear_urls')
    {
        if (empty($urls)) {
            return 0;
        }
        Redis::connection('central_keys')->sadd($redisKey, ...$urls);

    }
}
