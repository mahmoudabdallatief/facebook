<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InfiniteScrollDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $mahmoud = User::updateOrCreate(
            ['id' => 12],
            [
                'name' => 'Mahmoud abdallateef',
                'email' => 'mahmoud.abdallateef@example.com',
                'bio' => 'اختبار التمرير اللانهائي لكل صفحات فيسبوك.',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $others = collect();
        $takenEmails = User::query()->pluck('email')->flip();
        while ($others->count() < 60) {
            $name = fake()->name();
            $email = strtolower(Str::slug($name)).'.'.fake()->numberBetween(1000, 99999).'@example.com';
            if (isset($takenEmails[$email]) || $others->contains('email', $email)) {
                continue;
            }
            $takenEmails[$email] = true;
            $others->push(User::create([
                'name' => $name,
                'email' => $email,
                'bio' => fake()->randomElement(['Coffee lover ☕', 'Building cool things.', 'Photography & travel.', 'Reading, learning, sharing.', 'Hello there!', null]),
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]));
        }

        $all = $others->push($mahmoud);

        // ---- Freunde von Mahmoud (akzeptiert, beide Richtungen über user_id=12 ausreichen) ----
        $friends = $all->where('id', '!=', 12)->random(25);
        foreach ($friends as $f) {
            Friendship::updateOrCreate(
                ['user_id' => 12, 'friend_id' => $f->id],
                ['status' => 'accepted', 'accepted_at' => now()],
            );
        }

        // ---- Eingehende Anfragen (andere -> Mahmoud, pending) ----
        $incoming = $all->where('id', '!=', 12)->whereNotIn('id', $friends->pluck('id'))->random(15);
        foreach ($incoming as $f) {
            Friendship::updateOrCreate(
                ['user_id' => $f->id, 'friend_id' => 12],
                ['status' => 'pending', 'accepted_at' => null],
            );
        }

        // ---- Ausgehende Anfragen (Mahmoud -> andere, pending) ----
        $outgoing = $all->where('id', '!=', 12)->whereNotIn('id', $friends->pluck('id'))->whereNotIn('id', $incoming->pluck('id'))->random(10);
        foreach ($outgoing as $f) {
            Friendship::updateOrCreate(
                ['user_id' => 12, 'friend_id' => $f->id],
                ['status' => 'pending', 'accepted_at' => null],
            );
        }

        // ---- Mahmoud bloquea a algunos usuarios aleatorios (sin eliminar datos existentes) ----
        $blockedCandidates = $all->where('id', '!=', 12)->random(min(120, $all->where('id', '!=', 12)->count()));
        foreach ($blockedCandidates as $blocked) {
            \App\Models\UserBlock::updateOrCreate(
                ['user_id' => 12, 'blocked_user_id' => $blocked->id],
                ['were_friends' => false],
            );
        }

        // ---- Gruppen ----
        $groupNames = [
            'مطورو الويب العرب', 'مصممو واجهات المستخدم', 'تعلم اللغات', 'ريادة الأعمال',
            'التصوير الفوتوغرافي', 'وصفات الطبخ', 'سفر وسياحة', 'لياقة وصحة',
            'أفلام وسينما', 'كتب ومطالعة', 'برمجة بلغة بايثون', 'جافاسكربت وتايلويند',
        ];
        $groups = [];
        foreach ($groupNames as $index => $name) {
            $owner = $all->random();
            $group = Group::updateOrCreate(
                ['slug' => Str::slug($name).'-demo-'.Str::random(4)],
                [
                    'user_id' => $owner->id,
                    'name' => $name,
                    'description' => fake()->sentence(12),
                    'privacy' => fake()->randomElement(['public', 'private']),
                    'is_verified' => fake()->boolean(15),
                ]
            );
            GroupMember::updateOrCreate(
                ['group_id' => $group->id, 'user_id' => $owner->id],
                ['role' => 'admin', 'joined_at' => now()],
            );
            foreach ($all->random(8) as $member) {
                GroupMember::updateOrCreate(
                    ['group_id' => $group->id, 'user_id' => $member->id],
                    ['role' => 'member', 'joined_at' => now()],
                );
            }
            $groups[] = $group;
        }

        // ---- Seiten ----
        $pageNames = [
            'فيزياء للجميع', 'البرمجة العربية', 'تصوير وفن', 'تطوير الويب العربي',
            'كتاب ومطالعة', 'طهي ووصفات', 'سفر وسياحة', 'لياقة وصحة', 'أفلام وسينما', 'تقنية المستقبل',
        ];
        $pages = [];
        foreach ($pageNames as $index => $name) {
            $owner = $all->random();
            $page = Page::updateOrCreate(
                ['slug' => Str::slug($name).'-demo-'.Str::random(4)],
                [
                    'user_id' => $owner->id,
                    'type' => 'page',
                    'name' => $name,
                    'description' => fake()->sentence(12),
                    'privacy' => 'public',
                    'is_verified' => fake()->boolean(15),
                ]
            );
            PageMember::updateOrCreate(
                ['page_id' => $page->id, 'user_id' => $owner->id],
                ['role' => 'admin', 'joined_at' => now()],
            );
            foreach ($all->random(6) as $member) {
                PageMember::updateOrCreate(
                    ['page_id' => $page->id, 'user_id' => $member->id],
                    ['role' => 'member', 'joined_at' => now()],
                );
            }
            $pages[] = $page;
        }

        // ---- Beiträge für timeline, Gruppen und Seiten ----
        $bodies = [
            'جرب التمرير اللانهائي على فيسبوك الجديد!',
            'تم إضافة خاصية التمرير اللانهائي لجميع الصفحات.',
            'أجمل Sunset شاهدته اليوم 🌅',
            'عمل على مشروع جديد باستخدام React و Tailwind.',
            'صباح القهوة ☕ وجلسة برمجة.',
            'ذكريات سفر من الصيف الماضي.',
            'مرحباً بكم في مجموعتنا!',
            'أي نصيحة للمبتدئين في هذا المجال؟',
            'شاركوا آراءكم في التعليقات أدناه.',
            'إعلان مهم قادم قريباً، ترقبوا.',
        ];

        foreach ($all as $user) {
            foreach (range(1, rand(2, 4)) as $_) {
                Post::create([
                    'user_id' => $user->id,
                    'body' => fake()->randomElement($bodies),
                    'visibility' => fake()->randomElement(['public', 'friends']),
                    'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
                ]);
            }
        }

        foreach ($groups as $group) {
            $members = $group->members()->with('user')->get()->pluck('user')->push($group->owner)->unique('id');
            foreach (range(1, 3) as $_) {
                $post = Post::create([
                    'user_id' => $members->random()->id,
                    'group_id' => $group->id,
                    'body' => fake()->randomElement($bodies),
                    'visibility' => 'public',
                    'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
                ]);
                foreach ($members->random(rand(1, 3)) as $reactor) {
                    Reaction::updateOrCreate(
                        ['user_id' => $reactor->id, 'reactionable_type' => Post::class, 'reactionable_id' => $post->id],
                        ['reaction_type' => fake()->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry'])],
                    );
                }
            }
        }

        foreach ($pages as $page) {
            $members = $page->members()->with('user')->get()->pluck('user')->push($page->owner)->unique('id');
            foreach (range(1, 3) as $_) {
                $post = Post::create([
                    'user_id' => $members->random()->id,
                    'page_id' => $page->id,
                    'body' => fake()->randomElement($bodies),
                    'visibility' => 'public',
                    'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
                ]);
                foreach ($members->random(rand(1, 3)) as $reactor) {
                    Reaction::updateOrCreate(
                        ['user_id' => $reactor->id, 'reactionable_type' => Post::class, 'reactionable_id' => $post->id],
                        ['reaction_type' => fake()->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry'])],
                    );
                }
            }
        }

        // ---- Kommentare & Antworten auf Mahmouds Beiträge (für Benachrichtigungen) ----
        $mahmoudPosts = Post::where('user_id', 12)->get();
        foreach ($mahmoudPosts as $post) {
            foreach (range(1, rand(1, 3)) as $_) {
                $author = $all->where('id', '!=', 12)->random();
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $author->id,
                    'body' => fake()->sentence(6),
                    'parent_id' => null,
                    'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
                ]);
                foreach ($all->random(rand(0, 2)) as $reactor) {
                    Reaction::updateOrCreate(
                        ['user_id' => $reactor->id, 'reactionable_type' => Comment::class, 'reactionable_id' => $comment->id],
                        ['reaction_type' => fake()->randomElement(['like', 'love', 'haha'])],
                    );
                }
                foreach (range(1, rand(0, 2)) as $_) {
                    $replyAuthor = $all->random();
                    Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $replyAuthor->id,
                        'body' => fake()->sentence(4),
                        'parent_id' => $comment->id,
                        'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
                    ]);
                }
            }
        }

        // ---- Benachrichtigungen für Mahmoud (id 12) ----
        $notificationTypes = [
            'post_reaction' => 'تفاعل :name مع منشورك',
            'comment_reaction' => 'تفاعل :name مع تعليقك',
            'post_comment' => 'علّق :name على منشورك',
            'comment_reply' => 'رد :name على تعليقك',
            'post_share' => 'شارك :name منشورك',
            'friend_request' => 'أرسل لك طلب صداقة',
            'friend_accepted' => 'قبل طلب صداقتك',
            'post_created' => 'تم إنشاء منشور جديد في :name',
        ];

        $now = now();
        $notifications = [];
        foreach (range(1, 120) as $i) {
            $type = fake()->randomElement(array_keys($notificationTypes));
            $actor = $all->where('id', '!=', 12)->random();
            $data = [
                'type' => $type,
                'user_id' => $actor->id,
                'user_name' => $actor->name,
            ];
            if (in_array($type, ['post_reaction', 'post_comment', 'post_share'], true) && $mahmoudPosts->isNotEmpty()) {
                $post = $mahmoudPosts->random();
                $data['post_id'] = $post->id;
            }
            if (in_array($type, ['comment_reaction', 'comment_reply'], true) && $mahmoudPosts->isNotEmpty()) {
                $post = $mahmoudPosts->random();
                $data['post_id'] = $post->id;
                $data['comment_id'] = fake()->numberBetween(1, 999);
            }
            if ($type === 'post_created') {
                $community = count($groups) > 0 ? $groups[array_rand($groups)] : null;
                $data['community_name'] = $community ? $community->name : null;
                $data['community_type'] = $community ? 'group' : 'page';
            }
            $notifications[] = [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\DemoNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => 12,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'read_at' => fake()->boolean(30) ? now()->subMinutes(rand(1, 60)) : null,
                'created_at' => $now->copy()->subSeconds($i * 17),
                'updated_at' => $now->copy()->subSeconds($i * 17),
            ];
        }
        \Illuminate\Support\Facades\DB::table('notifications')->insert($notifications);

        // ---- Konversationen & Nachrichten für Mahmoud (viele Nachrichten pro Chat) ----
        $chatFriends = $friends->random(8);
        foreach ($chatFriends as $friend) {
            $conversation = Conversation::getOrCreate($mahmoud, $friend);
            $messages = [];
            foreach (range(1, 40) as $index) {
                $sender = $index % 2 === 1 ? $mahmoud->id : $friend->id;
                $messages[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender,
                    'content' => fake()->sentence(rand(3, 12)),
                    'type' => 'text',
                    'read_at' => $sender === 12 || $index < 5 ? now()->subMinutes(rand(1, 60)) : null,
                    'created_at' => now()->subMinutes(rand(1, 10000)),
                ];
            }
            Message::insert($messages);
            $latest = Message::where('conversation_id', $conversation->id)->latest()->first();
            if ($latest) {
                $conversation->update(['last_message_at' => $latest->created_at]);
            }
        }
    }
}
