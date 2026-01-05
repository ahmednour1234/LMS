<?php

namespace App\Providers;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Category;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\Accounting\Policies\AccountPolicy;
use App\Domain\Accounting\Policies\ArInvoicePolicy;
use App\Domain\Accounting\Policies\CategoryPolicy;
use App\Domain\Accounting\Policies\CostCenterPolicy;
use App\Domain\Accounting\Policies\JournalLinePolicy;
use App\Domain\Accounting\Policies\JournalPolicy;
use App\Domain\Accounting\Policies\PaymentMethodPolicy;
use App\Domain\Branch\Models\Branch;
use App\Domain\Media\Models\MediaFile;
use App\Domain\Media\Policies\MediaFilePolicy;
use App\Models\Setting;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Branch::class => BranchPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Account::class => AccountPolicy::class,
        Journal::class => JournalPolicy::class,
        JournalLine::class => JournalLinePolicy::class,
        Category::class => CategoryPolicy::class,
        CostCenter::class => CostCenterPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        ArInvoice::class => ArInvoicePolicy::class,
        MediaFile::class => MediaFilePolicy::class,
        Setting::class => SettingPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
