<?php

function xmldb_local_formationsapi_upgrade($oldversion) {
    global $CFG, $DB;

    $result = TRUE;

    if ($oldversion < 2021062401) {
        $dbman = $DB->get_manager();
        // Define table local_formationsapi to be created.
        $table = new xmldb_table('local_formationsapi');

        // Adding fields to table local_formationsapi.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, false, null);
        $table->add_field('app_course_id', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, false, null);
        $table->add_field('completion', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, false, null);

        // Adding keys to table local_formationsapi.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_formationsapi.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Formationsapi savepoint reached.
        upgrade_plugin_savepoint(true, 2021062401, 'local', 'formationsapi');
    }

    return $result;
}
