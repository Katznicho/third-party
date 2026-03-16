<?php

namespace App\Traits;

trait AccessTrait
{
    public static $admin = [
        "Dashboard" => [
            "View Dashboard", "View Dashboard Cards", "View Dashboard Charts",
        ]
    ];

    public static $clients = [
        "Clients" => [
            'View Clients',
            'Edit Clients',
            'Add Clients',
            'View Client Account Statement',
        ],
    ];

    public static $authorizations = [
        "Authorizations" => [
            'View Authorizations',
            'Approve Authorizations',
            'Reject Authorizations',
            'View Rejected Items',
        ],
    ];

    public static $policies = [
        "Policies" => ['View Policies', 'Edit Policies', 'Add Policies'],
    ];

    public static $providers = [
        "Service Providers" => [
            'View Providers',
            'Edit Providers',
            'Configure Exclusions',
            'View Provider Financials',
        ],
    ];

    public static $finance = [
        "Finance" => [
            'View Finance',
            'View Client Statements',
            'View Provider Financials',
            'View Deductible Ledger',
            'View Payments',
        ],
    ];

    public static $settings = [
        "Settings" => [
            'View Settings',
            'Edit Settings',
            'Manage Roles',
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
        $roles = static::spreadArrayKeys(
            array_merge(
                static::$admin,
                static::$clients,
                static::$authorizations,
                static::$policies,
                static::$providers,
                static::$finance,
                static::$settings,
            )
        );
        return $roles;
    }

    public static function getAccessControl(array $exclude = [])
    {
        $permissions = [
            "Dashboard" => self::$admin,
            "Clients" => self::$clients,
            "Authorizations" => self::$authorizations,
            "Policies" => self::$policies,
            "Service Providers" => self::$providers,
            "Finance" => self::$finance,
            "Settings" => self::$settings,
        ];

        if (!empty($exclude)) {
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

