<?php

namespace App\Providers;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Accounting\Events\RefundCreated;
use App\Domain\Accounting\Listeners\CreateArInvoice;
use App\Domain\Accounting\Listeners\PostCashReceipt;
use App\Domain\Accounting\Listeners\PostRefundEntry;
use App\Domain\Accounting\Listeners\PostDeferredRevenue;
use App\Domain\Accounting\Listeners\RecognizeRevenue;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\EnrollmentCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EnrollmentCreated::class => [
            CreateArInvoice::class,
            PostDeferredRevenue::class,
        ],
        PaymentPaid::class => [
            PostCashReceipt::class,
        ],
        EnrollmentCompleted::class => [
            RecognizeRevenue::class,
        ],
        RefundCreated::class => [
            PostRefundEntry::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

