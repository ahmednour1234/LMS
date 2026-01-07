<?php

namespace App\Services;

use App\Domain\Accounting\Models\ArInstallment;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * InstallmentService - Handles installment schedule generation and payment allocation.
 * 
 * Features:
 * - Deterministic schedule generation with configurable intervals
 * - Rounding rules: last installment absorbs rounding difference
 * - Payment allocation: always pays earliest due first
 * - Validation for down payment and installment constraints
 */
class InstallmentService
{
    /**
     * Generate an installment schedule for an invoice.
     * 
     * @param ArInvoice $invoice
     * @param float $downPayment Initial down payment amount
     * @param int $numberOfInstallments Number of installments (excluding down payment)
     * @param string $interval 'weekly', 'biweekly', 'monthly'
     * @param Carbon|null $startDate First installment due date (defaults to today)
     * @return Collection<ArInstallment>
     */
    public function generateSchedule(
        ArInvoice $invoice,
        float $downPayment,
        int $numberOfInstallments,
        string $interval = 'monthly',
        ?Carbon $startDate = null
    ): Collection {
        $total = (float) $invoice->total_amount;
        $startDate = $startDate ?? Carbon::today();

        // Validate inputs
        $validation = $this->validateScheduleParams($total, $downPayment, $numberOfInstallments);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(implode(' ', $validation['errors']));
        }

        // Calculate remaining amount after down payment
        $remainingAmount = $total - $downPayment;
        
        // Calculate base installment amount
        $baseInstallmentAmount = $this->round($remainingAmount / $numberOfInstallments);
        
        // Calculate rounding difference (last installment absorbs this)
        $totalFromBase = $baseInstallmentAmount * $numberOfInstallments;
        $roundingDiff = $this->round($remainingAmount - $totalFromBase);

        $installments = collect();

        return DB::transaction(function () use (
            $invoice,
            $downPayment,
            $numberOfInstallments,
            $baseInstallmentAmount,
            $roundingDiff,
            $startDate,
            $interval,
            $installments
        ) {
            // Delete existing installments for this invoice
            ArInstallment::where('ar_invoice_id', $invoice->id)->delete();

            // Create down payment installment (installment #0 or #1)
            if ($downPayment > 0) {
                $installments->push(ArInstallment::create([
                    'ar_invoice_id' => $invoice->id,
                    'installment_no' => 1,
                    'due_date' => Carbon::today(), // Down payment due immediately
                    'amount' => $this->round($downPayment),
                    'status' => 'pending',
                    'paid_amount' => 0,
                ]));
            }

            // Create remaining installments
            $currentDate = $startDate;
            $installmentOffset = $downPayment > 0 ? 1 : 0;

            for ($i = 1; $i <= $numberOfInstallments; $i++) {
                $amount = $baseInstallmentAmount;
                
                // Last installment absorbs rounding difference
                if ($i === $numberOfInstallments) {
                    $amount = $this->round($baseInstallmentAmount + $roundingDiff);
                }

                $installments->push(ArInstallment::create([
                    'ar_invoice_id' => $invoice->id,
                    'installment_no' => $i + $installmentOffset,
                    'due_date' => $currentDate->copy(),
                    'amount' => $amount,
                    'status' => 'pending',
                    'paid_amount' => 0,
                ]));

                // Move to next due date
                $currentDate = $this->getNextDueDate($currentDate, $interval);
            }

            return $installments;
        });
    }

    /**
     * Allocate a payment to installments (earliest due first).
     * 
     * @param Payment $payment
     * @param ArInvoice $invoice
     * @return array Allocation details
     */
    public function allocatePayment(Payment $payment, ArInvoice $invoice): array
    {
        $remainingAmount = (float) $payment->amount;
        $allocations = [];

        // Get unpaid installments ordered by due date (earliest first)
        $installments = $invoice->arInstallments()
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->orderBy('installment_no', 'asc')
            ->get();

        return DB::transaction(function () use ($payment, $installments, $remainingAmount, &$allocations) {
            foreach ($installments as $installment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $dueAmount = (float) $installment->amount - (float) $installment->paid_amount;
                
                if ($dueAmount <= 0) {
                    continue;
                }

                // Allocate as much as possible to this installment
                $allocationAmount = min($remainingAmount, $dueAmount);
                
                $newPaidAmount = $this->round((float) $installment->paid_amount + $allocationAmount);
                $installment->paid_amount = $newPaidAmount;

                // Update status
                if ($newPaidAmount >= (float) $installment->amount) {
                    $installment->status = 'paid';
                    $installment->paid_at = now();
                }

                $installment->save();

                $allocations[] = [
                    'installment_id' => $installment->id,
                    'installment_no' => $installment->installment_no,
                    'allocated_amount' => $allocationAmount,
                    'new_paid_amount' => $newPaidAmount,
                    'status' => $installment->status,
                ];

                $remainingAmount = $this->round($remainingAmount - $allocationAmount);
            }

            // Link payment to first allocated installment (for reference)
            if (!empty($allocations) && !$payment->installment_id) {
                $payment->installment_id = $allocations[0]['installment_id'];
                $payment->save();
            }

            return [
                'payment_id' => $payment->id,
                'total_allocated' => $this->round((float) $payment->amount - $remainingAmount),
                'unallocated' => $remainingAmount,
                'allocations' => $allocations,
            ];
        });
    }

    /**
     * Check for overdue installments and update their status.
     * 
     * @param ArInvoice|null $invoice If null, checks all invoices
     * @return int Number of installments marked overdue
     */
    public function updateOverdueStatus(?ArInvoice $invoice = null): int
    {
        $query = ArInstallment::where('status', 'pending')
            ->where('due_date', '<', Carbon::today());

        if ($invoice) {
            $query->where('ar_invoice_id', $invoice->id);
        }

        return $query->update(['status' => 'overdue']);
    }

    /**
     * Get installment summary for an invoice.
     * 
     * @param ArInvoice $invoice
     * @return array
     */
    public function getInstallmentSummary(ArInvoice $invoice): array
    {
        $installments = $invoice->arInstallments()->orderBy('installment_no')->get();

        if ($installments->isEmpty()) {
            return [
                'has_installments' => false,
                'total_installments' => 0,
                'paid_installments' => 0,
                'pending_installments' => 0,
                'overdue_installments' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'next_due_date' => null,
                'next_due_amount' => null,
            ];
        }

        $paidCount = $installments->where('status', 'paid')->count();
        $pendingCount = $installments->where('status', 'pending')->count();
        $overdueCount = $installments->where('status', 'overdue')->count();

        $totalAmount = $installments->sum('amount');
        $paidAmount = $installments->sum('paid_amount');

        $nextDue = $installments
            ->whereIn('status', ['pending', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return [
            'has_installments' => true,
            'total_installments' => $installments->count(),
            'paid_installments' => $paidCount,
            'pending_installments' => $pendingCount,
            'overdue_installments' => $overdueCount,
            'total_amount' => $this->round($totalAmount),
            'paid_amount' => $this->round($paidAmount),
            'remaining_amount' => $this->round($totalAmount - $paidAmount),
            'next_due_date' => $nextDue?->due_date?->format('Y-m-d'),
            'next_due_amount' => $nextDue ? $this->round((float) $nextDue->amount - (float) $nextDue->paid_amount) : null,
        ];
    }

    /**
     * Validate schedule generation parameters.
     * 
     * @param float $total Invoice total
     * @param float $downPayment
     * @param int $numberOfInstallments
     * @param float|null $minDownPayment Minimum down payment required
     * @param int|null $maxInstallments Maximum installments allowed
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateScheduleParams(
        float $total,
        float $downPayment,
        int $numberOfInstallments,
        ?float $minDownPayment = null,
        ?int $maxInstallments = null
    ): array {
        $errors = [];

        if ($total <= 0) {
            $errors[] = 'Invoice total must be greater than zero.';
        }

        if ($downPayment < 0) {
            $errors[] = 'Down payment cannot be negative.';
        }

        if ($downPayment > $total) {
            $errors[] = 'Down payment cannot exceed invoice total.';
        }

        if ($minDownPayment !== null && $downPayment < $minDownPayment) {
            $errors[] = "Down payment must be at least {$minDownPayment}.";
        }

        if ($numberOfInstallments < 1) {
            $errors[] = 'Number of installments must be at least 1.';
        }

        if ($maxInstallments !== null && $numberOfInstallments > $maxInstallments) {
            $errors[] = "Number of installments cannot exceed {$maxInstallments}.";
        }

        // Each installment should have a meaningful amount
        $remainingAmount = $total - $downPayment;
        if ($numberOfInstallments > 0 && $remainingAmount / $numberOfInstallments < 0.001) {
            $errors[] = 'Installment amount would be too small.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate down payment against course price settings.
     * 
     * @param float $downPayment
     * @param float $total
     * @param float|null $minDownPayment From CoursePrice
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateDownPayment(float $downPayment, float $total, ?float $minDownPayment = null): array
    {
        $errors = [];

        if ($downPayment < 0) {
            $errors[] = 'Down payment cannot be negative.';
        }

        if ($downPayment > $total) {
            $errors[] = 'Down payment cannot exceed total amount.';
        }

        if ($minDownPayment !== null && $downPayment < $minDownPayment) {
            $errors[] = "Minimum down payment required is {$minDownPayment}.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate the next due date based on interval.
     * 
     * @param Carbon $currentDate
     * @param string $interval
     * @return Carbon
     */
    protected function getNextDueDate(Carbon $currentDate, string $interval): Carbon
    {
        return match ($interval) {
            'weekly' => $currentDate->addWeek(),
            'biweekly' => $currentDate->addWeeks(2),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            default => $currentDate->addMonth(),
        };
    }

    /**
     * Round monetary value to configured precision.
     * 
     * @param float $value
     * @return float
     */
    protected function round(float $value): float
    {
        $precision = config('money.precision', 3);
        return round($value, $precision);
    }

    /**
     * Preview installment schedule without creating records.
     * 
     * @param float $total
     * @param float $downPayment
     * @param int $numberOfInstallments
     * @param string $interval
     * @param Carbon|null $startDate
     * @return array
     */
    public function previewSchedule(
        float $total,
        float $downPayment,
        int $numberOfInstallments,
        string $interval = 'monthly',
        ?Carbon $startDate = null
    ): array {
        $startDate = $startDate ?? Carbon::today();
        
        $validation = $this->validateScheduleParams($total, $downPayment, $numberOfInstallments);
        if (!$validation['valid']) {
            return [
                'valid' => false,
                'errors' => $validation['errors'],
                'schedule' => [],
            ];
        }

        $remainingAmount = $total - $downPayment;
        $baseInstallmentAmount = $this->round($remainingAmount / $numberOfInstallments);
        $totalFromBase = $baseInstallmentAmount * $numberOfInstallments;
        $roundingDiff = $this->round($remainingAmount - $totalFromBase);

        $schedule = [];

        // Down payment
        if ($downPayment > 0) {
            $schedule[] = [
                'installment_no' => 1,
                'due_date' => Carbon::today()->format('Y-m-d'),
                'amount' => $this->round($downPayment),
                'type' => 'down_payment',
            ];
        }

        // Regular installments
        $currentDate = $startDate;
        $installmentOffset = $downPayment > 0 ? 1 : 0;

        for ($i = 1; $i <= $numberOfInstallments; $i++) {
            $amount = $baseInstallmentAmount;
            
            if ($i === $numberOfInstallments) {
                $amount = $this->round($baseInstallmentAmount + $roundingDiff);
            }

            $schedule[] = [
                'installment_no' => $i + $installmentOffset,
                'due_date' => $currentDate->format('Y-m-d'),
                'amount' => $amount,
                'type' => 'installment',
            ];

            $currentDate = $this->getNextDueDate($currentDate->copy(), $interval);
        }

        return [
            'valid' => true,
            'errors' => [],
            'schedule' => $schedule,
            'summary' => [
                'total' => $total,
                'down_payment' => $this->round($downPayment),
                'financed_amount' => $this->round($remainingAmount),
                'number_of_installments' => $numberOfInstallments,
                'installment_amount' => $baseInstallmentAmount,
                'last_installment_amount' => $this->round($baseInstallmentAmount + $roundingDiff),
                'interval' => $interval,
            ],
        ];
    }
}

