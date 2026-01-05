<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\ArInvoice;
use App\Models\User;

class ArInvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     * Allow Admin/Accountant roles to view AR invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole(['admin', 'accountant']);
    }

    /**
     * Determine whether the user can view the model.
     * Allow Admin/Accountant roles to view AR invoices.
     */
    public function view(User $user, ArInvoice $arInvoice): bool
    {
        return $user->isSuperAdmin() || $user->hasRole(['admin', 'accountant']);
    }

    /**
     * Determine whether the user can create models.
     * AR invoices are generated from enrollment - no manual creation allowed.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     * AR invoices cannot be manually updated - due_amount is computed.
     */
    public function update(User $user, ArInvoice $arInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * No one can delete AR invoices.
     */
    public function delete(User $user, ArInvoice $arInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ArInvoice $arInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ArInvoice $arInvoice): bool
    {
        return false;
    }
}

