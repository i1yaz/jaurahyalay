<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Club;
use App\Models\Admin\Player;
use App\Models\Admin\Result;
use App\Models\Admin\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateTournamentTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'super_admin' => 1,
        ]);

        \Illuminate\Support\Facades\Schema::dropIfExists('news');
        \Illuminate\Support\Facades\Schema::create('news', function ($table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('show')->default(1);
            $table->timestamps();
        });
        
        \Illuminate\Support\Facades\Schema::dropIfExists('sliders');
        \Illuminate\Support\Facades\Schema::create('sliders', function ($table) {
            $table->id();
            $table->boolean('show')->default(1);
            $table->timestamps();
        });
        
        \Illuminate\Support\Facades\Schema::dropIfExists('sponsors');
        \Illuminate\Support\Facades\Schema::create('sponsors', function ($table) {
            $table->id();
            $table->boolean('show')->default(1);
            $table->timestamps();
        });
        
        \Illuminate\Support\Facades\Schema::dropIfExists('tournament_prizes');
        \Illuminate\Support\Facades\Schema::create('tournament_prizes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->string('name');
            $table->string('position');
            $table->timestamps();
        });
    }

    /**
     * Re-seeding start times shouldn't destroy custom start times that were previously edited!
     */
    public function test_updating_tournament_preserves_custom_start_times()
    {
        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['pigeons' => 5, 'club_id' => $club->id, 'start_time' => '06:00:00']);
        $player = Player::factory()->create();
        $tournament->players()->attach($player->id);
        
        $date = now()->format('Y-m-d');
        \App\Models\Admin\TournamentFlyingDay::factory()->create(['tournament_id' => $tournament->id, 'date' => $date]);

        // Mock the initial creation of results
        $service = new \App\Services\TournamentService();
        // Uses reflection to call private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('updateTournamentPlayerData');
        $method->setAccessible(true);
        $method->invokeArgs($service, [$tournament]);

        // Let's pretend the admin edited this specific player's start time to 07:00:00 
        // using the X-editable ResultController@time endpoint earlier today
        Result::where('player_id', $player->id)->update(['start_time' => '07:00:00']);

        // Now, the admin goes to edit the entire tournament simply to change the name,
        // or add a new flying date. 
        $this->actingAs($this->adminUser)->patch(route('tournament.update', $tournament->id), [
            'name' => 'Updated Tournament Name',
            'club' => $club->id,
            'days' => 1,
            'pigeons' => 5, // unchanged
            'time' => '06:00:00', // tournament baseline is still 06:00
            'date' => [now()->format('m/d/Y')], // MUST use m/d/Y format to match frontend daterangepicker
            'players' => [$player->id],
            'status' => 'on',
            'show' => 'on',
            'supporter' => 0,
            'type' => 'OPEN'
        ])->assertRedirect(); // assuming it redirects on success

        // The critical check: Does the player still have their customized 07:00:00 start time?
        // Or did updateTournamentPlayerData destroy it and flip it back to 06:00:00?
        $pigeon1 = Result::where('player_id', $player->id)->where('pigeon_number', 1)->first();
        $this->assertEquals('07:00:00', $pigeon1->start_time, "Updating a tournament destroyed a player's customized start time!");
    }

    /**
     * Scaling down pigeons should clean up dangling data to guarantee accurate player_tournament_totals
     */
    public function test_scaling_down_pigeons_cleans_up_dangling_entries()
    {
        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['pigeons' => 7, 'club_id' => $club->id]);
        $player = Player::factory()->create();
        $tournament->players()->attach($player->id);
        $date = now()->format('Y-m-d');
        \App\Models\Admin\TournamentFlyingDay::factory()->create(['tournament_id' => $tournament->id, 'date' => $date]);

        // Seed 7 pigeons
        $service = new \App\Services\TournamentService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('updateTournamentPlayerData');
        $method->setAccessible(true);
        $method->invokeArgs($service, [$tournament]);

        // verify we have 7
        $this->assertCount(7, Result::where('player_id', $player->id)->get());

        // Now Admin edits tournament downward to 5 pigeons
        $this->actingAs($this->adminUser)->patch(route('tournament.update', $tournament->id), [
            'name' => 'Scaled Down Tourney',
            'club' => $club->id,
            'days' => 1,
            'pigeons' => 5, // DOWN FROM 7!
            'time' => '06:00:00',
            'date' => [now()->format('m/d/Y')], // MUST use m/d/Y format to match frontend daterangepicker
            'players' => [$player->id],
            'status' => 'on',
            'supporter' => 0,
            'type' => 'OPEN'
        ]);

        // Critical Check: Did pigeons 6 and 7 get deleted? Or are they dangling?
        $pigeonCount = Result::where('player_id', $player->id)->count();
        $this->assertEquals(5, $pigeonCount, "Dangling pigeons were not cleaned up when the tournament size was reduced!");
    }

    /**
     * Syncing multiple moderators should save all of them, not just the last one in the array
     */
    public function test_syncing_multiple_moderators_saves_all()
    {
        $club = Club::factory()->create();
        $tournament = Tournament::factory()->create(['club_id' => $club->id]);
        
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();
        $admin3 = User::factory()->create();

        $this->actingAs($this->adminUser)->patch(route('tournament.update', $tournament->id), [
            'name' => 'Moderator Tourney',
            'club' => $club->id,
            'days' => 1,
            'pigeons' => 5,
            'time' => '06:00:00',
            'date' => [now()->format('m/d/Y')], // MUST use m/d/Y format to match frontend daterangepicker
            'status' => 'on',
            'supporter' => 0,
            'type' => 'OPEN',
            'tournament_admins' => [$admin1->id, $admin2->id, $admin3->id] // Attempt to sync 3 admins
        ]);

        // Critical Check: Are there 3 admins in the moderator table?
        $moderatorCount = DB::table('tournament_moderator')->where('tournament_id', $tournament->id)->count();
        $this->assertEquals(3, $moderatorCount, "Failed to sync all moderators. Only $moderatorCount were saved instead of 3.");
    }
}
