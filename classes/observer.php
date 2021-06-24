<?php

use core_completion\progress;

class local_formationsapi_observer
{
    /**
     * @throws \invalid_parameter_exception|\moodle_exception
     */
    public static function update_user_profile(core\event\base $event)
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
        if (!is_null($course_completion_percentage)) {
            $url = get_config('local_formationsapi', 'update_user_call_url');
            if (!$url) {
                throw new invalid_parameter_exception('API endpoint for updating users is not set.');
            }
            $data = [
                'participantEmail' => $user->email,
                'courseId' => (int)$app_course_id,
                'completion' => $course_completion_percentage
            ];

            return self::call_api('PUT', $url, $data);
        }

        return null;
    }

    /**
     * @param string $method POST | PUT
     * @param string $url
     * @param array $data
     * @return bool|string
     * @throws \moodle_exception
     */
    public static function call_api($method, $url, $data = [])
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

        curl_exec($ch);
        $http_error_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_error_code !== 200) {
            self::process_error($data);
            return false;
        }

        self::clean_failed_api_calls($data);
        return true;
    }

    private static function clean_failed_api_calls(array $data){
        global $DB;
        $DB->delete_records(
            'local_formationsapi',
            [
                'user_email' => $data['participantEmail'],
                'app_course_id' => $data['courseId']
            ]
        );
    }

    private static function process_error(array $data)
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
    }
}