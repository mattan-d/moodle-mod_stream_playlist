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

// Initialize videos array
$videos = [];
$auto_collected = false;

// Check if this is collection mode
if ($stream->collection_mode) {
    // Handle collection mode - get videos from local_stream plugin
    if (function_exists('local_stream_get_course_videos')) {
        $auto_videos = local_stream_get_course_videos($course->id, $cm->id);
        $auto_collected = true;
        
        // If we have manually selected videos (identifier field), merge them
        if (!empty($stream->identifier) && $stream->identifier !== 'auto_collection' && $stream->identifier !== 'auto_collection_pending') {
            $manual_identifiers = array_values(array_filter(explode(',', $stream->identifier)));
            if (!empty($manual_identifiers)) {
                $manual_videos = mod_stream\stream_video::get_videos_by_id($manual_identifiers);
                
                // Merge auto-collected and manually selected videos, avoiding duplicates
                $video_ids = [];
                $videos = [];
                
                // Add manual videos first (they take priority)
                foreach ($manual_videos as $video) {
                    if (!in_array($video->id, $video_ids)) {
                        $videos[] = $video;
                        $video_ids[] = $video->id;
                    }
                }
                
                // Add auto-collected videos that aren't already included
                foreach ($auto_videos as $video) {
                    if (!in_array($video->id, $video_ids)) {
                        $videos[] = $video;
                        $video_ids[] = $video->id;
                    }
                }
            } else {
                $videos = $auto_videos;
            }
        } else {
            $videos = $auto_videos;
        }
    } else {
        // Fallback: show message that local_stream plugin is required
        echo $OUTPUT->notification(
            get_string('collectionmode_plugin_required', 'stream', 'local_stream'), 
            'notifywarning'
        );
        
        // Still try to show manually selected videos if any
        if (!empty($stream->identifier) && $stream->identifier !== 'auto_collection' && $stream->identifier !== 'auto_collection_pending') {
            $identifiers = array_values(array_filter(explode(',', $stream->identifier)));
            if (!empty($identifiers)) {
                $videos = mod_stream\stream_video::get_videos_by_id($identifiers);
            }
        }
    }
} else {
    // Regular mode - get videos from identifier field
    if (!empty($stream->identifier) && trim($stream->identifier) !== '' && $stream->identifier !== 'auto_collection' && $stream->identifier !== 'auto_collection_pending') {
        $identifiers = array_values(array_filter(explode(',', $stream->identifier)));
        if (!empty($identifiers)) {
            $videos = mod_stream\stream_video::get_videos_by_id($identifiers);
        }
    }
}

// Check if we have any videos before proceeding
if (empty($videos)) {
    if ($stream->collection_mode && $auto_collected) {
        echo $OUTPUT->notification(get_string('noresults', 'stream') . ' - ' . get_string('collectionmode_auto_collected', 'stream'), 'notifyinfo');
    } else {
        echo $OUTPUT->notification(get_string('noresults', 'stream'), 'notifyinfo');
    }
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

// Apply custom order if available
if (!empty($stream->video_order)) {
    $videoOrder = json_decode($stream->video_order, true);
    if (is_array($videoOrder) && !empty($videoOrder)) {
        $orderedVideos = [];
        $videoMap = [];
        
        // Create a map of video ID to video object
        foreach ($videos as $video) {
            $videoMap[$video->id] = $video;
        }
        
        // Add videos in the specified order
        foreach ($videoOrder as $videoId) {
            if (isset($videoMap[$videoId])) {
                $orderedVideos[] = $videoMap[$videoId];
                unset($videoMap[$videoId]);
            }
        }
        
        // Add any remaining videos that weren't in the order (new auto-collected videos)
        foreach ($videoMap as $video) {
            $orderedVideos[] = $video;
        }
        
        $videos = $orderedVideos;
    }
}

if (!empty($videos)) {
    $videos[0]->active = true;
}

$first_video_identifier = !empty($videos) ? $videos[0]->id : '';
$initial_player = !empty($first_video_identifier) ? mod_stream\stream_video::player($cm->id, $first_video_identifier, $includeaudio) : '';

// Determine if we should show the playlist sidebar (only show if more than 1 video)
$show_playlist = count($videos) > 1;

$template_data = [
    'cmid' => $cm->id,
    'includeaudio' => $includeaudio,
    'videos' => $videos,
    'initial_player' => $initial_player,
    'show_playlist' => $show_playlist,
    'single_video' => !$show_playlist,
    'stream_id' => $stream->id,
    'course_id' => $course->id,
    'collection_mode' => $stream->collection_mode,
    'auto_collected' => $auto_collected
];

// Add current video (first video by default)
if (!empty($videos)) {
    $template_data['current_video'] = $videos[0];
}

echo $OUTPUT->render_from_template('mod_stream/playlist', $template_data);

// Update grades for the current user
stream_update_grades($stream, $USER->id);

echo $OUTPUT->footer();
