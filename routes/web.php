<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ChunkedUploadController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\GroupPostController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageMemberController;
use App\Http\Controllers\PagePostController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.switch');
Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::match(['get', 'post'], 'logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::view('/dashboard', 'home.dashboard')->name('dashboard');

    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::patch('privacy', [SettingsController::class, 'updatePrivacy'])->name('privacy');
        Route::post('block', [SettingsController::class, 'blockUser'])->name('block');
        Route::post('block/{user}', [SettingsController::class, 'blockProfileUser'])->name('block-profile');
        Route::delete('block/selected', [SettingsController::class, 'unblockSelected'])->name('unblock-selected');
        Route::delete('block/{user}', [SettingsController::class, 'unblockUser'])->name('unblock');
        Route::delete('delete-account', [SettingsController::class, 'deleteAccount'])->name('delete-account');
    });

    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::post('posts/{post}/reactions', [ReactionController::class, 'store'])->name('posts.reactions.store');
    Route::post('comments/{comment}/reactions', [ReactionController::class, 'storeOnComment'])->name('comments.reactions.store');

    Route::get('posts/{post}/shares', [ShareController::class, 'index'])->name('posts.shares.index');
    Route::post('posts/{post}/shares', [ShareController::class, 'store'])->name('posts.shares.store');

    Route::get('posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments.index');
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::match(['put', 'patch'], 'comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::prefix('pages')->name('pages.')->group(function (): void {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}', [PageController::class, 'show'])->name('show');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');

        Route::get('/{page}/members', [PageMemberController::class, 'index'])->name('members.index');
        Route::post('/{page}/members', [PageMemberController::class, 'store'])->name('members.join');
        Route::post('/{page}/leave', [PageMemberController::class, 'destroy'])->name('members.leave');
        Route::patch('/{page}/members/{user}', [PageMemberController::class, 'update'])->name('members.update');
        Route::delete('/{page}/members/{user}', [PageMemberController::class, 'remove'])->name('members.remove');

        Route::get('/{page}/posts', [PagePostController::class, 'index'])->name('posts.index');
        Route::get('/{page}/posts/create', [PagePostController::class, 'create'])->name('posts.create');
        Route::post('/{page}/posts', [PagePostController::class, 'store'])->name('posts.store');
        Route::get('/{page}/posts/{post}', [PagePostController::class, 'show'])->name('posts.show');
        Route::get('/{page}/posts/{post}/edit', [PagePostController::class, 'edit'])->name('posts.edit');
        Route::match(['put', 'patch'], '/{page}/posts/{post}', [PagePostController::class, 'update'])->name('posts.update');
        Route::delete('/{page}/posts/{post}', [PagePostController::class, 'destroy'])->name('posts.destroy');
    });

    Route::prefix('groups')->name('groups.')->group(function (): void {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create', [GroupController::class, 'create'])->name('create');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');

        Route::get('/{group}/members', [GroupMemberController::class, 'index'])->name('members.index');
        Route::post('/{group}/members', [GroupMemberController::class, 'store'])->name('members.join');
        Route::post('/{group}/leave', [GroupMemberController::class, 'destroy'])->name('members.leave');
        Route::patch('/{group}/members/{user}', [GroupMemberController::class, 'update'])->name('members.update');
        Route::delete('/{group}/members/{user}', [GroupMemberController::class, 'remove'])->name('members.remove');

        Route::get('/{group}/posts', [GroupPostController::class, 'index'])->name('posts.index');
        Route::get('/{group}/posts/create', [GroupPostController::class, 'create'])->name('posts.create');
        Route::post('/{group}/posts', [GroupPostController::class, 'store'])->name('posts.store');
        Route::get('/{group}/posts/{post}', [GroupPostController::class, 'show'])->name('posts.show');
        Route::post('/{group}/posts/{post}/shares', [ShareController::class, 'storeInGroup'])->name('posts.shares.store');
        Route::get('/{group}/posts/{post}/edit', [GroupPostController::class, 'edit'])->name('posts.edit');
        Route::match(['put', 'patch'], '/{group}/posts/{post}', [GroupPostController::class, 'update'])->name('posts.update');
        Route::delete('/{group}/posts/{post}', [GroupPostController::class, 'destroy'])->name('posts.destroy');
    });

    Route::prefix('uploads')->group(function (): void {
        Route::post('initiate', [ChunkedUploadController::class, 'initiate'])->name('uploads.initiate');
        Route::post('chunk', [ChunkedUploadController::class, 'uploadChunk'])->name('uploads.chunk');
        Route::post('complete', [ChunkedUploadController::class, 'complete'])->name('uploads.complete');
        Route::get('status', [ChunkedUploadController::class, 'status'])->name('uploads.status');
        Route::delete('cancel', [ChunkedUploadController::class, 'cancel'])->name('uploads.cancel');
    });

    Route::get('search', [SearchController::class, 'index'])->name('search');

    Route::get('friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('friends/{user}', [FriendController::class, 'send'])->name('friends.send');
    Route::post('friends/requests/{friendship}/accept', [FriendController::class, 'accept'])->name('friends.accept');
    Route::post('friends/requests/{friendship}/reject', [FriendController::class, 'reject'])->name('friends.reject');
    Route::post('friends/requests/{friendship}/cancel', [FriendController::class, 'cancel'])->name('friends.cancel');
    Route::delete('friends/{user}', [FriendController::class, 'destroy'])->name('friends.destroy');

    Route::get('messenger', [MessengerController::class, 'index'])->name('messenger.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('messenger/search', [MessengerController::class, 'search'])->name('messenger.search');
    Route::get('messenger/unread-count', [MessengerController::class, 'unreadCount'])->name('messenger.unread-count');
    Route::post('messenger/start', [MessengerController::class, 'start'])->name('messenger.start');
    Route::get('messenger/{conversation}', [MessengerController::class, 'show'])->name('messenger.show');
    Route::get('messenger/{conversation}/messages', [MessageController::class, 'index'])->name('messenger.messages.index');

    Route::prefix('messenger/{conversation}')->group(function (): void {
        Route::post('messages', [MessageController::class, 'store'])->name('messenger.messages.store');
        Route::post('read', [MessageController::class, 'markAsRead'])->name('messenger.messages.read');
        Route::post('typing', [MessengerController::class, 'typing'])->name('messenger.messages.typing');
        Route::post('presence', [MessengerController::class, 'presence'])->name('messenger.presence');
        Route::post('presence/leave', [MessengerController::class, 'leavePresence'])->name('messenger.presence.leave');
    });

    Route::post('messenger/upload', [MessageController::class, 'upload'])->name('messenger.upload');

    Route::get('messenger/conversation-item/{conversation}', [MessengerController::class, 'conversationItem'])->name('messenger.conversation-item');

    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('profile/{user}', [ProfileController::class, 'show'])->name('profile.show');
