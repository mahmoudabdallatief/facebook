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

        // Sample conversations and messages for messenger
        $me = User::where('email', 'test@example.com')->first();
        $friends = $users->take(4);

        foreach ($friends as $friend) {
            $conversation = Conversation::getOrCreate($me, $friend);

            $messages = [
                ['sender_id' => $me->id, 'content' => 'Hello '.$friend->name.'! How are you?'],
                ['sender_id' => $friend->id, 'content' => 'Hi! I am good, thanks. And you?'],
                ['sender_id' => $me->id, 'content' => 'Doing well. Want to catch up later?'],
            ];

            foreach ($messages as $index => $msgData) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $msgData['sender_id'],
                    'content' => $msgData['content'],
                    'type' => 'text',
                    'read_at' => $index < 2 ? now()->subMinutes(10) : null,
                ]);
            }

            $conversation->update([
                'last_message_at' => $conversation->messages()->latest()->first()->created_at,
            ]);
        }

        // Sample pages, groups, members, page posts, comments, reactions and shares
        $pageSamples = [
            [
                'name' => 'فيزياء للجميع',
                'slug' => 'physics-for-all',
                'type' => 'page',
                'privacy' => 'public',
                'description' => 'صفحة مخصصة لمحبي الفيزياء والعلوم لاستكشاف الكون وتبادل المعرفة.',
                'is_verified' => true,
            ],
            [
                'name' => 'مجموعة البرمجة العربية',
                'slug' => 'arab-code-group',
                'type' => 'group',
                'privacy' => 'closed',
                'description' => 'مجموعة للمطورين العرب لمناقشة لغات البرمجة، أطر العمل وأفضل الممارسات.',
                'is_verified' => false,
            ],
            [
                'name' => 'تصوير وفن',
                'slug' => 'photography-art',
                'type' => 'page',
                'privacy' => 'public',
                'description' => 'شارك صورك 최고의 وأعمالك الفنية مع مجتمع المصورين.',
                'is_verified' => false,
            ],
            [
                'name' => 'تطوير الويب العربي',
                'slug' => 'web-dev-ar',
                'type' => 'group',
                'privacy' => 'private',
                'description' => 'مجموعة خاصة لمطوري الويب العرب لبناء مشاريع مفتوحة المصدر.',
                'is_verified' => false,
            ],
            [
                'name' => 'كتاب ومطالعة',
                'slug' => 'books-reading',
                'type' => 'page',
                'privacy' => 'public',
                'description' => 'نناقش أفضل الكتب ونشارك الملخصات والتوصيات القرائية.',
                'is_verified' => false,
            ],
        ];

        $pageOwners = $allUsers ?? $users->push($me);
        $pages = [];

        foreach ($pageSamples as $index => $sample) {
            $owner = $pageOwners->get($index % $pageOwners->count());
            $page = Page::updateOrCreate(
                ['slug' => $sample['slug']],
                [
                    'user_id' => $owner->id,
                    'type' => $sample['type'],
                    'name' => $sample['name'],
                    'description' => $sample['description'],
                    'privacy' => $sample['privacy'],
                    'is_verified' => $sample['is_verified'],
                ],
            );

            // Owner is always admin
            PageMember::updateOrCreate(
                ['page_id' => $page->id, 'user_id' => $owner->id],
                ['role' => 'admin'],
            );

            // Add some members with roles
            $memberUsers = $allUsers->filter(fn ($u) => $u->id !== $owner->id)->take(4);
            foreach ($memberUsers as $mIndex => $memberUser) {
                $role = $mIndex === 0 ? 'moderator' : 'member';
                PageMember::updateOrCreate(
                    ['page_id' => $page->id, 'user_id' => $memberUser->id],
                    ['role' => $role],
                );
            }

            $pages[] = $page;
        }

        // Page posts with comments, replies and reactions
        $pagePostBodies = [
            'مرحباً بكم في صفحتنا! نتمنى أن تستمتعوا بالمحتوى.',
            'أي نصيحة للمبتدئين في هذا المجال؟',
            'شاركوا آراءكم في التعليقات أدناه.',
            'إعلان مهم:Cادم قادم قريباً، ترقبوا.',
            'ما هي أدواتكم المفضلة في العمل؟',
        ];

        $reactionTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

        foreach ($pages as $page) {
            $pageMembers = $page->members()->with('user')->get();
            $viewableUsers = $pageMembers->pluck('user')->push($page->owner);

            foreach (range(1, 3) as $postIndex) {
                $author = $viewableUsers->random();
                $post = Post::create([
                    'user_id' => $author->id,
                    'page_id' => $page->id,
                    'body' => $pagePostBodies[array_rand($pagePostBodies)],
                    'visibility' => 'public',
                ]);

                // Reactions on post from random members
                foreach ($viewableUsers->random(rand(1, 3)) as $reactor) {
                    Reaction::updateOrCreate(
                        [
                            'user_id' => $reactor->id,
                            'reactionable_type' => Post::class,
                            'reactionable_id' => $post->id,
                        ],
                        ['reaction_type' => fake()->randomElement($reactionTypes)],
                    );
                }

                // Comments on post
                foreach (range(1, 2) as $cIndex) {
                    $commentAuthor = $viewableUsers->random();
                    $comment = Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $commentAuthor->id,
                        'body' => fake()->sentence(6),
                        'parent_id' => null,
                    ]);

                    // Reactions on comment
                    foreach ($viewableUsers->random(rand(0, 2)) as $reactor) {
                        Reaction::updateOrCreate(
                            [
                                'user_id' => $reactor->id,
                                'reactionable_type' => Comment::class,
                                'reactionable_id' => $comment->id,
                            ],
                            ['reaction_type' => fake()->randomElement($reactionTypes)],
                        );
                    }

                    // Nested replies (tree up to 2 levels deep)
                    foreach (range(1, 2) as $rIndex) {
                        $replyAuthor = $viewableUsers->random();
                        $reply = Comment::create([
                            'post_id' => $post->id,
                            'user_id' => $replyAuthor->id,
                            'body' => fake()->sentence(4),
                            'parent_id' => $comment->id,
                        ]);

                        foreach ($viewableUsers->random(rand(0, 2)) as $reactor) {
                            Reaction::updateOrCreate(
                                [
                                    'user_id' => $reactor->id,
                                    'reactionable_type' => Comment::class,
                                    'reactionable_id' => $reply->id,
                                ],
                                ['reaction_type' => fake()->randomElement($reactionTypes)],
                            );
                        }
                    }
                }

                // Shares of the page post by random users (can share more than once across different posts)
                foreach ($allUsers->random(rand(0, 2)) as $sharer) {
                    Post::create([
                        'user_id' => $sharer->id,
                        'page_id' => $page->id,
                        'original_post_id' => $post->id,
                        'body' => fake()->optional()->sentence(5),
                        'visibility' => 'public',
                    ]);
                }
            }
        }

        // Sample groups (separate from pages)
        $groupSamples = [
            [
                'name' => 'مجموعة البرمجة العربية',
                'slug' => 'arab-code-group',
                'privacy' => 'closed',
                'description' => 'مجموعة للمطورين العرب لمناقشة لغات البرمجة، أطر العمل وأفضل الممارسات.',
                'is_verified' => false,
            ],
            [
                'name' => 'تطوير الويب العربي',
                'slug' => 'web-dev-ar',
                'privacy' => 'private',
                'description' => 'مجموعة خاصة لمطوري الويب العرب لبناء مشاريع مفتوحة المصدر.',
                'is_verified' => false,
            ],
            [
                'name' => 'تصميم وواجهات المستخدم',
                'slug' => 'ui-ux-design',
                'privacy' => 'public',
                'description' => 'مجموعة لمناقشة تصميم واجهات المستخدم وتجربة المستخدم وأدوات التصميم.',
                'is_verified' => false,
            ],
            [
                'name' => 'تعلم اللغات',
                'slug' => 'language-learning',
                'privacy' => 'public',
                'description' => 'مجموعة لمت aprendiz(g) اللغات لتبادل الموارد والتدريب على المحادثة.',
                'is_verified' => false,
            ],
            [
                'name' => 'ريادة الأعمال',
                'slug' => 'entrepreneurship',
                'privacy' => 'closed',
                'description' => 'مجموعة لرواد الأعمال لمناقشة الأفكار والمشاريع الناشئة.',
                'is_verified' => false,
            ],
        ];

        $groupOwners = $allUsers;
        $groups = [];

        foreach ($groupSamples as $index => $sample) {
            $owner = $groupOwners->get($index % $groupOwners->count());
            $group = Group::updateOrCreate(
                ['slug' => $sample['slug']],
                [
                    'user_id' => $owner->id,
                    'name' => $sample['name'],
                    'description' => $sample['description'],
                    'privacy' => $sample['privacy'],
                    'is_verified' => $sample['is_verified'],
                ],
            );

            // Owner is always admin
            GroupMember::updateOrCreate(
                ['group_id' => $group->id, 'user_id' => $owner->id],
                ['role' => 'admin'],
            );

            // Add some members with roles
            $memberUsers = $allUsers->filter(fn ($u) => $u->id !== $owner->id)->take(4);
            foreach ($memberUsers as $mIndex => $memberUser) {
                $role = $mIndex === 0 ? 'moderator' : 'member';
                GroupMember::updateOrCreate(
                    ['group_id' => $group->id, 'user_id' => $memberUser->id],
                    ['role' => $role],
                );
            }

            $groups[] = $group;
        }

        // Group posts with comments, replies and reactions
        $groupPostBodies = [
            'مرحباً بكم في مجموعتنا! نتمنى أن تستمتعوا بالمحتوى.',
            'أي نصيحة للمبتدئين في هذا المجال؟',
            'شاركوا آراءكم في التعليقات أدناه.',
            'إعلان مهم: اجتماع قادم قريباً، ترقبوا.',
            'ما هي أدواتكم المفضلة في العمل؟',
        ];

        foreach ($groups as $group) {
            $groupMembers = $group->members()->with('user')->get();
            $viewableUsers = $groupMembers->pluck('user')->push($group->owner);

            foreach (range(1, 3) as $postIndex) {
                $author = $viewableUsers->random();
                $post = Post::create([
                    'user_id' => $author->id,
                    'group_id' => $group->id,
                    'body' => $groupPostBodies[array_rand($groupPostBodies)],
                    'visibility' => 'public',
                ]);

                // Reactions on post from random members
                foreach ($viewableUsers->random(rand(1, 3)) as $reactor) {
                    Reaction::updateOrCreate(
                        [
                            'user_id' => $reactor->id,
                            'reactionable_type' => Post::class,
                            'reactionable_id' => $post->id,
                        ],
                        ['reaction_type' => fake()->randomElement($reactionTypes)],
                    );
                }

                // Comments on post
                foreach (range(1, 2) as $cIndex) {
                    $commentAuthor = $viewableUsers->random();
                    $comment = Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $commentAuthor->id,
                        'body' => fake()->sentence(6),
                        'parent_id' => null,
                    ]);

                    // Reactions on comment
                    foreach ($viewableUsers->random(rand(0, 2)) as $reactor) {
                        Reaction::updateOrCreate(
                            [
                                'user_id' => $reactor->id,
                                'reactionable_type' => Comment::class,
                                'reactionable_id' => $comment->id,
                            ],
                            ['reaction_type' => fake()->randomElement($reactionTypes)],
                        );
                    }

                    // Nested replies (tree up to 2 levels deep)
                    foreach (range(1, 2) as $rIndex) {
                        $replyAuthor = $viewableUsers->random();
                        $reply = Comment::create([
                            'post_id' => $post->id,
                            'user_id' => $replyAuthor->id,
                            'body' => fake()->sentence(4),
                            'parent_id' => $comment->id,
                        ]);

                        foreach ($viewableUsers->random(rand(0, 2)) as $reactor) {
                            Reaction::updateOrCreate(
                                [
                                    'user_id' => $reactor->id,
                                    'reactionable_type' => Comment::class,
                                    'reactionable_id' => $reply->id,
                                ],
                                ['reaction_type' => fake()->randomElement($reactionTypes)],
                            );
                        }
                    }
                }

                // Shares of the group post by random users (can share more than once across different posts)
                foreach ($allUsers->random(rand(0, 2)) as $sharer) {
                    Post::create([
                        'user_id' => $sharer->id,
                        'group_id' => $group->id,
                        'original_post_id' => $post->id,
                        'body' => fake()->optional()->sentence(5),
                        'visibility' => 'public',
                    ]);
                }
            }
        }
    }
}
