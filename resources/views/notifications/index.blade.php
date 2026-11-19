@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold dark:text-white">{{ __('app.notifications') }}</h1>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm text-blue-600 dark:text-blue-400">{{ __('app.mark_all_read') }}</button>
            </form>
        </div>
        <div class="space-y-2">
            @forelse($notifications as $n)
                @php $d = $n->data; @endphp
                @php $url = !empty($d['post_id']) ? route('posts.show', $d['post_id']) : (!empty($d['comment_id']) ? route('posts.show', \App\Models\Comment::find($d['comment_id'])?->post_id ?? 0) : null); @endphp
                <div class="p-3 rounded-lg {{ $n->read_at ? 'bg-gray-100 dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/30' }}">
                    @if($url)
                        <a href="{{ route('notifications.read', ['id' => $n->id, 'url' => $url]) }}" class="block">
                            <p class="dark:text-white">{{ $d['user_name'] ?? '' }} {{ __('app.'.$d['type']) }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                        </a>
                    @else
                        <p class="dark:text-white">{{ $d['user_name'] ?? '' }} {{ __('app.'.$d['type']) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400">{{ __('app.no_notifications') }}</p>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div>
@endsection
