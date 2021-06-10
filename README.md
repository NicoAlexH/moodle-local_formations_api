# API Formations Plugin

This plugin exposes three new API endpoints :

- **local_formationsapi_create_course (String course_title, Int conference_course_id, String category_name): course_id**

This method allows to create a new course by specifying a title and the name of an existing course_category. It returns the id of the created course.

Example call :

`curl '[MOODLE_URL]/webservice/rest/server.php?wstoken=[YOUR_TOKEN]&wsfunction=local_formationsapi_create_course&course_title=toto&category_name=test&moodlewsrestformat=json'`

returns 

`{"course_id":3, "url": "[MOODLE_URL]/auth/shibboleth/index.php?target=[MOODLE_URL]/course/view.php?id=3"}`

- **local_formationsapi_close_course (Int conference_course_id): course_id**

This method allows to close a course based on the conference_course_id passed as a parameter.

Example call :

`curl '[MOODLE_URL]/webservice/rest/server.php?wstoken=[YOUR_TOKEN]&wsfunction=local_formationsapi_close_course&conference_course_id=1&moodlewsrestformat=json'`

returns

`{"success":true}`


- **local_formationsapi_enrol_user(String user_email, String user_firstname, String user_lastname, Int course_id, String role_shortname): success**

This method allows to enrol a user in a course by specifying user information, a course id and the desired user role for the course ('student' or 'teacher').

Example call :
`[MOODLE_URL]/webservice/rest/server.php?wstoken=[YOUR_TOKEN]&wsfunction=local_formationsapi_enrol_user&user_email=toto@toto.com&user_firstname=Toto&user_lastname=Tutu&course_id=3&role_shortname=student&moodlewsrestformat=json`

returns 

`{"success": true}`