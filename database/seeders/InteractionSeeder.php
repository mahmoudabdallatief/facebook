<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InteractionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $posts = Post::all();

        if ($users->isEmpty()) {
            $users = collect([
                ['name' => 'Test User', 'email' => 'test@example.com', 'bio' => 'Demo profile.'],
                ['name' => 'Ahmed Ali', 'email' => 'ahmed@example.com', 'bio' => 'Coffee lover.'],
                ['name' => 'Sara Hassan', 'email' => 'sara@example.com', 'bio' => 'Designer.'],
                ['name' => 'Omar Khaled', 'email' => 'omar@example.com', 'bio' => 'Travel & photography.'],
                ['name' => 'Mona Ibrahim', 'email' => 'mona@example.com', 'bio' => 'Reader.'],
                ['name' => 'Youssef Adel', 'email' => 'youssef@example.com', 'bio' => null],
            ])->map(fn ($p) => User::updateOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'bio' => $p['bio'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            ));

            $me = $users->firstWhere('email', 'test@example.com');
            foreach ($users->where('id', '!=', $me->id)->take(3) as $u) {
                Friendship::updateOrCreate(
                    ['user_id' => $me->id, 'friend_id' => $u->id],
                    ['status' => 'accepted', 'accepted_at' => now()]
                );
            }
        }

        if ($posts->isEmpty()) {
            $samples = [
                ['body' => 'أول بوست تجريبي على المنصة!', 'visibility' => 'public'],
                ['body' => 'Just finished building a new feature 🚀', 'visibility' => 'public'],
                ['body' => 'يوم جميل مع الأصدقاء.', 'visibility' => 'friends'],
                ['body' => 'Coffee and code ☕', 'visibility' => 'public'],
                ['body' => 'Working on something exciting.', 'visibility' => 'public'],
                ['body' => 'صباح الخير عليكم جميعاً.', 'visibility' => 'friends'],
                ['body' => 'Weekend vibes.', 'visibility' => 'public'],
                ['body' => 'Learning Laravel step by step.', 'visibility' => 'public'],
            ];

            foreach ($samples as $i => $sample) {
                $user = $users[$i % $users->count()];
                Post::create([
                    'user_id' => $user->id,
                    'body' => $sample['body'],
                    'visibility' => $sample['visibility'],
                ]);
            }

            $posts = Post::all();
        }

        $users = User::all();
        $posts = Post::all();

        $commentBodies = [
            'بوست جميل جدًا 👏',
            'كلام رائع، متفق معاك.',
            'تسلم إيدك على المشاركة دي.',
            'حلو أوي! استمر.',
            'شكراً على المحتوى المفيد.',
            'أحسنت النشر.',
            'فعلاً كلام مظبوط.',
            'ما شاء الله، شغل جامد.',
            'Great post!',
            'Nice work, keep it up.',
            'Love this!',
            'Interesting point of view.',
            'Thanks for sharing.',
            'This made my day.',
            'Totally agree with you.',
        ];

        $replyBodies = [
            'متفق معاك تماماً.',
            'صح كلامك.',
            'هههه فعلاً.',
            'شكراً ليك!',
            'وصلت الفكرة 👍',
            'Exactly!',
            'I agree.',
            'Good point.',
            'Thanks!',
            'Haha, true.',
        ];

        $types = Reaction::REACTION_TYPES;
        $createdComments = 0;
        $createdReplies = 0;
        $createdReactions = 0;

        foreach ($posts as $post) {
            $commentCount = rand(1, 5);
            $commenters = $users->random(min($commentCount, $users->count()));

            $topLevel = [];
            foreach ($commenters as $user) {
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'parent_id' => null,
                    'body' => $commentBodies[array_rand($commentBodies)],
                ]);
                $topLevel[] = $comment;
                $createdComments++;
            }

            foreach ($topLevel as $comment) {
                if (rand(0, 100) < 45) {
                    $replyCount = rand(1, 2);
                    $repliers = $users->where('id', '!=', $comment->user_id)->shuffle()->take(min($replyCount, max($users->count() - 1, 0)));
                    foreach ($repliers as $replier) {
                        Comment::create([
                            'post_id' => $post->id,
                            'user_id' => $replier->id,
                            'parent_id' => $comment->id,
                            'body' => $replyBodies[array_rand($replyBodies)],
                        ]);
                        $createdReplies++;
                    }
                }
            }

            $postReactors = $users->shuffle()->take(rand(0, min(8, $users->count())));
            foreach ($postReactors as $user) {
                $reaction = $post->reactions()->firstOrNew(['user_id' => $user->id]);
                if (! $reaction->exists) {
                    $reaction->reaction_type = $types[array_rand($types)];
                    $reaction->save();
                    $createdReactions++;
                }
            }

            $freshComments = Comment::where('post_id', $post->id)->get();
            foreach ($freshComments as $comment) {
                if (rand(0, 100) < 35) {
                    $reactors = $users->shuffle()->take(rand(1, min(4, $users->count())));
                    foreach ($reactors as $user) {
                        $reaction = $comment->reactions()->firstOrNew(['user_id' => $user->id]);
                        if (! $reaction->exists) {
                            $reaction->reaction_type = $types[array_rand($types)];
                            $reaction->save();
                            $createdReactions++;
                        }
                    }
                }
            }
        }

        $this->command?->info("Seeded {$createdComments} comments, {$createdReplies} replies, {$createdReactions} reactions on {$posts->count()} posts for {$users->count()} users.");
    }
}
