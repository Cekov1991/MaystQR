<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $eventCategory = Category::where('name', 'Events')->first()->id;

        for ($i = 0; $i < 10; $i++) {
            $title = $faker->sentence(3);
            $startTime = $faker->dateTimeBetween('now', '+6 months');
            $endTime = (clone $startTime)->modify('+' . rand(1, 4) . ' hours');

            Event::create([
                'category_id' => $eventCategory,
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $faker->paragraphs(3, true),
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        }
    }
}
