<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Models\Journal;
use App\Services\PdfService;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    public function print(Journal $journal)
    {
        $journal->load(['journalLines.account', 'branch', 'poster']);
        return $this->pdfService->journalVoucher($journal);
    }
}

