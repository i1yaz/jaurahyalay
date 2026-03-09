<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClubFactory extends Factory
{
    protected $model = Club::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company() . ' Pigeon Club',
            'owner' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'city' => $this->faker->city(),
            'status' => true,
            'sort' => 0,
        ];
    }
}
