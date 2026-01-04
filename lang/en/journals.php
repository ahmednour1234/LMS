<?php

return [
    'reference' => 'Reference',
    'date' => 'Date',
    'description' => 'Description',
    'status' => 'Status',
    'status_options' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'void' => 'Void',
    ],
    'posted_by' => 'Posted By',
    'posted_at' => 'Posted At',
    'reference_type' => 'Reference Type',
    'branch' => 'Branch',
    'created_by' => 'Created By',
    'journal_lines' => 'Journal Lines',
    'actions' => [
        'post' => 'Post',
        'void' => 'Void',
        'print' => 'Print',
        'post_confirm' => 'Post Journal',
        'post_confirm_button' => 'Post',
        'void_confirm' => 'Void Journal',
        'void_confirm_button' => 'Void',
        'posted_success' => 'Journal posted successfully',
        'voided_success' => 'Journal voided successfully',
    ],
    'errors' => [
        'imbalanced' => 'Journal is not balanced',
        'debit_credit_mismatch' => 'Debit total ({debit}) does not equal Credit total ({credit})',
        'already_posted' => 'Already Posted',
        'duplicate_reference' => 'A journal with this reference type and ID is already posted',
    ],
];

