<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data  = [
            'La Molina',
            'Surco',
            'Santa Anita',
        ];

        foreach ( $data as $item ) {
            District::create([
                'name' => $item,
                'slug' => Str::slug($item),
                'created_by' => 1,
            ]);
        }

        $data = [
            [
                'user_id' => 3,
                'day' => 0,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 1,
            ],
            [
                'user_id' => 3,
                'day' => 0,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 1,
            ],
            [
                'user_id' => 3,
                'day' => 1,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 1,
            ],
            [
                'user_id' => 3,
                'day' => 1,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 1,
            ],
            [
                'user_id' => 3,
                'day' => 2,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 3,
                'day' => 2,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 3,
                'day' => 3,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 2,
                'day' => 3,
                'start_time' => '06:00',
                'end_time' => '10:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 2,
                'day' => 3,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 3,
            ],
            [
                'user_id' => 2,
                'day' => 3,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 2,
                'day' => 4,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 3,
            ],
            [
                'user_id' => 2,
                'day' => 4,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 3,
            ],
            [
                'user_id' => 2,
                'day' => 5,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 3,
            ],
            [
                'user_id' => 2,
                'day' => 5,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 2,
            ],
            [
                'user_id' => 2,
                'day' => 6,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'district_id' => 1,
            ],
            [
                'user_id' => 2,
                'day' => 6,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'district_id' => 1,
            ],
        ];

        foreach ($data as $item) {
            $ava = Availability::create([
                'user_id' => $item['user_id'],
                'day' => $item['day'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
            ]);

            $ava->districts()->attach($item['district_id']);
        }
    }
}
