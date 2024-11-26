<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Ensure we have some categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        // Sample blog posts with more realistic content
        $blogs = [
            [
                'title' => 'Understanding the Impact of Climate Change',
                'description' => '<p>Climate change is one of the most pressing challenges facing our world today. This comprehensive guide explores the latest research and findings on global warming, its effects on ecosystems, and what we can do to make a difference.</p>
                                <h3>Key Points:</h3>
                                <ul>
                                    <li>Rising global temperatures</li>
                                    <li>Impact on biodiversity</li>
                                    <li>Sustainable solutions</li>
                                </ul>
                                <p>Together, we can work towards a more sustainable future.</p>',
            ],
            [
                'title' => 'The Future of Renewable Energy',
                'description' => '<p>Renewable energy is transforming how we power our world. From solar panels to wind turbines, discover the latest innovations in sustainable energy technology.</p>
                                <h3>Latest Developments:</h3>
                                <ul>
                                    <li>Solar efficiency breakthroughs</li>
                                    <li>Wind power advancements</li>
                                    <li>Energy storage solutions</li>
                                </ul>',
            ],
            [
                'title' => 'Promoting Biodiversity Conservation',
                'description' => '<p>Biodiversity is crucial for maintaining healthy ecosystems. Learn about current conservation efforts and how you can contribute to protecting endangered species.</p>
                                <h3>Conservation Strategies:</h3>
                                <ul>
                                    <li>Habitat preservation</li>
                                    <li>Species recovery programs</li>
                                    <li>Community involvement</li>
                                </ul>',
            ],
        ];

        // Create 15 additional random blogs
        for ($i = 0; $i < 15; $i++) {
            $blogs[] = [
                'title' => $faker->unique()->sentence(rand(4, 8)),
                'description' => '<p>' . implode('</p><p>', $faker->paragraphs(rand(3, 6))) . '</p>',
            ];
        }

        // Insert all blogs with proper attributes
        foreach ($blogs as $blog) {
            $publishedAt = $faker->dateTimeBetween('-6 months', 'now');

            Blog::create([
                'category_id' => $categories->random()->id,
                'title' => $blog['title'],
                'slug' => Str::slug($blog['title']),
                'description' => $blog['description'],
                'is_published' => $faker->boolean(80), // 80% chance of being published
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $faker->dateTimeBetween($publishedAt, 'now'),
            ]);
        }
    }
}
