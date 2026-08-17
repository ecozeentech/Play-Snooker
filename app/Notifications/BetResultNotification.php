<?php

namespace App\Notifications;

use App\Models\Bet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BetResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Bet $bet) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'bet_id' => $this->bet->id,
            'status' => $this->bet->status,
            'payout' => $this->bet->payout,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $won = $this->bet->status === 'won';

        return (new WebPushMessage)
            ->title($won ? '🎉 Your bet won!' : 'Bet settled')
            ->icon('/icons/icon-192.png')
            ->body($won
                ? "You won {$this->bet->payout} {$this->bet->currency}!"
                : 'Your bet did not win this time. Better luck next match!')
            ->data(['url' => route('bets.index')]);
    }
}
