<?php

namespace Database\Factories\Admin;

use App\Models\Admin\TournamentFlyingDay;
use App\Models\Admin\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFlyingDayFactory extends Factory
{
    protected $model = TournamentFlyingDay::class;

    public function definition()
    {
        return [
            'tournament_id' => Tournament::factory(),
            'date' => $this->faker->date(),
        ];
    }
}
