<?php

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
        $course_completion_percentage = self::get_course_completion_percentage($event_data['userid'], $course_id);
        $user = $DB->get_record('user', [
            'id' => $user_id = $event_data['userid']
        ]);
        $url = get_config('local_formationsapi', 'update_user_call_url');
        if (!$url) {
            throw new invalid_parameter_exception('API endpoint for updating users is not set.');
        }
        $data = [
            'user_email' => $user->email,
            'course_id' => $course_id,
            'status_percent' => $course_completion_percentage
        ];

        var_dump(self::call_api('POST', $url, $data));
        die;

        return self::call_api('POST', $url, $data);
    }

    private static function get_course_completion_percentage($user_id, $course_id)
    {
        global $DB, $CFG;
        require_once("{$CFG->libdir}/completionlib.php");

        $course_object = $DB->get_record('course', ['id' => $course_id]);
        $cinfo = new completion_info($course_object);
        $activities = $cinfo->get_progress_all()[$user_id]->progress;
        $completed_activities = 0;
        foreach ($activities as $activity) {
            $completed_activities += $activity->completionstate;
        }

        return 100 * ($completed_activities / count($activities));
    }

    // Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value

    private static function call_api($method, $url, $data = false)
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
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        /*curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "username:password");*/

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }
}