<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(private readonly ShopService $shop) {}

    public function index(Request $request): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('type')
            ->orderBy('price')
            ->get()
            ->groupBy('type');

        return view('shop.index', ['products' => $products]);
    }

    public function purchase(Request $request, Product $product): RedirectResponse
    {
        $this->shop->purchase($request->user(), $product);

        return back()->with('success', "You purchased {$product->name}!");
    }

    public function inventory(Request $request): View
    {
        $items = InventoryItem::query()
            ->where('user_id', $request->user()->id)
            ->with('product')
            ->latest('acquired_at')
            ->get()
            ->groupBy(fn (InventoryItem $item) => $item->product->type);

        return view('shop.inventory', ['items' => $items]);
    }

    public function equip(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->shop->equip($request->user(), $item);

        return back()->with('success', "Equipped {$item->product->name}!");
    }
}
