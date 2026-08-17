<?php

namespace App\Notifications;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TournamentStartingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tournament $tournament) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tournament_id' => $this->tournament->id,
            'message' => "{$this->tournament->name} is starting now!",
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🏆 Tournament starting!')
            ->icon('/icons/icon-192.png')
            ->body("{$this->tournament->name} has been shuffled and is starting now. Good luck!")
            ->data(['url' => route('tournaments.show', $this->tournament)]);
    }
}
