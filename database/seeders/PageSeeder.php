<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'description' => 'Learn about our organization and mission.',
            ],
            [
                'title' => 'Contact',
                'description' => 'Get in touch with us.',
            ],
            [
                'title' => 'Privacy Policy',
                'description' => 'Our privacy policy and data handling practices.',
            ],
            [
                'title' => 'Terms of Service',
                'description' => 'Terms and conditions for using our services.',
            ],
        ];

        foreach ($pages as $page) {
            Page::create([
                'title' => $page['title'],
                'slug' => Str::slug($page['title']),
                'description' => $page['description'],
            ]);
        }
    }
}
