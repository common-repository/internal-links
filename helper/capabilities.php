<?php

namespace ILJ\Helper;

/**
 * Capabilities toolset
 *
 * Methods for handling capabilities settings
 *
 * @package ILJ\Helper
 * @since   1.0.0
 */
class Capabilities
{
    /**
     * Generates a select input for predefined user roles
     *
     * @since  1.0.0
     * @param  string|bool $selected The role that gets preselected
     * @return void
     */
    public static function rolesDropdown($selected = false)
    {
        $roles_preselected = array('administrator' => translate_user_role('Administrator'), 'editor' => translate_user_role('Editor'), 'author' => translate_user_role('Author'), 'contributor' => translate_user_role('Contributor'));
        $custom_roles = self::getCustomRoles();
        $merged_roles = array_merge($roles_preselected, $custom_roles);
        foreach ($merged_roles as $role => $name) {
            ?>
			 <option value="<?php 
            echo esc_attr($role);
            ?>" <?php 
            selected($selected, $role);
            ?>><?php 
            echo esc_html($name);
            ?></option>
			 <?php 
        }
    }
    public static function getValidRoles()
    {
        $default_roles = array('administrator', 'editor', 'author', 'contributor');
        $custom_roles = self::getCustomRoles();
        $custom_roles_keys = array_keys($custom_roles);
        $merged_roles = array_merge($default_roles, $custom_roles_keys);
        return $merged_roles;
    }
    /**
     * Translates a given user role to its accredited capability
     *
     * @since  1.0.0
     * @param  string $role The role to translate
     * @return string
     */
    public static function mapRoleToCapability($role)
    {
        $custom_roles = self::getCustomRoles();
        $custom_roles_keys = array_keys($custom_roles);
        switch ($role) {
            case 'administrator':
                return 'manage_options';
                break;
            case 'editor':
                return 'publish_pages';
                break;
            case 'author':
                return 'publish_posts';
                break;
            case 'contributor':
                return 'edit_posts';
                break;
            case in_array($role, $custom_roles_keys):
                if (!$role) {
                    break;
                }
                $role_capability = get_role($role);
                // Check if role has capability until edit_posts
                if ($role_capability->has_cap('manage_options')) {
                    return 'manage_options';
                } elseif ($role_capability->has_cap('publish_pages')) {
                    return 'publish_pages';
                } elseif ($role_capability->has_cap('publish_posts')) {
                    return 'publish_posts';
                } elseif ($role_capability->has_cap('edit_posts')) {
                    return 'edit_posts';
                } else {
                    return $role;
                }
                break;
            default:
                return $role;
                break;
        }
    }
    /**
     * Get Custom Roles that are added in WordPress by other plugins
     * Gives user more flexibility in managing which is the minimum required user role for editing keywords
     *
     * @return array
     */
    public static function getCustomRoles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        // Get all custom user roles
        $custom_roles = get_editable_roles();
        $roles = array();
        // Exclude the default roles and subscriber and customer which does not have access to WP admin
        $default_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer');
        $minimum_capability_requirements = array('manage_options', 'publish_pages', 'publish_posts', 'edit_posts');
        foreach ($custom_roles as $role => $details) {
            if (!in_array($role, $default_roles)) {
                // Add only user with atleast edit posts capability, without edit_posts capability roles will be skipped
                foreach ($minimum_capability_requirements as $capability) {
                    if (isset($details['capabilities'][$capability]) && 1 == $details['capabilities'][$capability]) {
                        $role_name = translate_user_role($details['name']);
                        $roles[$role] = $role_name;
                        break;
                    }
                }
            }
        }
        return $roles;
    }
}