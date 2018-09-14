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
 * @package    assignsubmission_qrcode
 * @copyright  2018 michael pollak <moodle@michaelpollak.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// https://docs.moodle.org/dev/Assign_submission_plugins#Overview_of_an_assignment_submission_plugin
class assign_submission_qrcode extends assign_submission_plugin {

    public function get_name() {
        return get_string('qrcode', 'assignsubmission_qrcode');
    }

    public function get_settings(MoodleQuickForm $mform) {
        // TODO: Add helptext.
        // Add motivational greetings for the learners.
        if($this->get_config('greetings') != '') $fieldtext = $this->get_config('greetings');
        else $fieldtext = 'You can only submit this assignment when you find the magic markers.'; // TODO: Multilang.
        $mform->addElement('text', 'assignsubmission_qrcode_greetings', 'Hints or encouragement'); // TODO: Multilang.
        $mform->setType('assignsubmission_qrcode_greetings', PARAM_TEXT);
        $mform->setDefault('assignsubmission_qrcode_greetings', $fieldtext);
        $mform->hideIf('assignsubmission_qrcode_greetings', 'assignsubmission_qrcode_enabled', 'notchecked');

        // Add a congratulation message to your successfull students.
        if($this->get_config('congrats') != '') $fieldtext = $this->get_config('congrats');
        else $fieldtext = 'Woop! You found the next badge!'; // TODO: Multilang.
        $mform->addElement('text', 'assignsubmission_qrcode_congrats', 'Congratulate after submission'); // TODO: Multilang.
        $mform->setType('assignsubmission_qrcode_congrats', PARAM_TEXT);
        $mform->setDefault('assignsubmission_qrcode_congrats', $fieldtext);
        $mform->hideIf('assignsubmission_qrcode_congrats', 'assignsubmission_qrcode_enabled', 'notchecked');

        // Add a secret so url can not be guessed.
        if($this->get_config('secret') != '') $fieldtext = $this->get_config('secret');
        else $fieldtext = 'topsecret';
        $mform->addElement('text', 'assignsubmission_qrcode_secret', 'Some secret so url cannot be guessed'); // TODO: Multilang.
        $mform->setType('assignsubmission_qrcode_secret', PARAM_TEXT);
        $mform->setDefault('assignsubmission_qrcode_secret', $fieldtext);
        $mform->hideIf('assignsubmission_qrcode_secret', 'assignsubmission_qrcode_enabled', 'notchecked');
    }

    public function save_settings(stdClass $data) {
        $this->set_config('greetings', $data->assignsubmission_qrcode_greetings);
        $this->set_config('secret', $data->assignsubmission_qrcode_secret);
        $this->set_config('congrats', $data->assignsubmission_qrcode_congrats);
        return true;
    }

    public function submission_is_empty(stdClass $data) {
        return false; // We don't care.
    }
    public function is_empty(stdClass $submission) {
        return false; // We don't care.
    }

    /**
     * Return a description of external params suitable for uploading an onlinetext submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        return array(
            'secret' => new external_value(
                PARAM_TEXT,
                'The secret for this assignment.',
                VALUE_OPTIONAL
            )
        );
    }

    /**
     *  Add the secret field for submission by learners. This will become a fallback if QR Codes are not working.
     */
    public function get_form_elements_for_user($submissionorgrade, MoodleQuickForm $mform, stdClass $data, $userid) {
        $mform->addElement('static', 'qrnote', 'NOTE:', $this->get_config('greetings'));
        $mform->addElement('text', 'secret', 'Do you know the secret'); // TODO: Multilang.
        $mform->setType('secret', PARAM_TEXT);
        return $this->get_form_elements($submissionorgrade, $mform, $data);
    }

    /**
     * Save submission to the database
     */
    public function save(stdClass $submission, stdClass $data) {
        $secret = $data->secret;
        // Check if the passed secret matches the one we stored.
        if ($this->get_config('secret') == $secret) {
            return true;
        } else {
            // Return error message.
            $this->set_error('The secret you entered was not correct.'); // TODO: Multilang.
            return false;
        }
    }

    /**
     * If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     *
     * @return bool
     */
    public function has_user_summary() {
        return false;
    }

    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * @return string
     */
    public function view_header() {
        global $CFG;
        // We display the QR code right here.
        // Check if this is a teacher.
        $context = $this->assignment->get_context();
        if(has_capability('mod/assign:grade', $context)) {
            $instance = $this->assignment->get_instance();

            // Url to encode.
            $submiturl = $CFG->wwwroot.'/mod/assign/submission/qrcode/submitnow.php?assignment='.$instance->id;
            $submiturl .= '&id='.$context->instanceid.'&secret='.$this->get_config('secret');

            // Teachers can pass a congratulation message.
            $submiturl .= '&congrats='.$this->get_config('congrats');

            // Testmessage for debugging or simply to try it out.
            $message = '<a href=\''.$submiturl.'\'>Test submission link.</a>';
            $message .=  '<img src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='. urlencode($submiturl) .'">';

            return $message;
        }
    }
}