<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(4)
            ->withAddresses(rand(1, 2))
            ->create();
    }
}
