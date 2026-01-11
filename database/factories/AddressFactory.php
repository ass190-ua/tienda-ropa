<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'line1'   => fake()->streetAddress(),
            'city'    => fake()->city(),
            'zip'     => fake()->postcode(),
            'country' => fake()->country(),
            'type'    => fake()->randomElement(['shipping','billing']),
        ];
    }
}
