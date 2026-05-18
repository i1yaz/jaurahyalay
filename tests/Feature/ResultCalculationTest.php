<?php

namespace Tests\Feature;

use App\Models\Admin\Club;
use App\Models\Admin\Player;
use App\Models\Admin\Result;
use App\Models\Admin\Setting;
use App\Models\Admin\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResultCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure auto_update_time setting exists
        Setting::updateOrCreate(
            ['key' => 'auto_update_time'],
            ['value' => '0', 'group_type' => 'auto_update_time']
        );

        // Create an admin user who can edit any tournament
        $this->adminUser = User::factory()->create([
            'super_admin' => 1,
        ]);
    }

    /**
     * Requirement A: Standard Tournament (Supporter = 0)
     */
    public function test_standard_tournament_pigeon_time_add_and_update()
    {
        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['pigeons' => 2, 'supporter' => 0, 'club_id' => $club->id, 'status' => 1]);
        $player = Player::factory()->create(['club_id' => $club->id]);
        $tournament->players()->attach($player->id);
        $date = now()->format('Y-m-d');

        // Initial state: Start time 06:00:00 setup by posting to name 'start'
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_{$club->id}",
            'name' => 'start',
            'value' => '06:00:00',
        ])->assertSuccessful();

        // 1. Adding Initial Pigeon Time
        // Add Pigeon 1 at 10:00:00
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_1_{$club->id}",
            'name' => 'pigeon',
            'value' => '10:00:00',
        ])->assertSuccessful();

        // Verify pigeon 1 is 4 hours (14400 seconds)
        $pigeon1 = Result::where(['player_id' => $player->id, 'pigeon_number' => 1])->first();
        $this->assertEquals(14400, $pigeon1->time_in_seconds);
        $this->assertEquals(14400, $pigeon1->pigeon_total);
        $this->assertEquals(14400, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));

        // Add Pigeon 2 at 11:00:00
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_2_{$club->id}",
            'name' => 'pigeon',
            'value' => '11:00:00',
        ])->assertSuccessful();

        // Verify pigeon 2 is 5 hours (18000), total is 9 hours (32400)
        $this->assertEquals(18000, Result::where(['player_id' => $player->id, 'pigeon_number' => 2])->value('time_in_seconds'));
        $this->assertEquals(32400, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));

        // 2. Updating Existing Pigeon Time
        // Change Pigeon 1 to 09:00:00 (3 hours - 10800)
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_1_{$club->id}",
            'name' => 'pigeon',
            'value' => '09:00:00',
        ])->assertSuccessful();

        $this->assertEquals(10800, Result::where(['player_id' => $player->id, 'pigeon_number' => 1])->value('time_in_seconds'));
        $this->assertEquals(28800, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total')); // 3hr + 5hr = 8hr

        // 3. Updating Start Time
        // Update Start Time to 07:00:00
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_{$club->id}",
            'name' => 'start',
            'value' => '07:00:00',
        ])->assertSuccessful();

        // Verify Pigeon 1 (09:00) becomes 2 hours (7200), Pigeon 2 (11:00) becomes 4 hours (14400). Total = 21600 (6 hours)
        $this->assertEquals(7200, Result::where(['player_id' => $player->id, 'pigeon_number' => 1])->value('time_in_seconds'));
        $this->assertEquals(14400, Result::where(['player_id' => $player->id, 'pigeon_number' => 2])->value('time_in_seconds'));
        $this->assertEquals(21600, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));

        // 4. Bulk Updating Start Time via updateResult
        // Assuming admin.result.update handles POST/PATCH (Actually PATCH in routes)
        $this->actingAs($this->adminUser)->patch(route('admin.result.update'), [
            'tournament_id' => $tournament->id,
            'club_id' => $club->id,
            'value' => $date,
        ])->assertRedirect();

        // Should recalculate and remain 21600
        $this->assertEquals(21600, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));
    }

    /**
     * Requirement B: Supporter Tournament (Pigeons: 7, Supporter: 1 => Target: 6)
     */
    public function test_supporter_tournament_calculations()
    {
        $this->withoutExceptionHandling();

        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['pigeons' => 7, 'supporter' => 1, 'club_id' => $club->id, 'status' => 1]);
        $player = Player::factory()->create(['club_id' => $club->id]);
        $tournament->players()->attach($player->id);
        $date = now()->format('Y-m-d');

        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_{$club->id}",
            'name' => 'start',
            'value' => '06:00:00',
        ]);

        // 1. All Pigeons Return. Flight durations: 2, 3, 4, 5, 6, 7, 8 hours.
        // Arrival times: 08:00, 09:00, 10:00, 11:00, 12:00, 13:00, 14:00
        for ($i = 1; $i <= 7; $i++) {
            $time = str_pad(7 + $i, 2, '0', STR_PAD_LEFT).':00:00';
            $this->actingAs($this->adminUser)->postJson(route('result.time'), [
                'pk' => "{$tournament->id}_{$date}_{$player->id}_{$i}_{$club->id}",
                'name' => 'pigeon',
                'value' => $time,
            ]);
        }

        // Assertion: The 2hr pigeon (lowest) MUST have pigeon_total = 0, but time_in_seconds is still 7200
        $pigeon1 = Result::where(['player_id' => $player->id, 'pigeon_number' => 1])->first();
        $this->assertEquals(7200, $pigeon1->time_in_seconds); // Original true flight time
        $this->assertEquals(0, $pigeon1->pigeon_total); // Supporter dropped it from calculation

        // Assertion: Total must be 3+4+5+6+7+8 = 33 hours (118800)
        $this->assertEquals(118800, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));

        // 2. Dynamic Resorting: Change an 8hr pigeon (Pigeon 7) to 1hr (07:00:00)
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_7_{$club->id}",
            'name' => 'pigeon',
            'value' => '07:00:00',
        ]);

        // Pigeon 7 (now 1hr) becomes lowest, so it drops to 0.
        $pigeon7 = Result::where(['player_id' => $player->id, 'pigeon_number' => 7])->first();
        $this->assertEquals(0, $pigeon7->pigeon_total);
        $this->assertEquals(3600, $pigeon7->time_in_seconds);

        // Pigeon 1 (2hr) is restored to 7200 in pigeon_total
        $pigeon1->refresh();
        $this->assertEquals(7200, $pigeon1->pigeon_total);

        // Total is now 2hr + 3hr + 4hr + 5hr + 6hr + 7hr = 27 hours (97200)
        $this->assertEquals(97200, DB::table('player_tournament_total')->where(['player_id' => $player->id])->value('total'));

        // 3. Not All Pigeons Return (Landed = 6)
        // Clear Pigeon 7 (simulating it was deleted/lost - our system doesn't formally 'delete', but it usually updates to 00:00:00 or similar if lost)
        // Let's delete the result entirely to simulate 'lost'
        $pigeon7->delete();
        // Since we bypassed normal HTTP for deletion, manually run calculation. Wait, system uses update to empty?
        // Usually, in X-editable, setting empty updates value to '' or '0'
        $res = $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$date}_{$player->id}_7_{$club->id}",
            'name' => 'pigeon',
            'value' => '', // or 0
        ]);

        // Now there are 6 valid pigeons. None should be dropped!
        $pigeons = Result::where(['player_id' => $player->id])->where('time_in_seconds', '>', 0)->get();
        $this->assertCount(6, $pigeons);
        $this->assertEquals(0, $pigeons->where('pigeon_total', 0)->count()); // None are dropped!
    }

    /**
     * Requirement C: Auto-Update Setting Override
     */
    public function test_auto_update_setting_override()
    {
        $club = Club::factory()->create();
        $date = now()->format('Y-m-d');
        $player = Player::factory()->create(['club_id' => $club->id]);

        $tournament1 = Tournament::factory()->create(['pigeons' => 2, 'club_id' => $club->id, 'status' => 1]);
        $tournament2 = Tournament::factory()->create(['pigeons' => 2, 'club_id' => $club->id, 'status' => 1]);
        $tournament1->players()->attach($player->id);
        $tournament2->players()->attach($player->id);

        // Explicitly create flying days so auto-update JOIN works
        \App\Models\Admin\TournamentFlyingDay::factory()->create(['tournament_id' => $tournament1->id, 'date' => $date]);
        \App\Models\Admin\TournamentFlyingDay::factory()->create(['tournament_id' => $tournament2->id, 'date' => $date]);

        // Turn ON Auto Update
        Setting::where('key', 'auto_update_time')->update(['value' => '1']);

        // Set Start Time in T1
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament1->id}_{$date}_{$player->id}_{$club->id}",
            'name' => 'start',
            'value' => '06:00:00',
        ]);
        // T2 Start Time should auto-update!
        $this->assertEquals('06:00:00', Result::where(['tournament_id' => $tournament2->id])->value('start_time'));

        // Add Pigeon 1 in T1
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament1->id}_{$date}_{$player->id}_1_{$club->id}",
            'name' => 'pigeon',
            'value' => '10:00:00',
        ]);

        // Verifying pigeon 1 in T2 auto-populated
        $this->assertEquals(14400, Result::where(['tournament_id' => $tournament2->id, 'pigeon_number' => 1])->value('pigeon_total'));
        $this->assertEquals(14400, DB::table('player_tournament_total')->where(['tournament_id' => $tournament2->id])->value('total'));
    }

    /**
     * Requirement D: Multi-Day Tournament Overarching Totals (Grand Total)
     */
    public function test_multi_day_overarching_totals()
    {
        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['pigeons' => 1, 'supporter' => 0, 'club_id' => $club->id, 'status' => 1]);
        $player = Player::factory()->create(['club_id' => $club->id]);
        $tournament->players()->attach($player->id);

        $day1 = now()->format('Y-m-d');
        $day2 = now()->addDay()->format('Y-m-d');

        // Day 1: 06:00 to 10:00 = 4 hours (14400)
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$day1}_{$player->id}_{$club->id}", 'name' => 'start', 'value' => '06:00:00',
        ]);
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$day1}_{$player->id}_1_{$club->id}", 'name' => 'pigeon', 'value' => '10:00:00',
        ]);

        // Day 2: 06:00 to 11:00 = 5 hours (18000)
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$day2}_{$player->id}_{$club->id}", 'name' => 'start', 'value' => '06:00:00',
        ]);
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$day2}_{$player->id}_1_{$club->id}", 'name' => 'pigeon', 'value' => '11:00:00',
        ]);

        // Verify Daily Totals
        $day1Total = DB::table('player_tournament_total')->where(['date' => $day1, 'player_id' => $player->id])->value('total');
        $day2Total = DB::table('player_tournament_total')->where(['date' => $day2, 'player_id' => $player->id])->value('total');

        $this->assertEquals(14400, $day1Total);
        $this->assertEquals(18000, $day2Total);

        // Verify Grand Total matching total_result.blade.php sum logic
        // This query exactly mirrors the $players->get($data->player_id)->sum('total') or sum variable accumulation in loops
        $grandTotal = DB::table('player_tournament_total')->where(['tournament_id' => $tournament->id, 'player_id' => $player->id])->sum('total');
        $this->assertEquals(32400, $grandTotal); // 4h + 5h = 9 hours exact

        // Correcting Past Day Mistake: Edit Day 1 Pigeon to 09:00 (3 hours, 10800)
        $this->actingAs($this->adminUser)->postJson(route('result.time'), [
            'pk' => "{$tournament->id}_{$day1}_{$player->id}_1_{$club->id}", 'name' => 'pigeon', 'value' => '09:00:00',
        ]);

        // Verifying Grand Total dynamically adjusts with exact seconds!
        $newGrandTotal = DB::table('player_tournament_total')->where(['tournament_id' => $tournament->id, 'player_id' => $player->id])->sum('total');
        $this->assertEquals(28800, $newGrandTotal); // 3h + 5h = 8 hours exact
    }
}
