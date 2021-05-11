<?php

global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');


class api extends external_api
{
    /**
     * Creates new course
     *
     * @throws \moodle_exception
     * @returns course course
     */
    public function create_course($course_title, $category_id_number): ?array
    {
        global $DB;
        $cat_id = $DB
            ->get_record(
                'course_categories',
                ['idnumber' => $category_id_number],
                '*',
                MUST_EXIST
            )
            ->id;

        self::validate_parameters(self::create_course_parameters(), [
            'course_title' => $course_title,
            'category_id_number' => $category_id_number
        ]);

        $data = (object)[
            'fullname' => $course_title,
            'category' => (int)$cat_id
        ];

        return ['course_id' => (int)create_course($data)->id];
    }

    /**
     * @return \external_single_structure
     */
    public static function create_course_returns(): external_single_structure
    {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'ID of the created course')
        ]);
    }

    /**
     * @return external_function_parameters
     * @throws \invalid_parameter_exception|\dml_exception
     */
    public static function create_course_parameters(): external_function_parameters
    {
        $course_category_id = get_config('local_formationsapi', 'course_category_id');
        if (empty($course_category_id)) {
            throw new invalid_parameter_exception('Course category id not set in plugin settings.');
        }

        return new external_function_parameters([
            'course_title' => new external_value(PARAM_RAW_TRIMMED, ''),
            'category_id_number' => new external_value(PARAM_ALPHANUM, '', VALUE_DEFAULT, $course_category_id)
        ]);
    }

    public static function enrol_user_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'course_title' => new external_value(PARAM_RAW_TRIMMED, 'Course title'),
            'category_id_number' => new external_value(PARAM_ALPHANUM, 'Course namespace')
        ]);
    }

    /**
     * @return \external_single_structure
     */
    public static function enrol_user_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, '')
        ]);
    }

    public function enrol_user()
    {
        //validation
        self::validate_parameters(self::create_course_parameters(), [

        ]);

        $email = strtolower($token_contents->email);

        $user = $DB->get_record('user', [
            'username' => $email,
            'auth' => 'shibboleth',
            'suspended' => 0,
            'deleted' => 0
        ], '*', IGNORE_MISSING);

        if (!$user) {
            // We have to create this user as it does not yet exist.
            $newuser = (object)[
                'auth' => 'shibboleth',
                'confirmed' => 1,
                'policyagreed' => 0,
                'deleted' => 0,
                'suspended' => 0,
                'username' => $email,
                'email' => $email,
                'password' => 'not cached',
                'firstname' => $email,
                'lastname' => $email,
                'timecreated' => time(),
                'mnethostid' => $SITE->id,
            ];
            $newuserid = $DB->insert_record('user', $newuser);
            $user = $DB->get_record('user', ['id' => $newuserid], '*', MUST_EXIST);
        }

        return true;
    }
}