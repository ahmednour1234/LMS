<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('payment_methods.viewAny');
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        if (!$user->hasPermissionTo('payment_methods.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Payment methods are generally accessible to all authorized users
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('payment_methods.create');
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        if (!$user->hasPermissionTo('payment_methods.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Payment methods can be updated by authorized users
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        if (!$user->hasPermissionTo('payment_methods.delete')) {
            return false;
        }

        return $user->isSuperAdmin(); // Only super admin can delete payment methods
    }
}

