<?php

use App\Http\Controllers\AdClickController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/hall-of-fame', [HallOfFameController::class, 'index'])->name('hall-of-fame');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])
    ->middleware('throttle:6,1')
    ->name('contact.submit');
Route::get('/ads/{ad}/click', [AdClickController::class, 'redirect'])->name('ads.click');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tournaments.
    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register'])->name('tournaments.register');
    Route::post('/tournaments/{tournament}/check-in', [TournamentController::class, 'checkIn'])->name('tournaments.check-in');

    // Live betting (rate-limited to prevent automated abuse of betting endpoints).
    Route::middleware('throttle:betting')->group(function () {
        Route::get('/bets', [BetController::class, 'index'])->name('bets.index');
        Route::post('/matches/{match}/bets', [BetController::class, 'store'])->name('bets.store');
    });

    // Game engine.
    Route::get('/play/practice', [GameController::class, 'practice'])->name('game.practice');
    Route::get('/play/lobby', [GameController::class, 'lobby'])->name('game.lobby');
    Route::get('/play/matches/{match}', [GameController::class, 'show'])->name('game.show');
    Route::post('/play/matches/{match}/frames', [GameController::class, 'recordFrame'])->name('game.frames.store');
    Route::post('/play/matches/{match}/replays', [GameController::class, 'saveReplay'])->name('game.replays.store');
    Route::get('/play/replays', [GameController::class, 'replays'])->name('game.replays.index');
    Route::get('/play/replays/{replay}', [GameController::class, 'showReplay'])->name('game.replays.show');

    // Shop, inventory & gifting.
    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::post('/shop/{product}/purchase', [ShopController::class, 'purchase'])->name('shop.purchase');
    Route::get('/inventory', [ShopController::class, 'inventory'])->name('shop.inventory');
    Route::post('/inventory/{item}/equip', [ShopController::class, 'equip'])->name('shop.equip');
    Route::get('/gifts', [GiftController::class, 'index'])->name('gifts.index');
    Route::post('/shop/{product}/gift', [GiftController::class, 'store'])->name('gifts.store');
    Route::post('/gifts/{gift}/claim', [GiftController::class, 'claim'])->name('gifts.claim');

    // Escrow marketplace.
    Route::get('/marketplace', [EscrowController::class, 'index'])->name('escrow.index');
    Route::get('/marketplace/mine', [EscrowController::class, 'myListings'])->name('escrow.mine');
    Route::get('/marketplace/create', [EscrowController::class, 'create'])->name('escrow.create');
    Route::post('/marketplace', [EscrowController::class, 'store'])->name('escrow.store');
    Route::post('/marketplace/{escrow}/fund', [EscrowController::class, 'fund'])->name('escrow.fund');
    Route::post('/marketplace/{escrow}/dispute', [EscrowController::class, 'dispute'])->name('escrow.dispute');

    // Push notifications.
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    // Wallet.
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/deposit/manual', [WalletController::class, 'depositManual'])->name('wallet.deposit.manual');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
    Route::post('/wallet/currency', [WalletController::class, 'switchCurrency'])->name('wallet.currency');
});

require __DIR__.'/auth.php';
