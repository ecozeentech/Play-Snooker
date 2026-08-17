# Play Snooker — playsnooker.bet

A hybrid web platform bridging physical and digital snooker/pool tournaments, live betting, social multiplayer, and an in-game marketplace, built on Laravel 11.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3, Laravel 11 |
| Frontend | Blade + Tailwind CSS 3 + Alpine.js |
| Database | MySQL 8 in production (SQLite for local dev/tests) via Eloquent |
| Real-time | Laravel Reverb (WebSockets) for live odds, frame updates, bracket updates |
| Queues/cache | Redis-backed queues & cache (database/array drivers locally) |
| Admin | Filament 3 admin panel at `/admin` |
| Game engine | Vanilla Canvas 2D physics engine (`resources/js/game`) |
| PWA | Web app manifest, service worker, offline practice mode, Web Push notifications |
| Auth | Laravel Breeze (Blade stack), Socialite scaffolding for Google/Facebook |

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan webpush:vapid   # generates VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY for push notifications
touch database/database.sqlite
php artisan migrate --seed
npm run build   # or `npm run dev` while developing
php artisan serve
```

Seeding creates:

- An admin user: `admin@playsnooker.bet` / `password` (`is_admin = true`, access `/admin`).
- The default shop catalog (cues, boosters, table skins, avatar frames).
- Default achievements, currencies and payment-gateway system settings.
- In `local`/`testing` environments, 16 demo players, a demo tournament, and a live demo match.

### Running the full stack locally

```bash
php artisan serve                 # HTTP
php artisan queue:work            # background jobs (tournament shuffling, notifications, payouts)
php artisan reverb:start          # WebSocket server for live odds / bracket updates
npm run dev                       # Vite dev server with HMR
```

### Tests

```bash
php artisan test
./vendor/bin/pint          # code style
```

42 feature tests cover the platform's critical financial and gameplay flows: wallet funding (manual deposits, admin approval, withdrawals, insufficient-funds guards), bet placement & settlement (stake debiting, payout crediting, win/loss outcomes), and tournament registration (entry fees, duplicate/overflow guards, bracket shuffling & seeding, user-hosted tournament fees).

## Architecture

Business logic lives in a **Services + Repositories** layer (`app/Services`, `app/Repositories`) rather than in controllers, per the platform's complexity requirements:

- `WalletService` — the single source of truth for all balance mutations (deposits, withdrawals, bet stakes/payouts, purchases, gifts, escrow, referral rewards, admin overrides), always paired with an auditable `Transaction` record and row-locked to stay consistent under concurrent access.
- `CurrencyExchangeService` — resolves USD/GBP/EUR/NGN/BTC/USDT exchange rates (OpenExchangeRates + CoinGecko when configured, with safe static fallbacks otherwise).
- `BettingService` / `OddsService` / `BetRepository` — bet placement, dynamic odds calculation from player stats + live match flow, and automatic/manual settlement.
- `TournamentService` — registration, hosting-fee collection, fair bracket shuffling & seeding (single/double elimination, round robin), bracket progression, and automated prize payouts.
- `MatchFlowService` — drives a live match frame-by-frame, recalculating odds and broadcasting updates, and triggers bet settlement + bracket advancement once a match finishes.
- `EscrowService` — list → fund (hold) → release/refund/dispute flow for peer-to-peer digital asset trades.
- `ReferralService`, `GiftService`, `ShopService`, `AchievementService`, `AuditLogService` — referral rewards, gifting, shop purchases/equip, achievement badges, and a dedicated admin audit trail (kept separate from financial transactions).

Heavy/bulk operations are queued (`app/Jobs`): bulk tournament-start push notifications, tournament shuffling, and prize-pool settlement.

### Data model

All models specified in the brief are implemented under `app/Models` with matching migrations, factories and (where useful) query scopes: `User`, `Profile`, `Wallet`, `Transaction`, `Product`, `InventoryItem`, `Tournament`, `TournamentRegistration`, `GameMatch` (the `matches` table — named `GameMatch` since `Match` is a PHP reserved word), `Bet`, `Escrow`, `Advertisement`, `Referral`, `Gift`, `SystemSetting`, plus `AuditLog`, `Achievement`/`UserAchievement`, and `MatchReplay` to support the "top-tier extra features" (badges, replays, Hall of Fame).

### Admin panel

Filament resources at `/admin` provide full CRUD for users, tournaments, products, ads, escrow, bets, matches, system settings and achievements, plus manual-override actions with audit logging: force-settle a bet, force-finish a match (settles bets + advances the bracket), release/refund an escrow, shuffle & seed a tournament bracket, and adjust a user's wallet balance. `Transaction` and `AuditLog` are read-only ledgers.

### Game engine

`resources/js/game/engine.js` is a dependency-free Canvas 2D physics engine (ball-to-ball collisions, cushion rebounds, rolling friction, a lightweight cue-ball spin approximation, and touch/mouse "drag to aim, pull back for power" controls with haptic feedback). `practice.js` wires it up to a solo AI opponent (`ai.js`, three difficulty levels) at `/play/practice`. `multiplayer.js` wires the same engine into live 1v1 matches at `/play/matches/{match}`, with turn state, live odds and frame scores synced across clients via Laravel Reverb.

> **Scope note:** full real-time ball-position netcode between two browsers (streaming every physics tick) is out of scope for this pass — each frame is played out locally by whichever player holds the shot, and the *frame result* is what's synced live. This still delivers working turn-based multiplayer, live odds that react to match flow, and spectator/betting support; frame-by-frame ball-position replication is a natural next iteration.

### PWA

`public/manifest.json` + `public/service-worker.js` cache static build assets and the practice-mode page shell so solo practice keeps working offline; `public/offline.html` is the network-failure fallback. Web Push (`laravel-notification-channels/webpush`) sends native notifications for match invitations, bet results and tournament-start alerts — enable them from the account dropdown ("🔔 Enable notifications").

## Known limitations / follow-ups

- Automated payment gateways (Stripe, Coinbase Commerce, Binance Pay) are wired up as configuration + service placeholders (`config/services.php`, `.env.example`) but not connected to live SDKs — only the manual bank/crypto deposit-with-proof flow is fully implemented end-to-end, per the brief's "Admin configures manual... user uploads proof... admin manually verifies" flow.
- Google/Facebook social login has Socialite installed and config placeholders but the OAuth controller/routes are not yet wired up.
- Multiplayer is turn-based with server-synced frame results and live odds, not full real-time ball-position netcode (see note above).
- Multi-language UI translation (Laravel Localization / Lang packages) is not yet wired up; currency handling is fully multi-currency.
