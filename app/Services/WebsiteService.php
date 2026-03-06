<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Admin\Club;
use App\Models\Admin\News;
use App\Models\Admin\Result;
use App\Models\Admin\Slider;
use Litespeed\LSCache\LSCache;
use App\Models\Admin\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;

class WebsiteService
{

    public function getAllActiveClubs(): Collection
    {
        return Club::where('status', true)
        ->where('id', '!=', 1) 
        ->orderBy('sort')->get();
    }

    public function getActiveTournamentForWebsite(): Collection
    {
        return Tournament::where('show', true)->where('public_hide', false)->orderBy('sort')->get();
    }
    public function getActiveNews()
    {
        return  News::where('show', true)->orderBy('created_at', 'ASC')->get();
    }
    public function getAllSliders()
    {
        return Slider::get();
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

    public static function flushCache($tournament_id=null,$date=null,$club_id=null): void
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

            $paths = array_map(function($url) {
                $path = str_replace(url('/'), '', $url);
                return empty($path) ? '/' : $path;
            }, $routes);

            try {
                // Stale while revalidate purge in LiteSpeed exclusively for the URIs that changed
                LSCache::purgeItems($paths, true); // true sets the "stale," prefix
            } catch (\Exception $e) {
                Cache::flush();
                LSCache::purgeAll();
            }

            self::storeUrlsForCacheClearing($routes);
        } else {
            Cache::flush();
            LSCache::purgeAll();
            opcache_reset();
            Cache::store('remember_forever_cache_store')->flush();
            
        }
    }
    
    /**
     * Store URLs in Redis for cache clearing
     * This method handles duplicates and stores URLs without Laravel's prefix
     * 
     * @param array $urls Array of URLs to be cached for clearing
     * @param string $redisKey The Redis key to store URLs (default: 'cache_clear_urls')
     * @return int Number of URLs actually added (excluding duplicates)
     */
    public static function storeUrlsForCacheClearing(array $urls, string $redisKey = 'cache_clear_urls')
    {
        if (empty($urls)) {
            return 0;
        }
        Redis::connection('central_keys')->sadd($redisKey,...$urls);
        
    }
}
