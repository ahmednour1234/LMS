<?php

return [
    'model' => 'Course Price',
    'course' => 'Course',
    'course_name' => 'Course Name',
    'branch' => 'Branch',
    'branch_helper' => 'Leave empty for global price',
    'delivery_type' => 'Delivery Type',
    'delivery_type_helper' => 'Leave empty for all delivery types',
    'delivery_type_options' => [
        'onsite' => 'Onsite',
        'online' => 'Online',
    ],
    'all_delivery_types' => 'All Types',
    'global' => 'Global',
    'pricing_mode' => 'Pricing Mode',
    'pricing_mode_options' => [
        'course_total' => 'Course Total',
        'per_session' => 'Per Session',
        'both' => 'Both',
    ],
    'price' => 'Price',
    'session_price' => 'Session Price',
    'session_price_helper' => 'Student pays per class session',
    'sessions_count' => 'Sessions Count',
    'allow_installments' => 'Allow Installments',
    'min_down_payment' => 'Min Down Payment',
    'max_installments' => 'Max Installments',
    'max_installments_helper' => 'Maximum number of installments allowed',
    'max_installments_helper_course_total' => 'Maximum :limit installments allowed for course total pricing',
    'max_installments_helper_session_based' => 'Maximum :count installments (based on sessions count)',
    'max_installments_exceeds_sessions' => 'Maximum installments cannot exceed the number of sessions (:count)',
    'max_installments_exceeds_limit' => 'Maximum installments cannot exceed :limit',
    'is_active' => 'Active',
];

