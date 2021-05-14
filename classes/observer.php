<?php

use core_completion\progress;

class local_formationsapi_observer
{
    /**
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function update_user_profile(core\event\base $event)
    {
        global $DB;
        $event_data = $event->get_data();
        $course_id = $event_data['courseid'];
        $course_object = $DB->get_record('course', ['id' => $course_id]);
        //$course_completion_percentage = self::get_course_completion_percentage($event_data['userid'], $course_id);
        $user = $DB->get_record('user',
            ['id' => $user_id = $event_data['userid']],
            '*',
            MUST_EXIST
        );
        $course_completion_percentage = progress::get_course_progress_percentage($course_object, $user->id);

        $url = get_config('local_formationsapi', 'update_user_call_url');
        if (!$url) {
            throw new invalid_parameter_exception('API endpoint for updating users is not set.');
        }
        $data = [
            'user_email' => $user->email,
            'course_id' => $course_id,
            'status_percent' => $course_completion_percentage
        ];

        return self::call_api('POST', $url, $data);
    }

    /**
     * @param string $method POST | PUT
     * @param string $url
     * @param array $data
     * @return bool|string
     */
    private static function call_api($method, $url, $data = [])
    {
        $curl = curl_init();

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                throw new moodle_exception('invalid call');
        }

        // Optional Authentication:
        /*curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "username:password");*/
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 10000);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);
        $curl_errno = curl_errno($curl); // 0 if fine
        $response_details = curl_getinfo($curl);

        curl_close($curl);

        if ($output === false) {
            if ($curl_errno) {
                print_error('CURL REQUEST ERROR ' . $curl_errno . ' while calling ' . $url);
            }

            return false;
        }

        try {
            $return = json_decode($output);
        } catch (Exception $e) {
            print_error('api_fail', 'exam', null, $e->getMessage() . $e->getCode());

            return false;
        }

        return $return;
    }
}