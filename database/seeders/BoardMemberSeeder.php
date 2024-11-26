<?php

namespace Database\Seeders;

use App\Models\BoardMember;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BoardMemberSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $boardCategory = Category::where('name', 'Board')->first()->id;
        $managementCategory = Category::where('name', 'Management')->first()->id;

        $positions = [
            $boardCategory => [
                'Board Chairman',
                'Board Vice Chairman',
                'Board Secretary',
                'Board Treasurer',
                'Board Member',
            ],
            $managementCategory => [
                'CEO',
                'CFO',
                'CTO',
                'COO',
                'HR Director',
            ],
        ];

        foreach ($positions as $categoryId => $titles) {
            foreach ($titles as $title) {
                $name = $faker->name();

                BoardMember::create([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => "**{$title}**\n\n" . $faker->paragraphs(2, true),
                ]);
            }
        }
    }
}
