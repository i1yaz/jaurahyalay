<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Result;
use App\Models\Admin\Player;
use App\Models\Admin\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition()
    {
        return [
            'player_id' => Player::factory(),
            'tournament_id' => Tournament::factory(),
            'date' => $this->faker->date(),
            'pigeon_number' => 1,
            'start_time' => '06:00:00',
            'pigeon_time' => null,
            'pigeon_total' => 0,
            'time_in_seconds' => 0,
        ];
    }
}
