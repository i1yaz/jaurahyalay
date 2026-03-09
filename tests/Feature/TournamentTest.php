<?php

namespace Tests\Feature;

use App\Models\Admin\Club;
use App\Models\Admin\Player;
use App\Models\Admin\Result;
use App\Models\Admin\Tournament;
use App\Models\Admin\TournamentFlyingDay;
use App\Services\ResultService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure auto_update_time setting exists (migration might have already inserted it)
        \App\Models\Admin\Setting::updateOrCreate(
            ['key' => 'auto_update_time'],
            ['value' => '0', 'group_type' => 'auto_update_time']
        );
    }

    /**
     * Requirement 1.1: Create Club.
     */
    public function test_can_create_club()
    {
        $club = Club::factory()->create([
            'name' => 'Test Pigeon Club',
            'city' => 'Lahore',
        ]);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Test Pigeon Club',
            'city' => 'Lahore',
        ]);
    }

    /**
     * Requirement 1.2: Create Players.
     */
    public function test_can_create_players_and_associate_with_club()
    {
        $club = Club::factory()->create();

        $player1 = Player::factory()->create(['club_id' => $club->id, 'name' => 'Ustad Asjad Chaddar']);
        $player2 = Player::factory()->create(['club_id' => $club->id, 'name' => 'Bali Jatt Rukan Pur']);

        $this->assertEquals($club->id, $player1->fresh()->club_id);
        $this->assertEquals($club->id, $player2->fresh()->club_id);
    }

    /**
     * Requirement 2.1: Create a Regular Multi-Day Tournament.
     */
    public function test_create_regular_multi_day_tournament()
    {
        $club = Club::factory()->create();
        $players = Player::factory()->count(2)->create(['club_id' => $club->id]);

        $tournament = Tournament::factory()->create([
            'club_id' => $club->id,
            'days' => 5,
            'pigeons' => 7,
            'supporter' => 0,
            'start_date' => now()->format('Y-m-d'),
        ]);

        $tournament->players()->attach($players->pluck('id'));

        for ($i = 0; $i < 5; $i++) {
            TournamentFlyingDay::factory()->create([
                'tournament_id' => $tournament->id,
                'date' => now()->addDays($i)->format('Y-m-d'),
            ]);
        }

        $this->assertEquals(5, $tournament->flyingDays()->count());
        $this->assertEquals(7, $tournament->pigeons);
        $this->assertCount(2, $tournament->players);
    }

    /**
     * Requirement 2.2: Create a Supporter Multi-Day Tournament.
     */
    public function test_create_supporter_multi_day_tournament()
    {
        $club = Club::factory()->create();

        $tournament = Tournament::factory()->create([
            'club_id' => $club->id,
            'days' => 3,
            'pigeons' => 8,
            'supporter' => 1,
        ]);

        $this->assertEquals(8, $tournament->pigeons);
        $this->assertEquals(1, $tournament->supporter);
    }

    /**
     * Requirement 3.1: Calculate Standard Single-Day Total.
     */
    public function test_calculate_standard_single_day_total()
    {
        $tournament = Tournament::factory()->create(['pigeons' => 7, 'supporter' => 0]);
        $player = Player::factory()->create();
        $date = now()->format('Y-m-d');
        $service = new ResultService;
        $startTime = '060000';

        for ($i = 1; $i <= 7; $i++) {
            $time = str_pad(10 + $i, 2, '0', STR_PAD_LEFT).'0000';

            $mockRequest = new \Illuminate\Http\Request([
                'pk' => "{$tournament->id}_{$date}_{$player->id}_{$i}_{$tournament->club_id}",
                'name' => 'pigeon',
                'value' => $time,
            ]);

            if ($i == 1) {
                Result::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'date' => $date,
                    'pigeon_number' => 1,
                    'start_time' => '060000',
                ]);
            }

            $service->updatePlayerTime($mockRequest);
        }

        $record = DB::table('player_tournament_total')
            ->where('tournament_id', $tournament->id)
            ->where('date', $date)
            ->where('player_id', $player->id)
            ->first();

        $this->assertNotNull($record, 'player_tournament_total record not found');
        $this->assertEquals(7, $record->landed);

        $expectedTotal = 0;
        for ($i = 1; $i <= 7; $i++) {
            $time = str_pad(10 + $i, 2, '0', STR_PAD_LEFT).'0000';
            $expectedTotal += Carbon::parse('060000')->diffInSeconds(Carbon::parse($time));
        }

        $this->assertEquals($expectedTotal, $record->total);
    }

    /**
     * Requirement 3.2: Change Start Time and Recalculate (Single Day).
     */
    public function test_change_start_time_and_recalculate()
    {
        $tournament = Tournament::factory()->create(['pigeons' => 7, 'supporter' => 0]);
        $player = Player::factory()->create();
        $date = now()->format('Y-m-d');
        $service = new ResultService;

        // 1. Initial setup with 06:00 AM
        $startTime = '060000';
        Result::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'date' => $date,
            'pigeon_number' => 1,
            'start_time' => $startTime,
            'pigeon_time' => '100000',
            'pigeon_total' => 14400,
            'time_in_seconds' => 14400,
        ]);

        // 2. Change start time to 07:38 AM
        $mockRequest = new \Illuminate\Http\Request([
            'pk' => "{$tournament->id}_{$date}_{$player->id}_{$tournament->club_id}",
            'name' => 'start',
            'value' => '07:38:00',
        ]);

        $service->updatePlayerTime($mockRequest);

        $result = Result::where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->where('date', $date)
            ->where('pigeon_number', 1)
            ->first();

        $this->assertNotNull($result, 'Result record not found after start time change');
        $expectedSeconds = Carbon::parse('073800')->diffInSeconds(Carbon::parse('100000'));
        $this->assertEquals($expectedSeconds, $result->pigeon_total);
    }

    /**
     * Requirement 4.1: Calculate Single-Day Total (All Pigeons Return - Drop Lowest).
     */
    public function test_supporter_tournament_drops_lowest_when_all_return()
    {
        $tournament = Tournament::factory()->create(['pigeons' => 8, 'supporter' => 1]);
        $player = Player::factory()->create();
        $date = now()->format('Y-m-d');
        $service = new ResultService;
        $startTime = '060000';

        for ($i = 1; $i <= 8; $i++) {
            $time = str_pad(9 + $i, 2, '0', STR_PAD_LEFT).'0000';

            $mockRequest = new \Illuminate\Http\Request([
                'pk' => "{$tournament->id}_{$date}_{$player->id}_{$i}_{$tournament->club_id}",
                'name' => 'pigeon',
                'value' => $time,
            ]);

            if ($i == 1) {
                Result::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'date' => $date,
                    'pigeon_number' => 1,
                    'start_time' => $startTime,
                ]);
            }
            $service->updatePlayerTime($mockRequest);
        }

        $record = DB::table('player_tournament_total')
            ->where('tournament_id', $tournament->id)
            ->where('date', $date)
            ->where('player_id', $player->id)
            ->first();

        $this->assertNotNull($record, 'player_tournament_total record not found in supporter test');

        $droppedPigeon = Result::where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->where('date', $date)
            ->orderBy('pigeon_total', 'asc')
            ->first();

        $this->assertEquals(0, $droppedPigeon->pigeon_total, 'Lowest pigeon total was not set to 0');

        $allResults = Result::where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->where('date', $date)
            ->pluck('pigeon_total');

        $this->assertEquals($allResults->sum(), $record->total);
    }

    /**
     * Requirement 4.2: Calculate Single-Day Total (Pigeon Lost - Keep All).
     */
    public function test_supporter_tournament_keeps_all_when_one_lost()
    {
        $tournament = Tournament::factory()->create(['pigeons' => 8, 'supporter' => 1]);
        $player = Player::factory()->create();
        $date = now()->format('Y-m-d');
        $service = new ResultService;
        $startTime = '060000';

        // Only 7 pigeons return (8-1=7, which is <= 7 allowed for keeping all)
        for ($i = 1; $i <= 7; $i++) {
            $time = str_pad(9 + $i, 2, '0', STR_PAD_LEFT).'0000';

            $mockRequest = new \Illuminate\Http\Request([
                'pk' => "{$tournament->id}_{$date}_{$player->id}_{$i}_{$tournament->club_id}",
                'name' => 'pigeon',
                'value' => $time,
            ]);
            if ($i == 1) {
                Result::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'date' => $date,
                    'pigeon_number' => 1,
                    'start_time' => $startTime,
                ]);
            }
            $service->updatePlayerTime($mockRequest);
        }

        $record = DB::table('player_tournament_total')
            ->where('tournament_id', $tournament->id)
            ->where('date', $date)
            ->where('player_id', $player->id)
            ->first();

        $this->assertNotNull($record, 'player_tournament_total record not found in lost pigeon test');

        $minTotal = Result::where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->where('date', $date)
            ->whereNotNull('pigeon_time')
            ->min('pigeon_total');

        $this->assertGreaterThan(0, $minTotal, 'A pigeon was incorrectly dropped (total set to 0)');
    }

    /**
     * Requirement 5: Multi-Day Total Management.
     */
    public function test_multi_day_total_calculation()
    {
        $tournament = Tournament::factory()->create(['days' => 2, 'pigeons' => 7, 'supporter' => 0]);
        $player = Player::factory()->create();
        $service = new ResultService;

        $day1 = Carbon::now()->format('Y-m-d');
        $day2 = Carbon::now()->addDay()->format('Y-m-d');

        // Day 1: 2 hours total
        Result::create([
            'tournament_id' => $tournament->id, 'player_id' => $player->id, 'date' => $day1,
            'pigeon_number' => 1, 'start_time' => '060000', 'pigeon_time' => '080000',
            'pigeon_total' => 7200, 'time_in_seconds' => 7200,
        ]);

        // Trigger total calculation for Day 1
        $mockRequest = new \Illuminate\Http\Request([
            'pk' => "{$tournament->id}_{$day1}_{$player->id}_1_{$tournament->club_id}",
            'name' => 'pigeon',
            'value' => '080000',
        ]);
        $service->updatePlayerTime($mockRequest);

        // Day 2: 3 hours total
        Result::create([
            'tournament_id' => $tournament->id, 'player_id' => $player->id, 'date' => $day2,
            'pigeon_number' => 1, 'start_time' => '060000', 'pigeon_time' => '090000',
            'pigeon_total' => 10800, 'time_in_seconds' => 10800,
        ]);

        $mockRequest2 = new \Illuminate\Http\Request([
            'pk' => "{$tournament->id}_{$day2}_{$player->id}_1_{$tournament->club_id}",
            'name' => 'pigeon',
            'value' => '090000',
        ]);
        $service->updatePlayerTime($mockRequest2);

        $day1Record = DB::table('player_tournament_total')
            ->where(['tournament_id' => $tournament->id, 'date' => $day1, 'player_id' => $player->id])
            ->first();

        $day2Record = DB::table('player_tournament_total')
            ->where(['tournament_id' => $tournament->id, 'date' => $day2, 'player_id' => $player->id])
            ->first();

        $this->assertEquals(7200, $day1Record->total);
        $this->assertEquals(10800, $day2Record->total);

        // The Grand Total is usually handled by a query in TournamentService or similar
        // Let's verify we can sum them
        $grandTotal = DB::table('player_tournament_total')
            ->where(['tournament_id' => $tournament->id, 'player_id' => $player->id])
            ->sum('total');

        $this->assertEquals(18000, $grandTotal);
    }

    /**
     * Requirement 4.3: Change Start Time and Recalculate (Supporter).
     */
    public function test_change_start_time_and_recalculate_supporter()
    {
        $tournament = Tournament::factory()->create(['pigeons' => 8, 'supporter' => 1]);
        $player = Player::factory()->create();
        $date = now()->format('Y-m-d');
        $service = new ResultService();

        // 1. Initial setup with 06:00 AM and 8 pigeons
        $startTime = '060000';
        for ($i = 1; $i <= 8; $i++) {
            $time = str_pad(10 + $i, 2, '0', STR_PAD_LEFT) . '0000';
            Result::create([
                'tournament_id' => $tournament->id, 'player_id' => $player->id, 'date' => $date,
                'pigeon_number' => $i, 'start_time' => $startTime, 'pigeon_time' => $time
            ]);
        }
        
        // Trigger calculation (which drops the lowest)
        $mockRequestPigeon = new \Illuminate\Http\Request([
            'pk' => "{$tournament->id}_{$date}_{$player->id}_8_{$tournament->club_id}",
            'name' => 'pigeon',
            'value' => '180000'
        ]);
        $service->updatePlayerTime($mockRequestPigeon);

        // 2. Change start time to 07:00 AM
        $mockRequestStart = new \Illuminate\Http\Request([
            'pk' => "{$tournament->id}_{$date}_{$player->id}_{$tournament->club_id}",
            'name' => 'start',
            'value' => '07:00:00'
        ]);
        
        $service->updatePlayerTime($mockRequestStart);

        $record = DB::table('player_tournament_total')
            ->where(['tournament_id' => $tournament->id, 'date' => $date, 'player_id' => $player->id])
            ->first();

        $pigeonTotals = Result::where(['tournament_id' => $tournament->id, 'date' => $date, 'player_id' => $player->id])
            ->pluck('pigeon_total');
            
        $this->assertEquals($pigeonTotals->sum(), $record->total);
        $this->assertContains(0, $pigeonTotals->toArray());
    }
}
