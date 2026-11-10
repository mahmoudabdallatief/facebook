<?php

namespace Database\Seeders;

use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $me = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'bio' => 'This is my demo profile.',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $people = [
            ['name' => 'Ahmed Ali', 'email' => 'ahmed@example.com', 'bio' => 'Coffee lover and developer.'],
            ['name' => 'Sara Hassan', 'email' => 'sara@example.com', 'bio' => 'Designer. I love clean UI.'],
            ['name' => 'Omar Khaled', 'email' => 'omar@example.com', 'bio' => 'Travel & photography.'],
            ['name' => 'Mona Ibrahim', 'email' => 'mona@example.com', 'bio' => 'Reading and learning every day.'],
            ['name' => 'Youssef Adel', 'email' => 'youssef@example.com', 'bio' => null],
            ['name' => 'Nour Mohamed', 'email' => 'nour@example.com', 'bio' => 'Frontend enthusiast.'],
            ['name' => 'Hana Samir', 'email' => 'hana@example.com', 'bio' => 'Music and movies.'],
            ['name' => 'Karim Fathy', 'email' => 'karim@example.com', 'bio' => 'Backend engineer.'],
            ['name' => 'Laila Mostafa', 'email' => 'laila@example.com', 'bio' => 'Product thinker.'],
            ['name' => 'Tarek Nabil', 'email' => 'tarek@example.com', 'bio' => 'Let us connect!'],
            ['name' => 'Dina Zaki', 'email' => 'dina@example.com', 'bio' => 'New here.'],
            ['name' => 'Hossam Reda', 'email' => 'hossam@example.com', 'bio' => 'Always curious.'],
        ];

        $users = collect($people)->map(fn (array $p) => User::updateOrCreate(
            ['email' => $p['email']],
            [
                'name' => $p['name'],
                'bio' => $p['bio'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ));

        // Accepted friends
        foreach ($users->take(4) as $u) {
            Friendship::updateOrCreate(
                ['user_id' => $me->id, 'friend_id' => $u->id],
                ['status' => 'accepted', 'accepted_at' => now()],
            );
        }

        // Incoming pending requests (they -> me)
        foreach ($users->slice(4, 2) as $u) {
            Friendship::updateOrCreate(
                ['user_id' => $u->id, 'friend_id' => $me->id],
                ['status' => 'pending', 'accepted_at' => null],
            );
        }

        // Outgoing pending request (me -> them)
        $out = $users->get(6);
        if ($out) {
            Friendship::updateOrCreate(
                ['user_id' => $me->id, 'friend_id' => $out->id],
                ['status' => 'pending', 'accepted_at' => null],
            );
        }

        // The rest are suggestions (no relation)

        // Sample posts for timeline
        $postSamples = [
            ['body' => 'Just finished building my first Laravel app! Feeling excited to share more.', 'visibility' => 'public'],
            ['body' => 'Beautiful sunset at the beach today 🌅', 'visibility' => 'public'],
            ['body' => 'Working on a new project with React and Tailwind. Can\'t wait to launch it.', 'visibility' => 'friends'],
            ['body' => 'Morning coffee ☕ and coding session. Productive day ahead!', 'visibility' => 'public'],
            ['body' => 'Travel memories from last summer. Missing those days.', 'visibility' => 'friends'],
        ];

        $allUsers = $users->push($me);

        foreach ($allUsers as $index => $user) {
            $sample = $postSamples[array_rand($postSamples)];
            Post::create([
                'user_id' => $user->id,
                'body' => $sample['body'],
                'visibility' => $sample['visibility'],
            ]);
        }
    }
}
