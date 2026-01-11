<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {

            // Cada usuario tiene 1–2 direcciones
            Address::factory()
                ->count(rand(1, 2))
                ->create([
                    'user_id' => $user->id,
                ]);
        });
    }
}
