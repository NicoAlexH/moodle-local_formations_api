<?php

global $CFG;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/local/formationsapi/classes/api/api.php');

// vendor/bin/phpunit local/formationsapi/tests/formationsapi_testcase.php
class formationsapi_testcase extends advanced_testcase
{
    public function test_adding()
    {
        global $CFG;
        $this->resetAfterTest();
        $category = self::getDataGenerator()->create_category(['idnumber' => 'test']);
        var_dump($this->create_course('test', 'test'));
        die;
    }
}