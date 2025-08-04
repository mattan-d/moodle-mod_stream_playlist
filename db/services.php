<?php
$functions = array(
    'mod_stream_video_list' => array(
        'classname' => 'mod_stream\external\listing',
        'methodname' => 'video_list',
        'classpath' => '',
        'description' => 'Get list of videos',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ),
    'mod_stream_get_player' => array(
        'classname' => 'mod_stream\external\player',
        'methodname' => 'get_player',
        'classpath' => '',
        'description' => 'Get video player HTML',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ),
    'mod_stream_mark_viewed' => array(
        'classname' => 'mod_stream\external\player',
        'methodname' => 'mark_viewed',
        'classpath' => '',
        'description' => 'Mark video as viewed',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ),
);
?>
