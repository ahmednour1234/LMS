<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\InvoiceGenerated;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Enrollment\Events\EnrollmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateArInvoice implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment;

        // Idempotency check: skip if AR invoice already exists
        $existingInvoice = ArInvoice::where('enrollment_id', $enrollment->id)->first();

        if ($existingInvoice) {
            Log::info('AR invoice already exists for enrollment', [
                'enrollment_id' => $enrollment->id,
                'ar_invoice_id' => $existingInvoice->id,
            ]);
            return;
        }

        // Ensure enrollment has user_id
        if (empty($enrollment->user_id)) {
            Log::error('Cannot create AR invoice: enrollment missing user_id', [
                'enrollment_id' => $enrollment->id,
            ]);
            return;
        }

        // Create AR invoice
        // Note: due_amount is computed automatically via accessor (total_amount - paid_amount)
        // The accessor will always compute it correctly when reading, so we don't need to set it
        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $enrollment->user_id, // Student user_id
            'branch_id' => $enrollment->branch_id,
            'total_amount' => $enrollment->total_amount,
            'status' => 'open',
            'issued_at' => now(),
            'created_by' => $enrollment->created_by,
        ]);

        // Set initial due_amount in database using raw query (bypasses model accessor)
        // This is for database consistency, but the accessor will always compute it correctly when reading
        DB::table('ar_invoices')
            ->where('id', $invoice->id)
            ->update(['due_amount' => $enrollment->total_amount]);

        // Fire event for audit logging
        event(new InvoiceGenerated($invoice));

        Log::info('AR invoice created for enrollment', [
            'enrollment_id' => $enrollment->id,
        ]);
    }
}

