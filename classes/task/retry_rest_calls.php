<?php

namespace local_formationsapi\task;

use local_formationsapi_observer;

class retry_rest_calls extends \core\task\scheduled_task
{
    /**
     * @param array $data
     * @throws \dml_exception
     */
    private static function send_mail(array $data): void
    {
        $mails = explode(',', trim(get_config('local_formationsapi', 'admin_emails')));
        $subject = 'Error while updating user';
        $message = count($data) . " personne(s) en erreur. \n\n" . json_encode($data, JSON_PRETTY_PRINT);
        $headers = "From: " . get_admin()->email . "\r\n";
        foreach ($mails as $mail) {
            mail($mail, $subject, $message, $headers);
        }
    }

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
     * @throws \dml_exception
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

            local_formationsapi_observer::call_api('PUT', $url, $data) !== 200 || local_formationsapi_observer::clean_failed_api_calls($data);
        }
        self::send_mail($failed_api_calls);
    }

}