<?php

namespace App\Providers;

use App\Domain\Accounting\Events\InvoiceGenerated;
use App\Domain\Accounting\Events\JournalPosted;
use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Accounting\Events\RefundCreated;
use App\Domain\Accounting\Listeners\CreateArInvoice;
use App\Domain\Accounting\Listeners\LogEnrollmentCompleted;
use App\Domain\Accounting\Listeners\LogEnrollmentCreated;
use App\Domain\Accounting\Listeners\LogInvoiceGenerated;
use App\Domain\Accounting\Listeners\LogJournalPosted;
use App\Domain\Accounting\Listeners\LogPaymentPaid;
use App\Domain\Accounting\Listeners\LogRefundCreated;
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
            LogEnrollmentCreated::class,
        ],
        PaymentPaid::class => [
            PostCashReceipt::class,
            LogPaymentPaid::class,
        ],
        EnrollmentCompleted::class => [
            RecognizeRevenue::class,
            LogEnrollmentCompleted::class,
        ],
        RefundCreated::class => [
            PostRefundEntry::class,
            LogRefundCreated::class,
        ],
        JournalPosted::class => [
            LogJournalPosted::class,
        ],
        InvoiceGenerated::class => [
            LogInvoiceGenerated::class,
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

