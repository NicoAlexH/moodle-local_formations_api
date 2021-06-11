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
        $conference_course_id = $course_object->idnumber;
        $user = $DB->get_record('user',
            ['id' => $user_id = $event_data['userid']],
            '*',
            MUST_EXIST
        );
        $course_completion_percentage = progress::get_course_progress_percentage($course_object, $user->id);
        if (!is_null($course_completion_percentage)) {
            $url = get_config('local_formationsapi', 'update_user_call_url');
            if (!$url) {
                throw new invalid_parameter_exception('API endpoint for updating users is not set.');
            }
            $data = [
                'participantEmail' => $user->email,
                'courseId' => $conference_course_id,
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
    private static function call_api($method, $url, $data = [])
    {
        $curl = curl_init();
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                throw new moodle_exception('invalid call');
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, 'Content-type: application/json');
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 10000);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);
        $curl_errno = curl_errno($curl); // 0 if fine
        $http_error_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_error_code !== 200) {
            self::send_mail($data, $http_error_code, $curl_errno);

            return false;
        }

        return true;
    }

    /**
     * @param array $data
     * @param $http_error_code
     * @param int $curl_errno
     * @throws \dml_exception
     */
    private static function send_mail(array $data, $http_error_code, int $curl_errno): void
    {
        $mails = explode(',', trim(get_config('local_formationsapi', 'admin_emails')));
        $subject = 'Error while updating user';
        $message = "There was an issue while updating the user " . $data['user_email'] . ", and her/his progress ("
            . $data['status_percent'] . " %) for the course " . $data['course_id']
            . " has not been taken into account. \n\n"
            . "HTTP Error code : " . $http_error_code
            . " \nCurl error (might be empty): " . $curl_errno;
        $headers = "From: " . get_admin()->email . "\r\n";
        foreach ($mails as $mail) {
            mail($mail, $subject, $message, $headers);
        }
    }
}