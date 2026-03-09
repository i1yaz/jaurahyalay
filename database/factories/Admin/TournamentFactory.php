<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Tournament;
use App\Models\Admin\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3) . ' Tournament',
            'club_id' => Club::factory(),
            'days' => 5,
            'pigeons' => 7,
            'supporter' => 0,
            'start_date' => $this->faker->date(),
            'start_time' => '06:00',
            'status' => true,
            'show' => true,
            'type' => 'OPEN',
            'public_hide' => false,
            'sort' => 0,
        ];
    }

    public function supporter()
    {
        return $this->state(function (array $attributes) {
            return [
                'pigeons' => 8,
                'supporter' => 1,
            ];
        });
    }
}
