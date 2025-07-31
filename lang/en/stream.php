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
 * Plugin strings are defined here.
 *
 * @package     mod_stream
 * @copyright   2024 mattandor <mattan@centricapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Stream';
$string['modulenameplural'] = 'Streams';
$string['modulename_help'] = 'Use the stream module for... | The stream module allows...';
$string['pluginname'] = 'Stream';
$string['pluginadministration'] = 'Stream administration';

// Form strings.
$string['nametitle'] = 'Stream title';
$string['collectionmode'] = 'Video Recordings Collection Mode';
$string['collectionmode_desc'] = 'Enable this option to have new recordings automatically gathered and added as a playlist to this activity (By external plugin local_stream).';
$string['collectionmode_help'] = 'When enabled, this activity will automatically collect video recordings from the course and create a playlist. The videos can still be manually edited and reordered. This feature requires the local_stream plugin to be installed.';

// General strings.
$string['views'] = 'views';
$string['before'] = 'before';
$string['noresults'] = 'No videos found';
$string['selectedvideos'] = 'Selected Videos';
$string['dragtoorder'] = 'Drag to reorder';
$string['servererror'] = 'Server connection failed';

// Collection mode strings.
$string['collectionmode_plugin_required'] = 'The {$a} plugin is required for Video Recordings Collection Mode to work properly.';
$string['collectionmode_auto_collected'] = 'Videos automatically collected from course recordings';
$string['collectionmode_manual_edit'] = 'You can still manually add, remove, or reorder videos below';

// Privacy strings.
$string['privacy:metadata:stream_viewed_videos'] = 'Information about which videos a user has viewed in stream activities.';
$string['privacy:metadata:stream_viewed_videos:userid'] = 'The ID of the user who viewed the video.';
$string['privacy:metadata:stream_viewed_videos:videoid'] = 'The ID of the video that was viewed.';
$string['privacy:metadata:stream_viewed_videos:timecreated'] = 'The time when the video was viewed.';
