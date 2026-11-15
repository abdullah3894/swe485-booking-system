<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'password' => 'Password123',
            'role' => 'supervisor',
        ]);

        foreach ([1, 2, 3] as $number) {
            User::factory()->create([
                'name' => 'Member '.$number,
                'email' => 'member'.$number.'@example.com',
                'password' => 'Password123',
                'role' => 'member',
            ]);
        }

        $items = [
            ['name' => 'Pitch A', 'type' => 'field', 'is_active' => true],
            ['name' => 'Pitch B', 'type' => 'field', 'is_active' => true],
            ['name' => 'Football Set', 'type' => 'equipment', 'is_active' => true],
            ['name' => 'Old Goal', 'type' => 'equipment', 'is_active' => false],
        ];

        foreach ($items as $item) {
            Item::factory()->create($item);
        }
    }
}
