<?php
/**
 * Adds admin settings for the plugin.
 *
 * @package     unilformationpers
 * @category    admin
 */

/** @var admin_root $ADMIN */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_unilformationpers_settings', get_string('pluginname', 'local_unilformationpers')));
    $settingspage = new admin_settingpage('manageunilformationpers', get_string('UnilFormationPers', 'local_unilformationpers'));

    if ($ADMIN->fulltree) {

    }

    $ADMIN->add('localplugins', $settingspage);
}

