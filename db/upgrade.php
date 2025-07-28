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
 * This file keeps track of upgrades to the Stream plugin
 *
 * @package    mod_stream
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute stream upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_stream_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024121101) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');

        // Change field identifier to text.
        $field = new xmldb_field('identifier', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Drop field topic.
        $field = new xmldb_field('topic');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121101, 'stream');
    }

    return true;
}
