<?php

return [
    'model' => 'سعر الدورة',
    'course' => 'الدورة',
    'course_name' => 'اسم الدورة',
    'branch' => 'الفرع',
    'branch_helper' => 'اتركه فارغاً للسعر العام',
    'delivery_type' => 'نوع التوصيل',
    'delivery_type_helper' => 'اتركه فارغاً لجميع أنواع التوصيل',
    'delivery_type_options' => [
        'onsite' => 'حضوري',
        'online' => 'أونلاين',
        'virtual' => 'افتراضي',
    ],
    'all_delivery_types' => 'جميع الأنواع',
    'global' => 'عام',
    'pricing_mode' => 'وضع التسعير',
    'pricing_mode_options' => [
        'course_total' => 'سعر الدورة الكامل',
        'per_session' => 'لكل جلسة',
        'both' => 'كلاهما',
    ],
    'price' => 'السعر',
    'session_price' => 'سعر الجلسة',
    'session_price_helper' => 'يدفع الطالب لكل جلسة',
    'sessions_count' => 'عدد الجلسات',
    'allow_installments' => 'السماح بالأقساط',
    'min_down_payment' => 'الحد الأدنى للدفعة الأولى',
    'max_installments' => 'الحد الأقصى للأقساط',
    'max_installments_helper' => 'الحد الأقصى لعدد الأقساط المسموح بها',
    'max_installments_helper_course_total' => 'الحد الأقصى :limit قسط لتسعير الدورة الكاملة',
    'max_installments_helper_session_based' => 'الحد الأقصى :count قسط (بناءً على عدد الجلسات)',
    'max_installments_exceeds_sessions' => 'لا يمكن أن يتجاوز الحد الأقصى للأقساط عدد الجلسات (:count)',
    'max_installments_exceeds_limit' => 'لا يمكن أن يتجاوز الحد الأقصى للأقساط :limit',
    'is_active' => 'نشط',
];

