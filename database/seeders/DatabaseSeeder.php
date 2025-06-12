<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)->create()->each(function ($user) {
            Post::factory(3)->create(['user_id' => $user->id])->each(function ($post) use ($user) {
                Comment::factory(5)->create([
                    'user_id' => User::inRandomOrder()->first()->id,
                    'post_id' => $post->id,
                ]);
                $reactingUsers = User::inRandomOrder()->take(rand(3, 7))->get();
                foreach ($reactingUsers as $reactingUser) {
                    \App\Models\PostReaction::create([
                        'user_id' => $reactingUser->id,
                        'post_id' => $post->id,
                        'reaction_type' => 'like',
                    ]);
                }
            });
        });
        User::create([
            'firstname' => 'hamza',
            'lastname' => 'saadaldeel',
            'email' => 'hamzasaadaldeen2002@gmail.com',
            'birthday' => '2002/7/17',
            'gender' => 'male',
            'password' => '12345678',
            'phone' => '0954649881',
            'role' =>  'police'
        ]);
    }
}
