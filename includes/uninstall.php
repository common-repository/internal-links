<?php

use ILJ\Cache\Transient_Cache;
use ILJ\Core\Options;
use ILJ\Database\Linkindex;
use ILJ\Database\LinkindexTemp;
use ILJ\Database\Postmeta;
use ILJ\Database\Usermeta;
/**
 * Responsible for removing database stuff on plugin uninstall
 *
 * @since 1.2.2
 */
function ilj_remove_db_data()
{
    // Delete all ilj transients
    Transient_Cache::delete_all();
    $keep_settings = Options::getOption(\ILJ\Core\Options\KeepSettings::getKey());
    if ($keep_settings) {
        return;
    }
    Options::removeAllOptions();
    Postmeta::removeAllLinkDefinitions();
    Usermeta::removeAllUsermeta();
}
/**
 * Responsible for deleting the database tables
 *
 * @return void
 */
function ilj_uninstall_db()
{
    global $wpdb;
    $query_linkindex = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX . ';';
    $wpdb->query($query_linkindex);
    $fs_accounts = get_option('fs_accounts', array());
    unset($fs_accounts['id_slug_type_path_map'][ILJ_FREEMIUS_PLUGIN_ID]);
    unset($fs_accounts['plugin_data']['internal-links']);
    unset($fs_accounts['file_slug_map']['internal-links/wp-internal-linkjuicer.php']);
    unset($fs_accounts['file_slug_map']['internal-links-premium/wp-internal-linkjuicer.php']);
    unset($fs_accounts['plugins']['[internal-links']);
    unset($fs_accounts['sites']['internal-links']);
    unset($fs_accounts['plans']['internal-links']);
    unset($fs_accounts['user_id_license_ids_map'][ILJ_FREEMIUS_PLUGIN_ID]);
    unset($fs_accounts['all_licenses'][ILJ_FREEMIUS_PLUGIN_ID]);
    update_option('fs_accounts', $fs_accounts);
}
/**
 * This function performs tasks such as deleting database tables,
 * and any other uninstall related procedures.
 *
 * @return void
 */
function ilj_plugin_uninstall()
{
    if (is_multisite()) {
        $site_ids = get_sites(array('fields' => 'ids'));
        foreach ($site_ids as $site_id) {
            switch_to_blog($site_id);
            ilj_uninstall_db();
            ilj_remove_db_data();
            restore_current_blog();
        }
        return;
    }
    ilj_uninstall_db();
    ilj_remove_db_data();
}
/**
 * Uninstall actions.
 */
\ILJ\ilj_fs()->add_action('after_uninstall', '\ilj_plugin_uninstall');