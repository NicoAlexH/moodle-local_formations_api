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
}