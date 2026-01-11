<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\Voucher;
use App\Models\User;

class VoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('account.vouchers.view');
    }

    public function view(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('account.vouchers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('account.vouchers.create');
    }

    public function update(User $user, Voucher $voucher): bool
    {
        if (!$user->hasPermissionTo('account.vouchers.update')) {
            return false;
        }

        return $voucher->canBeEdited();
    }

    public function delete(User $user, Voucher $voucher): bool
    {
        if (!$user->hasPermissionTo('account.vouchers.update')) {
            return false;
        }

        return $voucher->canBeEdited();
    }

    public function post(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('account.vouchers.post')
            && $voucher->isDraft();
    }

    public function cancel(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('account.vouchers.cancel')
            && $voucher->isPosted();
    }

    public function print(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('account.vouchers.print');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('account.vouchers.export');
    }
}
