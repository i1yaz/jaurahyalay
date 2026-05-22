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

    public function getActiveTournamentForWebsite($nav = false): Collection
    {
        if ($nav === true) {
            return Tournament::select(['id', 'name', 'club_id'])->where('start_date', '>=', now()->subDays(365)->toDateString())->where('public_hide', false)->orderBy('sort')->get();
        }
        return Tournament::select(['id', 'name', 'club_id'])->where('show', true)->where('public_hide', false)->orderBy('sort')->get();
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

    public function getTournamentDoubleStampTotal($tournament)
    {
        return DB::table('player_tournament_total')
            ->selectRaw('player_id as player_id, SUM(double_stamp_total) as total, tournament_id as tournament')
            ->where('tournament_id', $tournament->id)
            ->groupBy('player_id')
            ->orderBy('total', 'desc')
            ->get();
    }

    public function getTournamentDoubleStampTotalByDays($tournament)
    {
        return DB::table('player_tournament_total')
            ->where('tournament_id', $tournament->id)
            ->get()->groupBy('player_id');
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
                $data[$tournament->id][$player->id] = [$player->name, $player->city, $sortedPlayer->total, $prizes[$key]['name'], $player->poster];
            }
        }

        return collect($data);
    }

    public function flushCache($tournament_id = null, $date = null, $club_id = null)
    {
        if ($tournament_id && $date && $club_id) {
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

            // Double stamp total pages
            $routes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => 'double-stamp-total']);
            $routes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => 'double-stamp-total']);

            $paths = array_map(function ($url) {
                $path = str_replace(url('/'), '', $url);

                return empty($path) ? '/' : $path;
            }, $routes);

            try {
                $firstActiveTournament = Cache::remember('firstActiveTournament', now()->addMinutes(60), function () {
                    return $this->getFirstActiveTournamentForIndex();
                });

                $tags = [
                    'club-' . $club_id,
                    'club-default',
                    'tournament-' . $tournament_id,
                    'tournament-' . $tournament_id . '-date-' . $date,
                    'tournament-' . $tournament_id . '-date-total',
                    'tournament-' . $tournament_id . '-date-double-stamp-total',
                ];

                if ($firstActiveTournament->id == $tournament_id) {
                    $tags[] = 'home';
                }

                LSCache::purgeTags($tags, true);
            } catch (\Exception $e) {
                Cache::flush();
                LSCache::purgeAll();
            }

            $this->storeUrlsForCacheClearing($routes);
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

    public function flushCacheBatch(array $flushParams)
    {
        if (empty($flushParams)) {
            return;
        }

        $allRoutes = [];
        $allTags = [];
        $firstActiveTournament = null;

        try {
            $firstActiveTournament = Cache::remember('firstActiveTournament', now()->addMinutes(60), function () {
                return $this->getFirstActiveTournamentForIndex();
            });
        } catch (\Exception $e) {
            // Ignore error here, we'll try to flush everything if LSCache fails later
        }

        foreach ($flushParams as $params) {
            $tournament_id = $params['tournament_id'] ?? null;
            $date = $params['date'] ?? null;
            $club_id = $params['club_id'] ?? null;

            if (!$tournament_id || !$date || !$club_id) {
                continue;
            }

            // Club index page
            $allRoutes[] = route('result.club', ['club' => $club_id]);
            $allRoutes[] = route('result.club', ['club' => 'default']);

            // Tournament load pages
            $allRoutes[] = route('result.tournament', ['club_id' => $club_id, 'tournament_id' => $tournament_id]);
            $allRoutes[] = route('result.tournament', ['club_id' => 'default', 'tournament_id' => $tournament_id]);

            // Tournament specific date pages
            $allRoutes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => $date]);
            $allRoutes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => $date]);

            // Tournament total pages
            $allRoutes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => 'total']);
            $allRoutes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => 'total']);

            // Double stamp total pages
            $allRoutes[] = route('result.tournament.date', ['club' => $club_id, 'tournament' => $tournament_id, 'date' => 'double-stamp-total']);
            $allRoutes[] = route('result.tournament.date', ['club' => 'default', 'tournament' => $tournament_id, 'date' => 'double-stamp-total']);

            $allTags[] = 'club-' . $club_id;
            $allTags[] = 'club-default';
            $allTags[] = 'tournament-' . $tournament_id;
            $allTags[] = 'tournament-' . $tournament_id . '-date-' . $date;
            $allTags[] = 'tournament-' . $tournament_id . '-date-total';
            $allTags[] = 'tournament-' . $tournament_id . '-date-double-stamp-total';

            if ($firstActiveTournament && $firstActiveTournament->id == $tournament_id) {
                // Home page
                $allTags[] = 'home';
                $allRoutes[] = route('root');
            }
        }

        $routes = array_unique($allRoutes);
        $tags = array_values(array_unique($allTags));

        if (empty($routes) || empty($tags)) {
            return;
        }

        try {
            LSCache::purgeTags($tags, true);
        } catch (\Exception $e) {
            Cache::flush();
            LSCache::purgeAll();
        }

        $this->storeUrlsForCacheClearing($routes);
    }

    public function getPreviousDay($tournament, $resultDate)
    {

        try {
            return $tournament->flyingDays->filter(fn($item) => $item->date < $resultDate)
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

            return Cache::remember('shortPigeons-' . $tournament->id . '-' . $previousDay->date, now()->addMinutes(60), function () use ($tournament, $previousDay) {
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
    public function storeUrlsForCacheClearing(array $urls, string $redisKey = 'cache_clear_urls')
    {
        if (empty($urls)) {
            return 0;
        }
        Redis::connection('central_keys')->sadd($redisKey, ...$urls);
    }
}
