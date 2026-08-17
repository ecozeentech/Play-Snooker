<?php

namespace App\Notifications;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MatchInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public GameMatch $match) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'match_id' => $this->match->id,
            'message' => "You've been challenged to a match!",
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Play Snooker — Match invitation')
            ->icon('/icons/icon-192.png')
            ->body('You have a new match waiting. Tap to accept the challenge!')
            ->action('View match', 'view_match')
            ->data(['url' => route('game.show', $this->match)]);
    }
}
