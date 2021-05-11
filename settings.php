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
                'local_formationsapi/course_category_id',
                get_string('category_setting_description', 'local_formationsapi'),
                '',
                '1',
                PARAM_INT
            )
        );
    }

    $ADMIN->add('localplugins', $settingspage);
}

