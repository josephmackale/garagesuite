<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryService;

class InventoryItemController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of the inventory items.
     */
    public function index(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $query = InventoryItem::where('garage_id', $garageId);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%')
                  ->orWhere('brand', 'like', '%' . $search . '%')
                  ->orWhere('part_number', 'like', '%' . $search . '%');
            });
        }

        $items = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.index', [
            'items'  => $items,
            'search' => $search ?? null,
        ]);
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create()
    {
        return view('inventory.create');
    }

    /**
     * Store a newly created inventory item in storage.
     */
    public function store(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['nullable', 'string', 'max:255'],
            'brand'         => ['nullable', 'string', 'max:255'],
            'part_number'   => ['nullable', 'string', 'max:255'],
            'unit'          => ['required', 'string', 'max:50'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'status'        => ['required', 'in:active,inactive'],
        ]);

        $validated['garage_id'] = $garageId;
        $validated['current_stock'] = $validated['current_stock'] ?? 0;

        InventoryItem::create($validated);

        return redirect()
            ->route('inventory-items.index')
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display the specified inventory item.
     */
    public function show(InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        return view('inventory.show', [
            'item' => $inventoryItem,
        ]);
    }

    /**
     * Show the form for editing the specified inventory item.
     */
    public function edit(InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        return view('inventory.edit', [
            'item' => $inventoryItem,
        ]);
    }

    /**
     * Update the specified inventory item in storage.
     */
    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['nullable', 'string', 'max:255'],
            'brand'         => ['nullable', 'string', 'max:255'],
            'part_number'   => ['nullable', 'string', 'max:255'],
            'unit'          => ['required', 'string', 'max:50'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'status'        => ['required', 'in:active,inactive'],
        ]);

        $inventoryItem->update($validated);

        return redirect()
            ->route('inventory-items.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified inventory item from storage.
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        $inventoryItem->delete();

        return redirect()
            ->route('inventory-items.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    /**
     * Show the Adjust Stock form.
     */
    public function adjustForm(InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        return view('inventory.adjust', [
            'item' => $inventoryItem,
        ]);
    }

    /**
     * Handle Adjust Stock submission.
     */
    public function adjust(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'mode'     => ['required', 'in:increase,decrease,set'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason'   => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $mode     = $validated['mode'];
            $quantity = (int) $validated['quantity'];
            $reason   = $validated['reason'] ?? null;

            if ($mode === 'increase') {
                $this->inventoryService->increaseStock($inventoryItem, $quantity, $reason);
            } elseif ($mode === 'decrease') {
                $this->inventoryService->decreaseStock($inventoryItem, $quantity, $reason);
            } else {
                $this->inventoryService->adjustStock($inventoryItem, $quantity, $reason);
            }

            return redirect()
                ->route('inventory-items.index')
                ->with('success', 'Stock updated successfully.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['stock' => $e->getMessage()]);
        }
    }

    /**
     * Ensure the inventory item belongs to the current user's garage.
     */
    protected function authorizeItem(InventoryItem $inventoryItem): void
    {
        $userGarageId = Auth::user()->garage_id;

        if ($inventoryItem->garage_id !== $userGarageId) {
            abort(403, 'You are not allowed to access this inventory item.');
        }
    }
}
