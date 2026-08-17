<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Product;
use App\Models\User;
use App\Services\GiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GiftController extends Controller
{
    public function __construct(private readonly GiftService $gifts) {}

    public function index(Request $request): View
    {
        return view('gifts.index', [
            'received' => $request->user()->receivedGifts()->with('sender', 'product')->latest()->get(),
            'sent' => $request->user()->sentGifts()->with('receiver', 'product')->latest()->get(),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $receiver = User::query()->where('name', $data['username'])->orWhere('email', $data['username'])->first();

        if (! $receiver) {
            throw ValidationException::withMessages(['username' => 'We could not find that player.']);
        }

        $this->gifts->purchaseAndSend($request->user(), $receiver, $product, $data['message'] ?? null);

        return back()->with('success', "Gift sent to {$receiver->name}!");
    }

    public function claim(Request $request, Gift $gift): RedirectResponse
    {
        $this->gifts->claim($gift, $request->user());

        return back()->with('success', 'Gift claimed and added to your inventory!');
    }
}
