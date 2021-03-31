<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\Fight;
use Illuminate\Database\Eloquent\Factories\Factory;

class FightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Fight::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'hero_id' => $this->faker->randomElement(Character::hero()->get('id', 'type'))->id,
            'monster_id' => $this->faker->randomElement(Character::monster()->get('id', 'type'))->id,
        ];
    }
}
