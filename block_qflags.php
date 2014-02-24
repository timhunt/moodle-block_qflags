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
 * Flagged questions block.
 *
 * @package   block_qflags
 * @copyright 2014 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Flagged questions block.
 *
 * Show all questions that have been flagged within a course.
 *
 * @copyright 2014 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_qflags extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_qflags');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $USER, $CFG, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $flags = $DB->get_records_sql("
                SELECT qa.id, quiz.name, quiza.id AS attemptid, quiza.attempt, qa.slot, quiza.layout

                  FROM {question_attempts} qa
                  JOIN {quiz_attempts}     quiza ON quiza.uniqueid = qa.questionusageid
                  JOIN {quiz}              quiz  ON quiz.id = quiza.quiz

                 WHERE qa.flagged   = 1
                   AND quiz.course  = :courseid
                   AND quiza.userid = :userid

              ORDER BY quiz.name, qa.slot

                ", array('courseid' => $this->page->course->id, 'userid' => $USER->id),
                0, $CFG->block_qflags_maxflags);

        if (empty($flags)) {
            $this->content->text = get_string('noflaggedquestions', 'block_qflags');
            return $this->content;
        }

        $links = array();
        foreach ($flags as $flag) {
            $layout = explode(',', $flag->layout);
            $flag->page = 1;
            $firstonpage = true;
            foreach ($layout as $item) {
                if ($item == 0) {
                    $flag->page += 1;
                    $firstonpage = true;
                } else if ($item == $flag->slot) {
                    break;
                } else {
                    $firstonpage = false;
                }
            }

            $anchor = null;
            if (!$firstonpage) {
                $anchor = 'q' . $flag->slot;
            }

            $flag->name = format_string($flag->name);
            $links[] = html_writer::link(
                    new moodle_url('/mod/quiz/attempt.php',
                        array('attempt' => $flag->attemptid, 'page' => $flag->page - 1),
                        $anchor),
                    get_string('attemptatquiz', 'block_qflags', $flag));
        }
        $this->content->text = '<ul><li>' . implode('</li><li>', $links) . '</li></ul>';

        return $this->content;
    }

    function applicable_formats() {
        return array('course' => true, 'mod-quiz' => true);
    }
}
