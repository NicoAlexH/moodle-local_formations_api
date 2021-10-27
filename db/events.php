<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => 'local_formationsapi_observer::update_user_profile',
    ],
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'local_formationsapi_observer::set_user_role',
    ],
];