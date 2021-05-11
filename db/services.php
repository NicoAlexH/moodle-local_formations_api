<?php
$services = [
    'formationsapiservice' => [                                                // the name of the web service
        'functions' => ['local_formationsapi_create_course', 'local_formationsapi_enrol_user'], // web service functions of this service
        'requiredcapability' => '',                // if set, the web service user need this capability to access
        // any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 0,                                             // if enabled, the Moodle administrator must link some user to this service
        // into the administration
        'enabled' => 1,                                                       // if enabled, the service can be reachable on a default installation
        'shortname' =>  'formationsapiservice',       // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 0,    // allow file downloads.
        'uploadfiles'  => 0      // allow file uploads.
    ]
];

$functions = [
    'local_formationsapi_create_course' => [         //web service function name
        'classname'   => 'api',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'create_course',          //external function name
        'classpath'   => 'local/formationsapi/classes/api/api.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Creates new course.',    //human readable description of the web service function
        'type'        => 'write',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls.
        'capabilities' => 'moodle/course:create', // comma separated list of capabilities used by the function.
    ],
    'local_formationsapi_enrol_user' => [         //web service function name
        'classname'   => 'api',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'enrol_user',          //external function name
        'classpath'   => 'local/formationsapi/classes/api/api.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
        // defaults to the service's externalib.php
        'description' => 'Add user to course.',    //human readable description of the web service function
        'type'        => 'write',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ],
];