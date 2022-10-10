<?php
/**
 * Adds admin settings for the plugin.
 *
 * @package     formationsapi
 * @category    admin
 */

/** @var admin_root $ADMIN */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settingspage = new admin_settingpage(
        'manageformationsapi',
        get_string('pluginname', 'local_formationsapi')
    );

    if ($ADMIN->fulltree) {
        $settingspage->add(
            new admin_setting_configtext(
                'local_formationsapi/update_user_call_url',
                get_string('update_user_call_url_description', 'local_formationsapi'),
                '',
                '',
                PARAM_URL
            )
        );
        $settingspage->add(
            new admin_setting_configtext(
                'local_formationsapi/admin_emails',
                get_string('email_setting_description', 'local_formationsapi'),
                '',
                '',
                PARAM_RAW
            )
        );
        $settingspage->add(
            new admin_setting_configtext(
                'local_formationsapi/apikey',
                get_string('apikey_description', 'local_formationsapi'),
                '',
                '',
                PARAM_RAW
            )
        );
        $settingspage->add(
            new admin_setting_configtext(
                'local_formationsapi/admin_groups_prefix',
                get_string('admin_groups_prefix', 'local_formationsapi'),
                '',
                'app-cours-admin-',
                PARAM_RAW
            )
        );
    }

    $ADMIN->add('localplugins', $settingspage);
}

