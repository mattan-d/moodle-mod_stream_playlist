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
 * Stream configuration form.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Mod Form.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_stream_mod_form extends moodleform_mod {

    /**
     * Definition.
     *
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     */
    public function definition() {
        global $PAGE, $OUTPUT, $USER, $DB;

        $mform = $this->_form;
        $PAGE->requires->jquery();
        $PAGE->requires->js_call_amd('mod_stream/main', 'init');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('nametitle', 'stream'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Video Recordings Collection Mode
        $mform->addElement('advcheckbox', 'collection_mode', get_string('collectionmode', 'stream'),
                get_string('collectionmode_desc', 'stream'));
        $mform->addHelpButton('collection_mode', 'collectionmode', 'stream');
        $mform->setDefault('collection_mode', 0);

        $mform->addElement('hidden', 'identifier');
        $mform->setType('identifier', PARAM_TEXT);
        $mform->addRule('identifier', null, 'required', null, 'client');

        // Add the video_order hidden field to the form
        $mform->addElement('hidden', 'video_order');
        $mform->setType('video_order', PARAM_TEXT);

        // Set default values for existing instances
        if (!empty($this->current->instance)) {
            $stream = $DB->get_record('stream', ['id' => $this->current->instance]);
            if ($stream) {
                // Set default for identifier
                $mform->setDefault('identifier', $stream->identifier);

                // Set default for collection_mode
                $mform->setDefault('collection_mode', $stream->collection_mode ?? 0);

                // Set default for video_order
                if (!empty($stream->video_order)) {
                    $mform->setDefault('video_order', $stream->video_order);
                } else {
                    // If no video_order exists, create one from the identifier order
                    $identifiers = array_filter(explode(',', $stream->identifier));
                    if (!empty($identifiers)) {
                        $mform->setDefault('video_order', json_encode(array_values($identifiers)));
                    } else {
                        $mform->setDefault('video_order', '[]');
                    }
                }
            }
        } else {
            // For new instances, set empty defaults
            $mform->setDefault('video_order', '[]');
        }

        $this->standard_intro_elements();

        // Only show video search if collection mode is disabled
        $mform->addElement('html', '<div id="video-search-container">');
        $mform->addElement('html', $OUTPUT->render_from_template('mod_stream/search', [
                'endpoint' => get_config('stream', 'apiendpoint'),
                'token' => get_config('stream', 'accountid'),
                'email' => $USER->email,
        ]));
        $mform->addElement('html', '</div>');

        // Add JavaScript to handle collection mode behavior
        $PAGE->requires->js_init_code('
    function handleCollectionMode() {
        var collectionMode = document.getElementById("id_collection_mode");
        var identifierField = document.querySelector("input[name=\'identifier\']");
        
        if (collectionMode && identifierField) {
            if (collectionMode.checked) {
                // In collection mode, set a special identifier but keep video search visible
                if (identifierField.value === "" || identifierField.value === "auto_collection") {
                    identifierField.value = "auto_collection";
                }
            } else {
                // Not in collection mode, clear auto_collection if it was set
                if (identifierField.value === "auto_collection") {
                    identifierField.value = "";
                }
            }
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        var collectionMode = document.getElementById("id_collection_mode");
        if (collectionMode) {
            collectionMode.addEventListener("change", handleCollectionMode);
            handleCollectionMode(); // Initial call
        }
    });
');

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Collection mode allows either auto_collection or manual selection
        if (empty($data['collection_mode'])) {
            // Not in collection mode - require manual video selection
            if (empty(trim($data['identifier'])) || trim($data['identifier']) === 'auto_collection') {
                $errors['identifier'] = get_string('required');
            }
        }
        // If collection mode is enabled, we accept either auto_collection or manual selection

        // Validate video_order is valid JSON if provided
        if (!empty($data['video_order'])) {
            $decoded = json_decode($data['video_order'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Reset to empty array if invalid JSON
                $data['video_order'] = '[]';
            }
        }

        return $errors;
    }

    /**
     * Add elements to form
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Ensure video_order is properly set during data preprocessing
        if (isset($default_values['video_order']) && !empty($default_values['video_order'])) {
            // Make sure it's valid JSON
            $decoded = json_decode($default_values['video_order'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $default_values['video_order'] = '[]';
            }
        } else {
            $default_values['video_order'] = '[]';
        }
    }
}
