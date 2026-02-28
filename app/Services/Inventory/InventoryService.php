<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use Illuminate\Support\Facades\Auth;
use Exception;

class InventoryService
{
    /**
     * Record a movement and update stock automatically.
     */
    protected function recordMovement(
        InventoryItem $item,
        int $quantity,
        string $type,
        ?string $reason = null,
        ?int $jobId = null
    ): InventoryItemMovement {

        // Validate positive quantity
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }

        // Allowed movement types
        $allowedTypes = ['in', 'out', 'adjustment_in', 'adjustment_out'];
        if (!in_array($type, $allowedTypes)) {
            throw new Exception("Invalid stock movement type: {$type}");
        }

        $user = Auth::user();

        // Build the movement
        $movement = InventoryItemMovement::create([
            'garage_id'         => $user->garage_id,
            'inventory_item_id' => $item->id,
            'type'              => $type,
            'quantity'          => $quantity,
            'reason'            => $reason,
            'job_id'            => $jobId,
            'created_by'        => $user->id,
        ]);

        // Update stock based on type
        if (in_array($type, ['in', 'adjustment_in'])) {
            $item->current_stock += $quantity;
        } else {
            // Prevent negative stock
            if ($item->current_stock - $quantity < 0) {
                throw new Exception("Cannot reduce stock below zero for item {$item->name}.");
            }
            $item->current_stock -= $quantity;
        }

        $item->save();

        return $movement;
    }


    /**
     * Increase stock (Purchase / Restock)
     */
    public function increaseStock(InventoryItem $item, int $quantity, ?string $reason = null)
    {
        return $this->recordMovement($item, $quantity, 'in', $reason);
    }


    /**
     * Decrease stock (Manual removal)
     */
    public function decreaseStock(InventoryItem $item, int $quantity, ?string $reason = null)
    {
        return $this->recordMovement($item, $quantity, 'out', $reason);
    }


    /**
     * Adjust stock by setting a new quantity.
     * Difference-based adjustment (Option 1).
     */
    public function adjustStock(InventoryItem $item, int $newQuantity, ?string $reason = null)
    {
        if ($newQuantity < 0) {
            throw new Exception("Adjusted quantity cannot be negative.");
        }

        $current = $item->current_stock;

        // No change
        if ($newQuantity == $current) {
            return null;
        }

        // Adjustment IN
        if ($newQuantity > $current) {
            $difference = $newQuantity - $current;
            return $this->recordMovement($item, $difference, 'adjustment_in', $reason);
        }

        // Adjustment OUT
        if ($newQuantity < $current) {
            $difference = $current - $newQuantity;
            return $this->recordMovement($item, $difference, 'adjustment_out', $reason);
        }
    }


    /**
     * Deduct stock for a job (Job Part Used)
     */
    public function deductForJob(InventoryItem $item, int $quantity, int $jobId)
    {
        return $this->recordMovement($item, $quantity, 'out', "Used for job #{$jobId}", $jobId);
    }
}
