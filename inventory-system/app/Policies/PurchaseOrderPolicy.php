<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && ($user->isAdmin() || $purchaseOrder->created_by === $user->getKey());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isAdmin() && $purchaseOrder->status === 'draft';
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('admin', 'manager') && $purchaseOrder->canBeApproved();
    }

    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('admin', 'manager') && $purchaseOrder->canBeApproved();
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('admin', 'manager') && $purchaseOrder->status === 'approved';
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('admin', 'warehouse_staff') && $purchaseOrder->canBeReceived();
    }
}
