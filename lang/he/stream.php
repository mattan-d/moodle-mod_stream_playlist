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

$string['modulename'] = 'סטרים';
$string['modulenameplural'] = 'סטרימים';
$string['modulename_help'] = 'השתמש במודול הסטרים עבור... | מודול הסטרים מאפשר...';
$string['pluginname'] = 'סטרים';
$string['pluginadministration'] = 'ניהול סטרים';

// Form strings.
$string['nametitle'] = 'כותרת הסטרים';
$string['collectionmode'] = 'מצב איסוף הקלטות וידאו';
$string['collectionmode_desc'] = 'הפעל אפשרות זו כדי שהקלטות חדשות יאספו אוטומטית ויתווספו כרשימת השמעה לפעילות זו (על ידי תוסף חיצוני local_stream).';
$string['collectionmode_help'] = 'כאשר מופעל, פעילות זו תאסוף אוטומטית הקלטות וידאו מהקורס ותיצור רשימת השמעה. עדיין ניתן לערוך ולסדר מחדש את הסרטונים באופן ידני. תכונה זו דורשת התקנה של תוסף local_stream.';

// General strings.
$string['views'] = 'צפיות';
$string['before'] = 'לפני';
$string['noresults'] = 'לא נמצאו סרטונים';
$string['selectedvideos'] = 'סרטונים נבחרים';
$string['dragtoorder'] = 'גרור לסידור מחדש';
$string['servererror'] = 'החיבור לשרת נכשל';

// Collection mode strings.
$string['collectionmode_plugin_required'] = 'תוסף {$a} נדרש כדי שמצב איסוף הקלטות הוידאו יעבוד כראוי.';
$string['collectionmode_auto_collected'] = 'סרטונים נאספו אוטומטית מהקלטות הקורס';
$string['collectionmode_manual_edit'] = 'עדיין ניתן להוסיף, להסיר או לסדר מחדש סרטונים באופן ידני למטה';

// Privacy strings.
$string['privacy:metadata:stream_viewed_videos'] = 'מידע על אילו סרטונים משתמש צפה בפעילויות סטרים.';
$string['privacy:metadata:stream_viewed_videos:userid'] = 'מזהה המשתמש שצפה בסרטון.';
$string['privacy:metadata:stream_viewed_videos:videoid'] = 'מזהה הסרטון שנצפה.';
$string['privacy:metadata:stream_viewed_videos:timecreated'] = 'הזמן שבו נצפה הסרטון.';
