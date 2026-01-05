<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'invoice_no',
        'pdf_file_id',
        'issued_at',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function pdfFile(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Media\Models\MediaFile::class, 'pdf_file_id');
    }
}

