<?php

namespace App\Traits;

trait AccessTrait
{
    public static $admin = [
        'Dashboard' => [
            'View Dashboard',
            'View Dashboard Cards',
            'View Dashboard Charts',
            'View Authorized Visits',
        ],
    ];

    public static $users = [
        'Users' => [
            'View Users',
            'Add Users',
            'Edit Users',
            'Delete Users',
        ],
    ];

    public static $clients = [
        'Clients' => [
            'View Clients',
            'Add Clients',
            'Edit Clients',
            'Delete Clients',
            'View Client Account Statement',
            'View Client Usage',
            'View Client Deductible Ledger',
            'Collect Client Premium',
            'Check Client Mobile Money Payments',
        ],
    ];

    public static $policies = [
        'Policies' => [
            'View Policies',
            'Add Policies',
            'Edit Policies',
            'Delete Policies',
        ],
    ];

    public static $plans = [
        'Plans' => [
            'View Plans',
            'Add Plans',
            'Edit Plans',
            'Delete Plans',
        ],
    ];

    public static $serviceCategories = [
        'Service Categories' => [
            'View Service Categories',
            'Add Service Categories',
            'Edit Service Categories',
            'Delete Service Categories',
        ],
    ];

    public static $preAuthorizations = [
        'Pre-Authorizations' => [
            'View Pre-Authorizations',
            'Add Pre-Authorizations',
            'Edit Pre-Authorizations',
        ],
    ];

    public static $authorizationReview = [
        'Authorization Review' => [
            'View Authorization Review',
            'Approve Authorization Review',
            'Reject Authorization Review',
            'Reprocess Authorization Review',
            // Legacy labels (existing roles may still store these)
            'Approve Authorizations',
            'Reject Authorizations',
        ],
    ];

    public static $authorizationCodes = [
        'Authorization Codes' => [
            'View Authorization Codes',
            'View Rejected Authorization Items',
            // Legacy labels (existing roles may still store these)
            'View Authorizations',
            'View Rejected Items',
        ],
    ];

    public static $authorizationRules = [
        'Authorization Rules' => [
            'View Authorization Rules',
            'Add Authorization Rules',
            'Edit Authorization Rules',
            'Delete Authorization Rules',
        ],
    ];

    /**
     * Provider catalog items (connected company): coverage %, exclusions — mirrors Kashtre Items scope for insurers.
     */
    public static $providerItems = [
        'Provider Items' => [
            'View Provider Items',
            'Configure Item Coverage',
            'Configure Item Exclusions',
            'Configure Category Exclusions',
        ],
    ];

    public static $providers = [
        'Service Providers' => [
            'View Providers',
            'Edit Providers',
            'Configure Exclusions',
            'View Provider Financials',
            'Pay Provider',
            'Block Provider',
            'Reactivate Provider',
        ],
    ];

    public static $invoices = [
        'Invoices' => [
            'View Invoices',
            'Mark Invoice Paid',
            'Bulk Pay Invoices',
            'Generate Invoice PDF',
        ],
    ];

    public static $payments = [
        'Payments' => [
            'View Payments',
            'Add Payments',
            'Edit Payments',
            'Mark Payment Received',
        ],
    ];

    public static $transactions = [
        'Transactions' => [
            'View Transactions',
            'View Outstanding Transactions',
            'View Cleared Transactions',
        ],
    ];

    public static $finance = [
        'Finance' => [
            'View Finance',
            'View Client Statements',
            'View Provider Financials',
            'View Deductible Ledger',
            'View Payments',
        ],
    ];

    public static $medicalQuestions = [
        'Medical Questions' => [
            'View Medical Questions',
            'Add Medical Questions',
            'Edit Medical Questions',
            'Delete Medical Questions',
        ],
    ];

    public static $paymentResponsibilities = [
        'Payment Responsibilities' => [
            'View Payment Responsibilities',
            'Add Payment Responsibilities',
            'Edit Payment Responsibilities',
            'Delete Payment Responsibilities',
        ],
    ];

    public static $organization = [
        'Organization' => [
            'View Departments',
            'Add Departments',
            'Edit Departments',
            'Delete Departments',
            'View Sections',
            'Add Sections',
            'Edit Sections',
            'Delete Sections',
            'View Titles',
            'Add Titles',
            'Edit Titles',
            'Delete Titles',
            'View Qualifications',
            'Add Qualifications',
            'Edit Qualifications',
            'Delete Qualifications',
            'View Stores',
            'Add Stores',
            'Edit Stores',
            'Delete Stores',
            'View Supplies',
            'Add Supplies',
            'Edit Supplies',
            'Delete Supplies',
        ],
    ];

    public static $settings = [
        'Settings' => [
            'View Settings',
            'Edit Settings',
            'Manage Roles',
            'Manage Coverage Decision Matrix',
            'Manage Pre-Authorization Triggers',
            'Manage Verification Settings',
            'Manage Authorization Settings',
            'Send Vendor Code',
        ],
    ];

    public static function spreadArrayKeys($assocArray)
    {
        $result = [];
        foreach ($assocArray as $key => $value) {
            if (is_string($key)) {
                $result[] = $key;
            }
            if (is_array($value)) {
                $result = array_merge($result, static::spreadArrayKeys($value));
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    public static function getAllPermissions()
    {
        return static::spreadArrayKeys(
            array_merge(
                static::$admin,
                static::$users,
                static::$clients,
                static::$policies,
                static::$plans,
                static::$serviceCategories,
                static::$preAuthorizations,
                static::$authorizationReview,
                static::$authorizationCodes,
                static::$authorizationRules,
                static::$providerItems,
                static::$providers,
                static::$invoices,
                static::$payments,
                static::$transactions,
                static::$finance,
                static::$medicalQuestions,
                static::$paymentResponsibilities,
                static::$organization,
                static::$settings,
            )
        );
    }

    public static function getAccessControl(array $exclude = [])
    {
        $permissions = [
            'Dashboard' => self::$admin,
            'Users' => self::$users,
            'Clients' => self::$clients,
            'Policies' => self::$policies,
            'Plans' => self::$plans,
            'Service Categories' => self::$serviceCategories,
            'Pre-Authorizations' => self::$preAuthorizations,
            'Authorization Review' => self::$authorizationReview,
            'Authorization Codes' => self::$authorizationCodes,
            'Authorization Rules' => self::$authorizationRules,
            'Provider Items' => self::$providerItems,
            'Service Providers' => self::$providers,
            'Invoices' => self::$invoices,
            'Payments' => self::$payments,
            'Transactions' => self::$transactions,
            'Finance' => self::$finance,
            'Medical Questions' => self::$medicalQuestions,
            'Payment Responsibilities' => self::$paymentResponsibilities,
            'Organization' => self::$organization,
            'Settings' => self::$settings,
        ];

        if (! empty($exclude)) {
            $permissions = collect($permissions)->reject(function ($_, $key) use ($exclude) {
                return in_array($key, $exclude);
            })->toArray();
        }

        return $permissions;
    }

    public static function userCan(string $permission, $storedPermissions): bool
    {
        $permissions = is_array($storedPermissions) ? $storedPermissions : json_decode($storedPermissions ?? '[]', true);

        return in_array($permission, $permissions ?? []);
    }
}
