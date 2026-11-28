<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    private const TYPES = ['all', 'people', 'posts', 'groups', 'pages', 'shares'];
    private const MIN_QUERY_LENGTH = 3;
    private const LIMIT_ALL = 5;

    /** @var list<string> */
    private const BOOLEAN_SPECIALS = ['+', '-', '<', '>', '@', '~', '(', ')', '"'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $user?->loadMissing([
            'blocks',
            'blockedBy',
            'friendsOfMine',
            'friendOf',
            'sentFriendRequests',
            'receivedFriendRequests',
        ]);

        $query = trim($request->string('q')->toString());
        $type = $request->string('type')->toString();

        if (! in_array($type, self::TYPES, true)) {
            $type = 'all';
        }

        $hasQuery = mb_strlen($query) >= self::MIN_QUERY_LENGTH;

        $users = $posts = $groups = $pages = $shares = null;

        if ($hasQuery) {
            $booleanQuery = $this->escapeBooleanQuery($query) . '*';

            if ($type === 'all' || $type === 'people') {
                $users = $this->searchPeople($booleanQuery, $type === 'all');
            }
            if ($type === 'all' || $type === 'posts') {
                $posts = $this->searchPosts($booleanQuery, $type === 'all');
            }
            if ($type === 'all' || $type === 'groups') {
                $groups = $this->searchGroups($booleanQuery, $type === 'all');
            }
            if ($type === 'all' || $type === 'pages') {
                $pages = $this->searchPages($booleanQuery, $type === 'all');
            }
            if ($type === 'all' || $type === 'shares') {
                $shares = $this->searchShares($booleanQuery, $type === 'all');
            }
        }

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'users' => $users,
            'posts' => $posts,
            'groups' => $groups,
            'pages' => $pages,
            'shares' => $shares,
            'hasQuery' => $hasQuery,
            'minQueryLength' => self::MIN_QUERY_LENGTH,
        ]);
    }

    private function escapeBooleanQuery(string $query): string
    {
        $cleaned = str_replace(self::BOOLEAN_SPECIALS, ' ', $query);
        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }

    private function searchPeople(string $booleanQuery, bool $limited)
    {
        $user = auth()->user();

        $q = User::query()
            ->where('id', '!=', auth()->id())
            ->when(! empty($blockedIds = $user->blockedRelationUserIds()), fn ($q) => $q->whereNotIn('id', $blockedIds))
            ->whereRaw('MATCH(name, email) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->latest();

        return $q->paginate(10, ['*'], 'page')->withQueryString();
    }

    private function searchPosts(string $booleanQuery, bool $limited)
    {
        $viewer = auth()->user();

        $q = Post::query()
            ->withFeedRelations($viewer)
            ->visibleTo($viewer)
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->latest();

        return $q->paginate(10, ['*'], 'page')->withQueryString();
    }

    private function searchGroups(string $booleanQuery, bool $limited)
    {
        $q = Group::query()
            ->visibleTo(auth()->user())
            ->whereRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->latest();

        return $q->paginate(10, ['*'], 'page')->withQueryString();
    }

    private function searchPages(string $booleanQuery, bool $limited)
    {
        $q = Page::query()
            ->where('type', 'page')
            ->whereRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->latest();

        return $q->paginate(10, ['*'], 'page')->withQueryString();
    }

    private function searchShares(string $booleanQuery, bool $limited)
    {
        $viewer = auth()->user();

        $q = Post::query()
            ->withFeedRelations($viewer)
            ->visibleTo($viewer)
            ->whereNotNull('original_post_id')
            ->where(function ($q2) use ($booleanQuery): void {
                $q2->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
                    ->orWhereHas('originalPost', function ($q3) use ($booleanQuery): void {
                        $q3->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
                    });
            })
            ->latest();

        return $q->paginate(10, ['*'], 'page')->withQueryString();
    }

    public function infinite(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());
        $type = $request->string('type')->toString();

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH || ! in_array($type, ['people', 'posts', 'groups', 'pages', 'shares'], true)) {
            return response()->json(['html' => '', 'next_page' => null, 'has_more' => false]);
        }

        $booleanQuery = $this->escapeBooleanQuery($query) . '*';
        $viewer = auth()->user();

        $paginator = match ($type) {
            'people' => $this->searchPeople($booleanQuery, false),
            'posts' => $this->searchPosts($booleanQuery, false),
            'groups' => $this->searchGroups($booleanQuery, false),
            'pages' => $this->searchPages($booleanQuery, false),
            'shares' => $this->searchShares($booleanQuery, false),
            default => null,
        };

        if (! $paginator) {
            return response()->json(['html' => '', 'next_page' => null, 'has_more' => false]);
        }

        $html = view('search._'.$type.'_items', $this->itemsViewData($type, $paginator, $viewer))->render();

        return response()->json([
            'html' => $html,
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'has_more' => $paginator->hasMorePages(),
        ]);
    }

    private function itemsViewData(string $type, $paginator, $viewer): array
    {
        return match ($type) {
            'people' => ['users' => $paginator],
            'posts' => ['posts' => $paginator],
            'groups' => ['groups' => $paginator],
            'pages' => ['pages' => $paginator],
            'shares' => ['shares' => $paginator],
            default => [],
        };
    }
}
