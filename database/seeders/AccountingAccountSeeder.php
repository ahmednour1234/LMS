<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent accounts (Chart of Accounts structure)
        $parentAccounts = [
            [
                'code' => '1000',
                'name' => 'Assets',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '2000',
                'name' => 'Liabilities',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '3000',
                'name' => 'Equity',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '4000',
                'name' => 'Revenue',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '5000',
                'name' => 'Expenses',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        $createdParents = [];
        foreach ($parentAccounts as $account) {
            $createdParents[] = Account::create($account);
        }

        // Create child accounts for Assets
        $assetAccounts = [
            [
                'code' => '1100',
                'name' => 'Current Assets',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[0]->id, // Assets
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '1200',
                'name' => 'Fixed Assets',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[0]->id, // Assets
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        $createdAssetParents = [];
        foreach ($assetAccounts as $account) {
            $createdAssetParents[] = Account::create($account);
        }

        // Create specific asset accounts
        $specificAssets = [
            [
                'code' => '1110',
                'name' => 'Cash',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdAssetParents[0]->id, // Current Assets
                'opening_balance' => 50000.00,
                'is_active' => true,
            ],
            [
                'code' => '1120',
                'name' => 'Bank',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdAssetParents[0]->id, // Current Assets
                'opening_balance' => 100000.00,
                'is_active' => true,
            ],
            [
                'code' => '1130',
                'name' => 'Accounts Receivable',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdAssetParents[0]->id, // Current Assets
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '1210',
                'name' => 'Buildings',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdAssetParents[1]->id, // Fixed Assets
                'opening_balance' => 500000.00,
                'is_active' => true,
            ],
            [
                'code' => '1220',
                'name' => 'Equipment',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $createdAssetParents[1]->id, // Fixed Assets
                'opening_balance' => 100000.00,
                'is_active' => true,
            ],
        ];

        foreach ($specificAssets as $account) {
            Account::create($account);
        }

        // Create child accounts for Liabilities
        $liabilityAccounts = [
            [
                'code' => '2100',
                'name' => 'Current Liabilities',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[1]->id, // Liabilities
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '2200',
                'name' => 'Long-term Liabilities',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[1]->id, // Liabilities
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        $createdLiabilityParents = [];
        foreach ($liabilityAccounts as $account) {
            $createdLiabilityParents[] = Account::create($account);
        }

        // Create specific liability accounts
        $specificLiabilities = [
            [
                'code' => '2110',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $createdLiabilityParents[0]->id, // Current Liabilities
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '2120',
                'name' => 'Accrued Expenses',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $createdLiabilityParents[0]->id, // Current Liabilities
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '2130',
                'name' => 'Deferred Revenue',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $createdLiabilityParents[0]->id, // Current Liabilities
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($specificLiabilities as $account) {
            Account::create($account);
        }

        // Create equity accounts
        $equityAccounts = [
            [
                'code' => '3100',
                'name' => 'Capital',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[2]->id, // Equity
                'opening_balance' => 500000.00,
                'is_active' => true,
            ],
            [
                'code' => '3200',
                'name' => 'Retained Earnings',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[2]->id, // Equity
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($equityAccounts as $account) {
            Account::create($account);
        }

        // Create revenue accounts
        $revenueAccounts = [
            [
                'code' => '4100',
                'name' => 'Tuition Revenue',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[3]->id, // Revenue
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '4110',
                'name' => 'Training Revenue',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[3]->id, // Revenue
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '4200',
                'name' => 'Registration Fees',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[3]->id, // Revenue
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '4300',
                'name' => 'Other Income',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'parent_id' => $createdParents[3]->id, // Revenue
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($revenueAccounts as $account) {
            Account::create($account);
        }

        // Create expense accounts
        $expenseAccounts = [
            [
                'code' => '5100',
                'name' => 'Salaries and Wages',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[4]->id, // Expenses
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '5200',
                'name' => 'Rent Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[4]->id, // Expenses
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '5300',
                'name' => 'Utilities',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[4]->id, // Expenses
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '5400',
                'name' => 'Office Supplies',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[4]->id, // Expenses
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'code' => '5500',
                'name' => 'Marketing and Advertising',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $createdParents[4]->id, // Expenses
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($expenseAccounts as $account) {
            Account::create($account);
        }
    }
}
