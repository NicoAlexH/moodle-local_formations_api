<?php

global $CFG;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// vendor/bin/phpunit local/formationsapi/tests/formationsapi_testcase.php
class formationsapi_testcase extends advanced_testcase
{
    private $api_class;

    public function test_first_course_creation()
    {
        global $DB;

        $this->resetAfterTest();

        $course_name = 'Test course';
        $conference_course_id = 2;
        $category_name = 'TestCategory';

        $category = self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $result = (object)$this->api_class->create_course($course_name, $conference_course_id, $category_name);
        $course = $DB->get_record('course', ['id' => $result->course_id]);

        self::assertEquals("1", $course->enablecompletion);
        self::assertEquals($conference_course_id, $course->shortname);
        self::assertEquals($category->id, $course->category);
        self::assertEquals(1, $course->visible);
        self::assertEquals($course_name, $course->fullname);
    }

    public function test_course_already_exists()
    {
        $this->resetAfterTest();

        $course_name = 'test_course';
        $conference_course_id = 2;
        $category_name = 'TestCategory';
        self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $first_course = (object)$this->api_class->create_course($course_name, $conference_course_id, $category_name);
        $second_course = (object)$this->api_class->create_course($course_name, $conference_course_id, $category_name);
        self::assertEquals($first_course->course_id, $second_course->course_id);
    }

    public function test_closing_course()
    {
        global $DB;
        $this->resetAfterTest();

        $course_name = 'test_course';
        $conference_course_id = 2;
        $category_name = 'TestCategory';
        self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $course = (Object)$this->api_class->create_course($course_name, $conference_course_id, $category_name);
        $this->api_class->close_course($conference_course_id);
        $courseObject = $DB->get_record('course', ['id' => $course->course_id]);
        self::assertEquals(0, $courseObject->visible);
    }

    public function test_user_enrolment()
    {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $this->resetAfterTest();

        $user = self::getDataGenerator()->create_user([
            'username' => 'toto@example.com',
            'email' => 'toto@example.com',
            'auth' => 'shibboleth',
        ]);
        $category = self::getDataGenerator()->create_category();

        //Case 1 : the user already exists
        $course = self::getDataGenerator()->create_course(['category' => $category->id]);
        $this->api_class->enrol_user($user->email, $user->firstname, $user->lastname, $course->id, 'student');
        self::assertTrue(is_enrolled(context_course::instance($course->id), $user->id));

        //Case 2: user is automatically created
        $course = self::getDataGenerator()->create_course(['category' => $category->id]);
        $this->api_class->enrol_user('random@example.com', 'Arthur', 'Pendragon', $course->id, 'student');
        $user = $DB->get_record('user', ['username' => 'random@example.com']);
        self::assertTrue(is_enrolled(context_course::instance($course->id), $user->id));
    }


    protected function setUp(): void
    {
        require_once(__DIR__ . '/../classes/api/local_formationsapi_api.php');
        require_once(__DIR__ . '/../classes/observer.php');
        $this->api_class = new local_formationsapi_api();
    }
}