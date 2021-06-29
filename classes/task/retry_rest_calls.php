<?php

namespace local_formationsapi\task;

use local_formationsapi_observer;

class retry_rest_calls extends \core\task\scheduled_task
{
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('retry_rest_calls', 'local_formationsapi');
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;
        $failed_api_calls = $DB->get_records('local_formationsapi');
        $url = get_config('local_formationsapi', 'update_user_call_url');
        foreach ($failed_api_calls as $call_data) {
            $data = [
                'participantEmail' => $call_data->user_email,
                'courseId' => $call_data->app_course_id,
                'completion' => $call_data->completion
            ];
            local_formationsapi_observer::call_api('PUT', $url, $data);
        }
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
        $message = "There was an issue while updating the user " . $data['participantEmail'] . ", and her/his progress ("
            . $data['completion'] . " %) for the course " . $data['courseId']
            . " has not been taken into account. \n\n"
            . "HTTP Error code : " . $http_error_code
            . " \nCurl error (might be empty): " . $curl_errno;
        $headers = "From: " . get_admin()->email . "\r\n";
        foreach ($mails as $mail) {
            mail($mail, $subject, $message, $headers);
        }
    }

}