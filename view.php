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
 * Stream view.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('stream', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $stream = $DB->get_record('stream', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $stream = $DB->get_record('stream', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $stream->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('stream', $stream->id, $course->id, false, MUST_EXIST);
} else {
    throw new \moodle_exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_stream\event\course_module_viewed::create([
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
]);

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $stream);
$event->trigger();

$PAGE->set_url('/mod/stream/view.php', ['id' => $cm->id]);
$PAGE->requires->js_call_amd('mod_stream/playlist', 'init');
$PAGE->set_title(format_string($stream->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

if (trim(strip_tags($stream->intro))) {
    echo $OUTPUT->box(format_module_intro('stream', $stream, $cm->id), 'generalbox', 'intro');
}

$builtinaudioplayerpattern = get_config('stream', 'builtinaudioplayer');
$includeaudio = false;
if (isset($builtinaudioplayerpattern) && !empty($builtinaudioplayerpattern) && isset($PAGE->course->shortname)) {
    if (preg_match($builtinaudioplayerpattern, $PAGE->course->shortname)) {
        $includeaudio = true;
    }
}

$identifiers = array_values(array_filter(explode(',', $stream->identifier)));

if (empty($identifiers)) {
    echo $OUTPUT->notification(get_string('noresults', 'mod_stream'));
    echo $OUTPUT->footer();
    exit;
}

// Get video order if available
$videoOrder = [];
if (!empty($stream->video_order)) {
    try {
        $videoOrder = json_decode($stream->video_order, true);
        if (!is_array($videoOrder)) {
            $videoOrder = [];
        }
    } catch (Exception $e) {
        $videoOrder = [];
    }
}

// Order identifiers based on video_order if available
if (!empty($videoOrder)) {
    $orderedIdentifiers = [];
    // First add videos in the specified order
    foreach ($videoOrder as $videoId) {
        if (in_array($videoId, $identifiers)) {
            $orderedIdentifiers[] = $videoId;
        }
    }
    // Then add any remaining videos that weren't in the order
    foreach ($identifiers as $identifier) {
        if (!in_array($identifier, $orderedIdentifiers)) {
            $orderedIdentifiers[] = $identifier;
        }
    }
    $identifiers = $orderedIdentifiers;
}

$videos = mod_stream\stream_video::get_videos_by_id($identifiers);

// Check if we have any videos before proceeding
if (empty($videos)) {
    echo $OUTPUT->notification(get_string('noresults', 'mod_stream'));
    echo $OUTPUT->footer();
    exit;
}

// Get viewed videos for the current user.
$viewedvideoids = [];
if (!empty($videos)) {
    list($usql, $params) = $DB->get_in_or_equal(array_column($videos, 'id'), SQL_PARAMS_NAMED, 'video');
    $params['userid'] = $USER->id;
    $params['streamid'] = $stream->id;
    $viewedvideos = $DB->get_records_sql("SELECT videoid FROM {stream_viewed_videos}
                                          WHERE userid = :userid AND streamid = :streamid AND videoid $usql", $params);
    $viewedvideoids = array_keys($viewedvideos);
}

foreach ($videos as $video) {
    $video->viewed = in_array($video->id, $viewedvideoids);
}

if (!empty($videos)) {
    $videos[0]->active = true;
}

$first_video_identifier = $identifiers[0];
$initial_player = mod_stream\stream_video::player($cm->id, $first_video_identifier, $includeaudio);

// Determine if we should show the playlist sidebar (only show if more than 1 video)
$show_playlist = count($videos) > 1;

$template_data = [
    'cmid' => $cm->id,
    'includeaudio' => $includeaudio,
    'videos' => $videos,
    'initial_player' => $initial_player,
    'show_playlist' => $show_playlist,
];

echo $OUTPUT->render_from_template('mod_stream/playlist', $template_data);

echo $OUTPUT->footer();
