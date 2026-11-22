<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Policies\CommentPolicy;
use App\Policies\GroupMemberPolicy;
use App\Policies\GroupPolicy;
use App\Policies\PageMemberPolicy;
use App\Policies\PagePolicy;
use App\Policies\PostPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Email;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(PageMember::class, PageMemberPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(GroupMember::class, GroupMemberPolicy::class);

        Broadcast::routes(['middleware' => ['auth']]);

        Broadcast::channel('user.{userId}', function ($user, int $userId) {
            return $user->id === $userId;
        });

        Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
            return Conversation::query()
                ->where('id', $conversationId)
                ->where(function ($q) use ($user) {
                    $q->where('user_one_id', $user->id)
                        ->orWhere('user_two_id', $user->id);
                })
                ->exists();
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            $mail = (new MailMessage)
                ->subject(Lang::get('Reset Password Notification'))
                ->greeting(Lang::get('Hello!'))
                ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
                ->line(new HtmlString('<a href="'.$url.'" target="_blank" rel="noopener noreferrer" style="display:inline-block; color:#0866ff !important; text-decoration:underline !important; word-break:break-all; cursor:pointer;">'.$url.'</a>'))
                ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => $expire]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'));

            $mail->withSymfonyMessage(function (Email $message) use ($url): void {
                $text = Lang::get('Reset Password Notification')."\n\n"
                    .Lang::get('You are receiving this email because we received a password reset request for your account.')."\n\n"
                    .Lang::get('Reset Password').': '.$url."\n\n"
                    .Lang::get('If you did not request a password reset, no further action is required.');
                $message->text($text);
            });

            return $mail;
        });

        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                ['id' => $id, 'hash' => $hash]
            );
        });
    }
}
