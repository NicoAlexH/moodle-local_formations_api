<?php

global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');


class local_formationsapi_api extends external_api
{
    /**
     * @return \external_single_structure
     */
    public static function create_course_returns(): external_single_structure
    {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'ID of the created course'),
            'course_url' => new external_value(PARAM_URL, 'URL of the created course')
        ]);
    }

    /**
     * @return \external_single_structure
     */
    public static function enrol_user_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if the user has been enrolled in the course, else false.')
        ]);
    }

    public static function close_course_returns(): external_single_structure
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if the course has been closed, else false.')
        ]);
    }

    /**
     * Creates new course
     *
     * @throws \moodle_exception
     * @returns array[course_id, course_url]
     */
    public function create_course($course_title, $conference_course_id, $category_name): ?array
    {
        global $DB;

        self::validate_parameters(self::create_course_parameters(), [
            'course_title' => $course_title,
            'conference_course_id' => $conference_course_id,
            'category_name' => $category_name
        ]);

        if ($data = $DB->get_record(
            'course',
            ['shortname' => $conference_course_id],
            'id'
        )) {
            http_response_code(409);

            return [
                'course_id' => $data->id,
                'course_url' => (string)new moodle_url('/auth/shibboleth/index.php?target='
                    . new moodle_url('/course/view.php', ['id' => $data->id]))];
        }

        $cat_id = $DB
            ->get_record(
                'course_categories',
                ['idnumber' => $category_name],
                '*',
                MUST_EXIST
            )
            ->id;

        $data = (object)[
            'fullname' => $course_title,
            'shortname' => $conference_course_id,
            'category' => (int)$cat_id,
            'enablecompletion' => 1
        ];
        $data->id = (int)create_course($data)->id;
        $DB->update_record('course', $data);

        return [
            'course_id' => $data->id,
            'course_url' => (string)new moodle_url('/auth/shibboleth/index.php?target='
                . new moodle_url('/course/view.php', ['id' => $data->id]))
        ];
    }

    /**
     * @return external_function_parameters
     * @throws \invalid_parameter_exception|\dml_exception
     */
    public static function create_course_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'course_title' => new external_value(PARAM_RAW_TRIMMED, ''),
            'conference_course_id' => new external_value(PARAM_INT, ''),
            'category_name' => new external_value(PARAM_ALPHANUM, '')
        ]);
    }

    public function close_course($conference_course_id)
    {
        global $DB;

        self::validate_parameters(self::close_course_parameters(), [
            'conference_course_id' => $conference_course_id,
        ]);

        if ($course = $DB->get_record('course', ['shortname' => $conference_course_id])) {
            $course->visible = 0;

            return ['success' => $DB->update_record('course', $course)];
        }

        return ['success' => false];
    }

    public static function close_course_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'conference_course_id' => new external_value(PARAM_INT, 'Course ID on the Conference platform'),
        ]);
    }

    /**
     * Enrolls a user in a course. If the user does not exist, it is created.
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     */
    public function enrol_user($user_email, $user_firstname, $user_lastname, $course_id, $role_shortname): array
    {
        global $DB;

        self::validate_parameters(self::enrol_user_parameters(), [
            'user_email' => $user_email,
            'user_firstname' => $user_firstname,
            'user_lastname' => $user_lastname,
            'course_id' => $course_id,
            'role_shortname' => $role_shortname
        ]);

        $user_email = strtolower($user_email);

        $user = $DB->get_record('user', [
            'username' => $user_email,
            'auth' => 'shibboleth',
            'suspended' => 0,
            'deleted' => 0
        ]);

        if (!$user) {
            $user_data = (object)['email' => $user_email, 'firstname' => $user_firstname, 'lastname' => $user_lastname];
            $user = self::create_user($user_data);
        }

        $role = $DB->get_record('role', ['shortname' => $role_shortname], 'id', MUST_EXIST);

        return ['success' => self::enrol_user_in_course($user->id, $course_id, $role->id)];
    }

    /**
     * @return \external_function_parameters
     */
    public static function enrol_user_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'user_email' => new external_value(PARAM_EMAIL, 'User email'),
            'user_firstname' => new external_value(PARAM_RAW_TRIMMED, 'User firstname'),
            'user_lastname' => new external_value(PARAM_RAW_TRIMMED, 'User lastname'),
            'course_id' => new external_value(PARAM_INT, 'Course ID'),
            'role_shortname' => new external_value(
                PARAM_ALPHA, 'Role shortname to assign to the user (student|teacher)'
            )
        ]);
    }

    /**
     * creates user from data
     * @param $user_data Object
     * @throws \dml_exception
     * @returns user
     */
    private static function create_user(object $user_data)
    {
        global $DB, $CFG;

        $newuser = (object)[
            'auth' => 'shibboleth',
            'confirmed' => 1,
            'policyagreed' => 0,
            'deleted' => 0,
            'suspended' => 0,
            'username' => $user_data->email,
            'email' => $user_data->email,
            'password' => 'not cached',
            'firstname' => $user_data->firstname,
            'lastname' => $user_data->lastname,
            'timecreated' => time(),
            'mnethostid' => $CFG->mnet_localhost_id,
        ];

        $newuserid = $DB->insert_record('user', $newuser);

        return $DB->get_record('user', ['id' => $newuserid], '*', MUST_EXIST);
    }


    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function enrol_user_in_course($user_id, $course_id, $role_id): bool
    {
        global $DB;

        $context = context_course::instance($course_id);

        if (!is_enrolled($context, $user_id)) {
            $plugin_instance = $DB->get_record("enrol", ['courseid' => $course_id, 'enrol' => 'manual']);
            $plugin = enrol_get_plugin('manual');
            $plugin->enrol_user($plugin_instance, $user_id, $role_id);
        }

        return true;
    }
}