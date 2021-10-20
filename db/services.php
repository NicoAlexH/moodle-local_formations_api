<?php
$services = [
    'formationsapiservice' => [                                                // the name of the web service
        'functions' => ['local_formationsapi_create_course', 'local_formationsapi_enrol_user', 'local_formationsapi_close_course', 'local_formationsapi_delete_course'], // web service functions of this service
        'requiredcapability' => '',                // if set, the web service user need this capability to access
        // any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 1,                                             // if enabled, the Moodle administrator must link some user to this service
        // into the administration
        'enabled' => 1,                                                       // if enabled, the service can be reachable on a default installation
        'shortname' => 'formationsapiservice',       // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 0,    // allow file downloads.
        'uploadfiles' => 0      // allow file uploads.
    ]
];

$functions = [
    'local_formationsapi_create_course' => [
        'classname' => 'local_formationsapi_api',
        'methodname' => 'create_course',
        'classpath' => 'local/formationsapi/classes/api/local_formationsapi_api.php',
        'description' => 'Creates new course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:create',
    ],
    'local_formationsapi_close_course' => [
        'classname' => 'local_formationsapi_api',
        'methodname' => 'close_course',
        'classpath' => 'local/formationsapi/classes/api/local_formationsapi_api.php',
        'description' => 'Closes a course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:changevisibility',
    ],
    'local_formationsapi_delete_course' => [
        'classname' => 'local_formationsapi_api',
        'methodname' => 'delete_course',
        'classpath' => 'local/formationsapi/classes/api/local_formationsapi_api.php',
        'description' => 'Deletes a course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:delete',
    ],
    'local_formationsapi_enrol_user' => [
        'classname' => 'local_formationsapi_api',
        'methodname' => 'enrol_user',
        'classpath' => 'local/formationsapi/classes/api/local_formationsapi_api.php',
        'description' => 'Add user to course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'enrol/manual:enrol',
    ],
];