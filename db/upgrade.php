<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Stream upgrade script.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute stream upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_stream_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024121103) {
        // Define field video_order to be added to stream.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('video_order', XMLDB_TYPE_TEXT, null, null, null, null, null, 'identifier');

        // Conditionally launch add field video_order.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121103, 'stream');
    }

    if ($oldversion < 2024121104) {
        // Define field collection_mode to be added to stream.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('collection_mode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'video_order');

        // Conditionally launch add field collection_mode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course_collection (not unique) to be added to stream.
        $index = new xmldb_index('course_collection', XMLDB_INDEX_NOTUNIQUE, ['course', 'collection_mode']);

        // Conditionally launch add index course_collection.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121104, 'stream');
    }

    return true;
}
