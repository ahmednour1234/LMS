<?php

return [
    'reference' => 'المرجع',
    'date' => 'التاريخ',
    'description' => 'الوصف',
    'status' => 'الحالة',
    'status_options' => [
        'draft' => 'مسودة',
        'posted' => 'مسجل',
        'void' => 'ملغاة',
    ],
    'posted_by' => 'تم النشر بواسطة',
    'posted_at' => 'تاريخ النشر',
    'reference_type' => 'نوع المرجع',
    'branch' => 'الفرع',
    'created_by' => 'تم الإنشاء بواسطة',
    'journal_lines' => 'بنود القيد',
    'actions' => [
        'post' => 'نشر',
        'void' => 'إلغاء',
        'print' => 'طباعة',
        'post_confirm' => 'نشر السجل',
        'post_confirm_button' => 'نشر',
        'void_confirm' => 'إلغاء السجل',
        'void_confirm_button' => 'إلغاء',
        'posted_success' => 'تم نشر السجل بنجاح',
        'voided_success' => 'تم إلغاء السجل بنجاح',
    ],
    'errors' => [
        'imbalanced' => 'السجل غير متوازن',
        'debit_credit_mismatch' => 'إجمالي المدين ({debit}) لا يساوي إجمالي الدائن ({credit})',
        'already_posted' => 'تم النشر بالفعل',
        'duplicate_reference' => 'يوجد سجل بهذا النوع والمعرف تم نشره بالفعل',
    ],
];

