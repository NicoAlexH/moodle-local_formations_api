<?php

global $CFG;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

class formationsapi_testcase extends advanced_testcase
{
    private $observer;

    /**
     * @throws \moodle_exception
     * @throws \dml_exception
     */
    public function test_course_creation_with_existing_category(): void
    {
        global $DB;

        $this->resetAfterTest();

        $course_name = 'Test course éäèö';
        $app_course_id = 2;
        $category_name = 'TestCatégorie';

        $category = self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $result = (object)local_formationsapi_api::create_course($course_name, $app_course_id, $category_name);
        $course = $DB->get_record('course', ['id' => $result->course_id]);

        self::assertEquals("1", $course->enablecompletion);
        self::assertEquals($app_course_id, $course->idnumber);
        self::assertEquals($category->id, $course->category);
        self::assertEquals(1, $course->visible);
        self::assertEquals($course_name, $course->fullname);
        self::assertEquals($course_name, $course->shortname);
    }

    /**
     * @throws \moodle_exception
     * @throws \dml_exception
     */
    public function test_course_creation_with_non_existent_category(): void
    {
        global $DB;

        $this->resetAfterTest();

        $course_name = 'Test course';
        $app_course_id = 2;
        $category_name = 'NonExistentCategory';

        $result = (object)local_formationsapi_api::create_course($course_name, $app_course_id, $category_name);
        $course = $DB->get_record('course', ['id' => $result->course_id]);
        $category = $DB->get_record('course_categories', ['idnumber' => $category_name]);

        self::assertEquals("1", $course->enablecompletion);
        self::assertEquals($app_course_id, $course->idnumber);
        self::assertEquals($category->id, $course->category);
        self::assertEquals(1, $course->visible);
        self::assertEquals($course_name, $course->fullname);
        self::assertEquals($course_name, $course->shortname);
    }

    /**
     * @throws \moodle_exception
     */
    public function test_course_already_exists(): void
    {
        $this->resetAfterTest();

        $course_name = 'test_course';
        $app_course_id = 2;
        $category_name = 'TestCategory';
        self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $first_course = (object)local_formationsapi_api::create_course($course_name, $app_course_id, $category_name);
        $second_course = (object)local_formationsapi_api::create_course($course_name, $app_course_id, $category_name);
        self::assertEquals($first_course->course_id, $second_course->course_id);
    }

    /**
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \invalid_parameter_exception
     */
    public function test_closing_and_deleting_course(): void
    {
        global $DB;
        $this->resetAfterTest();

        $course_name = 'test_course';
        $app_course_id = 2;
        $category_name = 'TestCategory';
        self::getDataGenerator()->create_category(['idnumber' => $category_name]);
        $course = (object)local_formationsapi_api::create_course($course_name, $app_course_id, $category_name);
        local_formationsapi_api::close_course($app_course_id);
        $courseObject = $DB->get_record('course', ['id' => $course->course_id]);
        self::assertEquals(0, $courseObject->visible);
        //course_deletion
        local_formationsapi_api::delete_course($app_course_id);
        $courseObject = $DB->get_record('course', ['id' => $course->course_id]);
        self::assertFalse($courseObject);
    }


    /**
     * @throws \dml_exception
     */
    public function test_user_enrolment(): void
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
        $course = self::getDataGenerator()->create_course(['category' => $category->id, 'idnumber' => 3]);
        local_formationsapi_api::enrol_user($user->email, $user->firstname, $user->lastname, 3, 'student');
        self::assertTrue(is_enrolled(context_course::instance($course->id), $user->id));

        //Case 2: user is automatically created
        $course = self::getDataGenerator()->create_course(['category' => $category->id, 'idnumber' => 4]);
        local_formationsapi_api::enrol_user('random@example.com', 'Arthur', 'Pendragon', 4, 'student');
        $user = $DB->get_record('user', ['username' => 'random@example.com']);
        self::assertTrue(is_enrolled(context_course::instance($course->id), $user->id));

        //unenrolment
        local_formationsapi_api::unenrol_user($user->email, $user->firstname, $user->lastname, 4);
        self::assertFalse(is_enrolled(context_course::instance($course->id), $user->id));
    }

    /**
     * @throws \dml_exception
     */
    public function test_clean_failed_api_calls(): void
    {
        $this->resetAfterTest();
        global $DB;
        $DB->insert_record('local_formationsapi', ['user_email' => 'arthur.pendragon@mail.cz', 'app_course_id' => 1, 'completion' => 0]);
        $this->observer::clean_failed_api_calls(['participantEmail' => 'arthur.pendragon@mail.cz', 'courseId' => 1]);
        self::assertEmpty($DB->get_records('local_formationsapi'));
    }

    /**
     * @throws \dml_exception
     */
    public function test_process_error_replaces_old_value(): void
    {
        $this->resetAfterTest();
        global $DB;
        $DB->insert_record('local_formationsapi', ['user_email' => 'arthur.pendragon@mail.cz', 'app_course_id' => 1, 'completion' => 0]);
        $this->observer::process_error(['participantEmail' => 'arthur.pendragon@mail.cz', 'courseId' => 1, 'completion' => 50]);
        self::assertEquals("50", $DB->get_record('local_formationsapi', ['user_email' => 'arthur.pendragon@mail.cz', 'app_course_id' => 1])->completion);
    }


    protected function setUp(): void
    {
        require_once(__DIR__ . '/../classes/api/local_formationsapi_api.php');
        require_once(__DIR__ . '/../classes/observer.php');
        $this->observer = new local_formationsapi_observer();
    }
}