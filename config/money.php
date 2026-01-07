<?php

/**
 * Money/Currency Configuration
 * 
 * This configuration centralizes all currency-related settings for the LMS platform.
 * The system uses Omani Rial (OMR) as the primary currency.
 * 
 * OMR has 3 decimal places (1/1000 baisa), unlike most currencies which use 2.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The ISO 4217 currency code used throughout the application.
    |
    */
    'currency' => env('MONEY_CURRENCY', 'OMR'),

    /*
    |--------------------------------------------------------------------------
    | Currency Symbol
    |--------------------------------------------------------------------------
    |
    | The symbol used to display the currency in the UI.
    | For OMR: ر.ع (Rial Omani)
    |
    */
    'symbol' => env('MONEY_SYMBOL', 'ر.ع'),

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | The number of decimal places for monetary values.
    | OMR uses 3 decimal places (1 rial = 1000 baisa).
    |
    */
    'precision' => env('MONEY_PRECISION', 3),

    /*
    |--------------------------------------------------------------------------
    | Decimal Separator
    |--------------------------------------------------------------------------
    |
    | The character used to separate decimal places.
    |
    */
    'decimal_separator' => '.',

    /*
    |--------------------------------------------------------------------------
    | Thousands Separator
    |--------------------------------------------------------------------------
    |
    | The character used to separate thousands.
    |
    */
    'thousands_separator' => ',',

    /*
    |--------------------------------------------------------------------------
    | Symbol Position
    |--------------------------------------------------------------------------
    |
    | Where to display the currency symbol relative to the amount.
    | Options: 'before', 'after'
    | For Arabic/RTL, 'after' is typically used.
    |
    */
    'symbol_position' => 'after',

    /*
    |--------------------------------------------------------------------------
    | Space Between Symbol and Amount
    |--------------------------------------------------------------------------
    |
    | Whether to add a space between the symbol and the amount.
    |
    */
    'symbol_space' => true,

    /*
    |--------------------------------------------------------------------------
    | Formatting Locale
    |--------------------------------------------------------------------------
    |
    | The locale to use for number formatting. This affects how numbers
    | are displayed based on regional conventions.
    |
    */
    'locale' => env('MONEY_LOCALE', 'ar_OM'),

    /*
    |--------------------------------------------------------------------------
    | Rounding Mode
    |--------------------------------------------------------------------------
    |
    | How to round monetary values.
    | Options: PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN, PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD
    |
    */
    'rounding_mode' => PHP_ROUND_HALF_UP,

    /*
    |--------------------------------------------------------------------------
    | Account Codes (Chart of Accounts)
    |--------------------------------------------------------------------------
    |
    | Default account codes used for accounting entries.
    | These should match your Chart of Accounts setup.
    |
    */
    'accounts' => [
        'cash' => env('ACCOUNT_CASH', '1110'),
        'bank' => env('ACCOUNT_BANK', '1120'),
        'accounts_receivable' => env('ACCOUNT_AR', '1130'),
        'deferred_revenue' => env('ACCOUNT_DEFERRED_REVENUE', '2130'),
        'training_revenue' => env('ACCOUNT_TRAINING_REVENUE', '4110'),
        'discount_given' => env('ACCOUNT_DISCOUNT', '4910'),
        'tax_payable' => env('ACCOUNT_TAX_PAYABLE', '2140'),
    ],
];

