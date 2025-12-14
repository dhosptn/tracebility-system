<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $users = 
      [
        'name' => 'Administrator',
        'email' => 'admin@tracebility.com',
        'password' => Hash::make('admin123'),
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ]
  }
}
