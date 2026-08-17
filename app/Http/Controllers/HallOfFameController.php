<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HallOfFameController extends Controller
{
    private const CACHE_TTL_SECONDS = 300;

    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'wins');

        $leaderboard = Cache::remember("hall_of_fame:{$sort}", self::CACHE_TTL_SECONDS, function () use ($sort) {
            $query = User::query()
                ->with('profile')
                ->where('is_active', true);

            if ($sort === 'wallet') {
                $query->orderByDesc('wallet_balance');
            } else {
                $query->join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->orderByDesc('profiles.total_wins')
                    ->select('users.*');
            }

            return $query->limit(100)->get();
        });

        return view('hall-of-fame', ['leaderboard' => $leaderboard, 'sort' => $sort]);
    }
}
