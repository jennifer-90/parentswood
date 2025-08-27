<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Appeler le seeder CentresInteretSeeder
        $this->call(CentresInteretSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(UsersSeeder::class);
        //$this->call(EventsSeeder::class);
        $this->call(MessagesSeeder::class);
        $this->call(EventMessageSeeder::class);
        $this->call(CentreInteretEventSeeder::class);
        $this->call(CentreInteretUserSeeder::class);
        $this->call(RoleUserSeeder::class);




        // User::factory(10)->create();

        User::factory()->create([
            'pseudo' => 'testuser',
            'last_name' => 'User',
            'first_name' => 'Test',
            'email' => 'test@example.com',
        ]);
    }
}
