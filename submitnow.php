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
require(__DIR__ . '/../../../../config.php');
require_once('../../externallib.php');
$assignment = required_param('assignment', PARAM_INT);
$secret = required_param('secret', PARAM_TEXT);
$congrats = required_param('congrats', PARAM_TEXT);
$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm); // We do require login.

$PAGE->set_url('/mod/assign/view.php', array('id' => $cm->id));

// Implement a secret so users can't simply guess url.
$plugindata = array('secret'=>$secret);

$assignObject = new mod_assign_external();
$assignObject->save_submission($assignment, $plugindata);

$url = new moodle_url('/course/view.php', array('id' => $course->id));
redirect($url, $congrats, null, \core\output\notification::NOTIFY_INFO);
?>