<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::insert([
            [
                'code' => 'WELCOME10',
                'discount_type' => Coupon::TYPE_PERCENT,
                'discount_value' => 10,
                'min_order_total' => 50,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'SPRING20',
                'discount_type' => Coupon::TYPE_PERCENT,
                'discount_value' => 20,
                'min_order_total' => 100,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addMonth(),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE15',
                'discount_type' => Coupon::TYPE_FIXED,
                'discount_value' => 15,
                'min_order_total' => 80,
                'start_date' => Carbon::now()->subDays(20),
                'end_date' => Carbon::now()->addWeeks(2),
                'is_active' => true,
            ],
            [
                'code' => 'BLACKFRIDAY',
                'discount_type' => Coupon::TYPE_PERCENT,
                'discount_value' => 30,
                'min_order_total' => 150,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->subMonth(),
                'is_active' => false,
            ],
        ]);
    }
}
