<?php

namespace App\Domain\Accounting\Events;

use App\Domain\Accounting\Models\Journal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JournalPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Journal $journal
    ) {
    }
}

