<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel: anyone can watch live odds and frame updates for a match
// (spectating/VOD viewing party + live betting board).
Broadcast::channel('match.{matchId}', function () {
    return true;
});

// Public channel: tournament bracket updates, visible to all participants
// as soon as the bracket is shuffled/seeded.
Broadcast::channel('tournament.{tournamentId}', function () {
    return true;
});
