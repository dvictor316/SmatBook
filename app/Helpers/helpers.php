<?php

if (!function_exists('hasPermission')) {
    /**
     * Global Helper to check permissions
     */
    function hasPermission($permission, $userRole, $permissions) {
        // Master override for admins
        if (in_array($userRole, ['superadmin', 'administrator'])) {
            return true;
        }
        
        // Ensure $permissions is an array and check for the string
        return is_array($permissions) && in_array($permission, $permissions);
    }
}