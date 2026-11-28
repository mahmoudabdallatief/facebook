<div class="space-y-2">
    @forelse($notifications as $n)
        @php
            $d = $n->data;
            $url = null;
            $post = null;

            if (!empty($d['post_id'])) {
                $post = isset($notificationPosts)
                    ? $notificationPosts->get($d['post_id'])
                    : \App\Models\Post::with(['group', 'page'])->find($d['post_id']);
                if ($post) {
                    if ($post->isGroupPost() && $post->group) {
                        $url = route('groups.posts.show', ['group' => $post->group, 'post' => $post]);
                    } elseif ($post->isPagePost() && $post->page) {
                        $url = route('pages.posts.show', ['page' => $post->page, 'post' => $post]);
                    } else {
                        $url = route('posts.show', $post);
                    }
                }
            } elseif (!empty($d['comment_id'])) {
                $comment = isset($notificationComments)
                    ? $notificationComments->get($d['comment_id'])
                    : \App\Models\Comment::with('post.group', 'post.page')->find($d['comment_id']);
                if ($comment && $comment->post) {
                    if ($comment->post->isGroupPost() && $comment->post->group) {
                        $url = route('groups.posts.show', ['group' => $comment->post->group, 'post' => $comment->post]);
                    } elseif ($comment->post->isPagePost() && $comment->post->page) {
                        $url = route('pages.posts.show', ['page' => $comment->post->page, 'post' => $comment->post]);
                    } else {
                        $url = route('posts.show', $comment->post);
                    }
                }
            } elseif (in_array($d['type'] ?? '', ['friend_request', 'friend_accepted'])) {
                $url = route('friends.index');
            }

            $communityName = $post?->page?->name ?? $post?->group?->name;
            $notificationText = ($d['type'] ?? '') === 'post_created' && $communityName
                ? __('app.post_created_notification', ['name' => $communityName])
                : trim(($d['user_name'] ?? '').' '.__('app.'.($d['type'] ?? '')));
        @endphp
        <div class="p-3 rounded-lg {{ $n->read_at ? 'bg-gray-100 dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/30' }}">
            @if($url)
                <a href="{{ route('notifications.read', ['id' => $n->id, 'url' => $url]) }}" class="block">
                    <p class="dark:text-white">{{ $notificationText }}</p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                </a>
            @else
                <p class="dark:text-white">{{ $notificationText }}</p>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
            @endif
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400">{{ __('app.no_notifications') }}</p>
    @endforelse
</div>
