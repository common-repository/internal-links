<?php

// @codingStandardsIgnoreStart
/**
 * @package ILJ\Includes
 */
// @codingStandardsIgnoreEnd
use ILJ\Database\Usermeta;
use ILJ\Database\Linkindex;
use ILJ\Database\Postmeta;
use ILJ\Backend\Environment;
use ILJ\Core\Options;
use ILJ\Database\DatabaseCollation;
use ILJ\Database\LinkindexTemp;
/**
 * Responsible for creating the database tables
 *
 * @since  1.0.0
 * @return void
 */
function ilj_install_db()
{
    global $wpdb;
    $charset_collate = DatabaseCollation::get_collation(true);
    $query_linkindex = 'CREATE TABLE ' . $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX . ' (
        `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
        `link_from` BIGINT(20) NULL,
        `link_to` BIGINT(20) NULL,
        `type_from` VARCHAR(45) NULL,
        `type_to` VARCHAR(45) NULL,
        `anchor` TEXT NULL,
        PRIMARY KEY (`id`),
        INDEX `link_from` (`link_from` ASC),
        INDEX `type_from` (`type_from` ASC),
        INDEX `type_to` (`type_to` ASC),
        INDEX `link_to` (`link_to` ASC)) ' . $charset_collate . ';';
    include_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($query_linkindex);
    Environment::update('last_version', ILJ_VERSION);
}
/**
 * This function performs tasks such as creating database tables,
 * and any other activation-related procedures.
 *
 * @param  mixed $network_wide
 * @return void
 */
function ilj_plugin_activate($network_wide)
{
    if (is_multisite()) {
        if ($network_wide) {
            $site_ids = get_sites(array('fields' => 'ids'));
            foreach ($site_ids as $site_id) {
                switch_to_blog($site_id);
                ilj_install_db();
                restore_current_blog();
            }
            return;
        }
        ilj_install_db();
        return;
    }
    ilj_install_db();
}
/**
 * Function for `wp_initialize_site` action-hook to install ilj database
 *
 * @param WP_Site $new_site New site object.
 *
 * @return void
 */
function ilj_multisite_install_db($new_site)
{
    switch_to_blog($new_site->blog_id);
    ilj_install_db();
    restore_current_blog();
}
add_action('wp_initialize_site', 'ilj_multisite_install_db', 10, 1);
register_activation_hook(ILJ_FILE, '\ilj_plugin_activate');
register_activation_hook(ILJ_FILE, array('ILJ\Core\Options', 'setOptionsDefault'));