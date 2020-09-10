<?php

use Illuminate\Database\Seeder;
use App\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User;
        $user->name = "admin";
        $user->email = "mail@treenga.com";
        $user->password = bcrypt("admin");
        $user->status = User::STATUS_ACTIVE;
        $user->role = User::ADMIN_ROLE;
        $user->save();
    }
}
