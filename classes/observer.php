<?php

use core\event\base;
use core_completion\progress;

class local_formationsapi_observer
{
    /**
     * @throws \invalid_parameter_exception|\moodle_exception
     */
    public static function update_user_profile(core\event\base $event)
    {
        $url = get_config('local_formationsapi', 'update_user_call_url');
        if (!$url) {
            throw new invalid_parameter_exception('API endpoint for updating users is not set.');
        }

        $data = self::parse_event($event);

        if ($data['courseId'] > 0 && !is_null($data['completion'])) {
            return self::call_api('PUT', $url, $data) === 200
                ? self::clean_failed_api_calls($data)
                : self::process_error($data);
        }

        return null;
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     * Maintains the consistency between users shibboleth groups and category manager roles
     */
    public static function set_user_role(core\event\base $event): void
    {
        global $DB;
        $shibboleth_groups = explode(';', $_SERVER['unilMemberOf']);
        $manager_role = $DB->get_record('role', ['shortname' => 'manager']);

        self::assign_roles($shibboleth_groups, $manager_role->id, $event->userid);
        self::unassign_roles($shibboleth_groups, $manager_role->id, $event->userid);
    }

    /**
     * Parses the course_completion_updated event in order to get the user course completion info
     * @param \core\event\base $event
     * @return array
     * @throws \dml_exception
     */
    public static function parse_event(base $event): array
    {
        global $DB;
        $event_data = $event->get_data();
        $course_id = $event_data['courseid'];
        $course_object = $DB->get_record(
            'course',
            ['id' => $course_id],
            '*',
            MUST_EXIST
        );
        $app_course_id = $course_object->idnumber;
        $user = $DB->get_record('user',
            ['id' => $event_data['relateduserid']],
            '*',
            MUST_EXIST
        );
        $course_completion_percentage = (int)progress::get_course_progress_percentage($course_object, $user->id);

        return [
            'participantEmail' => $user->email,
            'courseId' => (int)$app_course_id,
            'completion' => $course_completion_percentage
        ];
    }

    /**
     * @param string $method POST | PUT
     * @param string $url
     * @param array $data
     * @return int
     * @throws \moodle_exception
     */
    public static function call_api($method, $url, $data = []): int
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Apikey: ' . get_config('local_formationsapi', 'apikey')
            ]
        );

        $exec = curl_exec($ch) !== false;


        if ($exec) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $http_code;

        }
        return 408;
    }

    /**
     * Deletes rows related to the user and course id specified in the parameter
     * @param array $data
     * @return bool true
     * @throws \dml_exception
     */
    public static function clean_failed_api_calls(array $data): bool
    {
        global $DB;
        $DB->delete_records(
            'local_formationsapi',
            [
                'user_email' => $data['participantEmail'],
                'app_course_id' => $data['courseId']
            ]
        );

        return true;
    }

    /**
     * Inserts a new row or replaces the previous one related to the user and course id specified in the parameter
     * @param array $data
     * @return bool
     * @throws \dml_exception
     */
    public static function process_error(array $data): bool
    {
        global $DB;
        self::clean_failed_api_calls($data);
        $DB->insert_record(
            'local_formationsapi',
            [
                'user_email' => $data['participantEmail'],
                'app_course_id' => $data['courseId'],
                'completion' => $data['completion']
            ]
        );

        return false;
    }

    /**
     * @param array $shibboleth_groups
     * @param int $manager_role_id
     * @param int $userid
     * @return mixed
     * @throws \coding_exception|\dml_exception
     */
    public static function assign_roles(array $shibboleth_groups, int $manager_role_id, $userid): void
    {
        global $DB;

        $prefix = get_config('local_formationsapi', 'admin_groups_prefix') ?: 'app-cours-admin-';

        foreach ($shibboleth_groups as $group_name) {
            if (strpos($group_name, $prefix) !== false) {
                $category_name = str_replace([$prefix, '-g'], '', $group_name);
                $category = $DB->get_record('course_categories', ['idnumber' => $category_name]);
                if ($category) {
                    role_assign($manager_role_id, $userid, context_coursecat::instance($category->id));
                }
            }
        }
    }

    /**
     * @param array $shibboleth_groups
     * @param int $manager_role_id
     * @param int $userid
     * @throws \coding_exception|\dml_exception
     */
    public static function unassign_roles(array $shibboleth_groups, int $manager_role_id, int $userid): void
    {
        global $DB;

        $user_manager_roles = $DB->get_records(
            'role_assignments',
            ['userid' => $userid, 'roleid' => $manager_role_id]
        );
        $prefix = get_config('local_formationsapi', 'admin_groups_prefix') ?: 'app-cours-admin-';

        foreach ($user_manager_roles as $role) {
            $category_id = $DB->get_record('context', ['id' => $role->contextid], 'instanceid')->instanceid;
            $category = $DB->get_record('course_categories', ['id' => $category_id]);

            if ($category && !in_array($prefix . $category->idnumber . '-g', $shibboleth_groups, true)) {
                role_unassign($manager_role_id, $userid, $role->contextid);
            }
        }
    }
}