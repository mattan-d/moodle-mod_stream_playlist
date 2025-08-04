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
 * External API for stream player functionality.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use mod_stream\stream_video;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

require_once($CFG->dirroot . '/mod/stream/locallib.php');

/**
 * External API for stream player functionality.
 */
class player extends external_api {

    /**
     * Returns description of method parameters for get_player.
     *
     * @return external_function_parameters
     */
    public static function get_player_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'identifier' => new external_value(PARAM_TEXT, 'Video identifier'),
            'includeaudio' => new external_value(PARAM_BOOL, 'Include audio', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Get video player HTML.
     *
     * @param int $cmid Course module ID
     * @param string $identifier Video identifier
     * @param bool $includeaudio Include audio
     * @return array
     */
    public static function get_player($cmid, $identifier, $includeaudio = false) {
        global $DB;

        $params = self::validate_parameters(self::get_player_parameters(), [
            'cmid' => $cmid,
            'identifier' => $identifier,
            'includeaudio' => $includeaudio,
        ]);

        // Get course module and validate access
        $cm = get_coursemodule_from_id('stream', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/stream:view', $context);

        try {
            $helper = new stream_video();
            $playerHtml = $helper->player($params['cmid'], $params['identifier'], $params['includeaudio']);

            return [
                'status' => 'success',
                'player' => $playerHtml,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'player' => '',
            ];
        }
    }

    /**
     * Returns description of method result value for get_player.
     *
     * @return external_single_structure
     */
    public static function get_player_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the request'),
            'player' => new external_value(PARAM_RAW, 'Player HTML'),
            'message' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method parameters for mark_viewed.
     *
     * @return external_function_parameters
     */
    public static function mark_viewed_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'videoid' => new external_value(PARAM_TEXT, 'Video ID'),
        ]);
    }

    /**
     * Mark video as viewed.
     *
     * @param int $cmid Course module ID
     * @param string $videoid Video ID
     * @return array
     */
    public static function mark_viewed($cmid, $videoid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::mark_viewed_parameters(), [
            'cmid' => $cmid,
            'videoid' => $videoid,
        ]);

        // Get course module and validate access
        $cm = get_coursemodule_from_id('stream', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/stream:view', $context);

        try {
            // Check if already viewed
            $existing = $DB->get_record('stream_viewed_videos', [
                'streamid' => $cm->instance,
                'userid' => $USER->id,
                'videoid' => $params['videoid'],
            ]);

            if (!$existing) {
                // Insert new viewed record
                $record = new \stdClass();
                $record->streamid = $cm->instance;
                $record->userid = $USER->id;
                $record->videoid = $params['videoid'];
                $record->timeviewed = time();

                $DB->insert_record('stream_viewed_videos', $record);

                // Update grades
                $stream = $DB->get_record('stream', ['id' => $cm->instance]);
                if ($stream) {
                    stream_update_grades($stream, $USER->id);
                }
            }

            return [
                'status' => 'success',
                'message' => 'Video marked as viewed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Returns description of method result value for mark_viewed.
     *
     * @return external_single_structure
     */
    public static function mark_viewed_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the request'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
