<?php

if (!function_exists('hasPermission')) {
    /**
     * Global Helper to check permissions
     */
    function hasPermission($permission, $userRole, $permissions) {
        return is_array($permissions) && in_array($permission, $permissions, true);
    }
}