<?php

namespace App\Http\Controllers;

use App\Models\Escrow;
use App\Models\InventoryItem;
use App\Services\EscrowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscrowController extends Controller
{
    public function __construct(private readonly EscrowService $escrow) {}

    public function index(): View
    {
        $listings = Escrow::query()
            ->where('status', 'pending')
            ->whereNull('buyer_id')
            ->with('seller', 'product')
            ->latest()
            ->paginate(20);

        return view('escrow.index', ['listings' => $listings]);
    }

    public function myListings(Request $request): View
    {
        $selling = Escrow::query()->where('seller_id', $request->user()->id)->with('product', 'buyer')->latest()->get();
        $buying = Escrow::query()->where('buyer_id', $request->user()->id)->with('product', 'seller')->latest()->get();

        return view('escrow.mine', ['selling' => $selling, 'buying' => $buying]);
    }

    public function create(): View
    {
        $tradeableItems = InventoryItem::query()
            ->where('user_id', auth()->id())
            ->whereHas('product', fn ($q) => $q->where('is_tradeable', true))
            ->with('product')
            ->get();

        return view('escrow.create', ['tradeableItems' => $tradeableItems]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'in:USD,GBP,EUR,NGN,BTC,USDT'],
        ]);

        $item = InventoryItem::query()->findOrFail($data['inventory_item_id']);

        $this->escrow->listItem(
            $request->user(),
            $item,
            (string) $data['amount'],
            $data['currency'],
            $data['title'],
            $data['description'] ?? null,
        );

        return redirect()->route('escrow.mine')->with('success', 'Listing created!');
    }

    public function fund(Request $request, Escrow $escrow): RedirectResponse
    {
        $this->escrow->fund($escrow, $request->user());

        return back()->with('success', 'Funds held in escrow. Awaiting delivery confirmation.');
    }

    public function dispute(Request $request, Escrow $escrow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string']]);

        abort_unless(in_array($request->user()->id, [$escrow->seller_id, $escrow->buyer_id]), 403);

        $this->escrow->dispute($escrow, $data['reason']);

        return back()->with('success', 'Dispute raised. An admin will review this listing.');
    }
}
