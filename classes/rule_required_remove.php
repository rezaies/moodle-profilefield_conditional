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
 * This file contains the rule_required_remove class.
 *
 * @package    profilefield_conditional
 * @copyright  2016 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_conditional;
defined('MOODLE_INTERNAL') || die();

/**
 * Class rule_required_remove
 *
 * @copyright  2016 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_required_remove extends \HTML_QuickForm_Rule  {
    /**
     * Checks if an element is not empty.
     * This is a server-side validation, it works for both text fields and editor fields
     *
     * @param string $value Value to check
     * @param int|string|array $options Not used yet
     * @return bool true if value is not empty
     */
    public function validate($value, $options = null) {
        $mform = $options[0];
        $options = $options[1];

        // Removing "required" conditions of fields that can be hidden.
        // This covers form fields that are placed after the conditional field.
        foreach ($options->options as $key => $option) {
            if (!empty($options->disabledset[$key])) {
                foreach ($options->disabledset[$key] as $element) {
                    if (false !== $pos = array_search("profile_field_{$element}", $mform->_required)) {
                        array_splice($mform->_required, $pos, 1);
                    }
                    if (isset($mform->_rules["profile_field_{$element}"])) {
                        foreach ($mform->_rules["profile_field_{$element}"] as $key => $rule) {
                            if ($rule['type'] == 'required') {
                                unset($mform->_rules["profile_field_{$element}"][$key]);
                            }
                        }
                    }
                }
            }
        }

        $submittedvalues = $mform->getSubmitValues();

        // We couldn't merge this into the previous one as the previous loop needs to finish to its end.
        if (!empty($options->disabledset[$value])) {
            foreach ($options->disabledset[$value] as $element) {
                if (array_key_exists("profile_field_$element", $submittedvalues)) {
                    return false;
                }
            }
        }

        return true;
    }
}