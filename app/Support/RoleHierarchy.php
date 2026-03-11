<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleHierarchy
{
    /**
     * Define role ranks (higher number = higher privilege)
     */
    public const ROLE_RANK = [
        'master'                  => 999,
        'Directrice du SPCR'      => 100,
        'super_admin'             => 100,
        'admin'                   => 90,
        'IT Admin'                => 85,
        'Chef de Pôle'            => 80,
        'Chef de Département'     => 70,
        'Assistante de Direction' => 65,
        'user'                    => 60,
        'Chargée de dépôt'        => 50,
        // Backward-compatibility aliases
        'department administrator' => 80,
        'division chief'           => 75,
        'service manager'          => 70,
        'service user'             => 60,
    ];

    public static function getRoleRank(string $roleName): int
    {
        return self::ROLE_RANK[$roleName] ?? 20;
    }

    public static function getUserMaxRank(User $user): int
    {
        $roles = $user->roles->pluck('name')->all();
        if (empty($roles)) {
            return 0;
        }
        return max(array_map([self::class, 'getRoleRank'], $roles));
    }

    /**
     * Roles current user may VIEW.
     * - Master: may view all roles.
     * - Others: may only view users whose max role rank is STRICTLY lower than their own.
     */
    public static function allowedRoleNamesFor(User $currentUser): array
    {
        if ($currentUser->hasRole('master')) {
            return Role::pluck('name')->all();
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $allRoles = Role::pluck('name')->all();
        return array_values(array_filter($allRoles, function ($roleName) use ($currentRank) {
            return self::getRoleRank($roleName) < $currentRank;
        }));
    }

    /**
     * Roles current user may ASSIGN to others.
     * - Master: any role (including master).
     * - Others: only roles with STRICTLY lower rank than their own.
     */
    public static function allowedAssignableRoleNamesFor(User $currentUser): array
    {
        if ($currentUser->hasRole('master')) {
            return Role::pluck('name')->all();
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $allRoles = Role::pluck('name')->all();
        return array_values(array_filter($allRoles, function ($roleName) use ($currentRank) {
            return self::getRoleRank($roleName) < $currentRank;
        }));
    }

    public static function canAssignRole(User $currentUser, Role $targetRole): bool
    {
        if ($currentUser->hasRole('master')) {
            return true;
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $targetRank = self::getRoleRank($targetRole->name);

        return $targetRank < $currentRank;
    }

    public static function canViewUser(User $currentUser, User $other): bool
    {
        return self::getUserMaxRank($other) <= self::getUserMaxRank($currentUser);
    }
}
