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
 * This file contains rule_required class.
 *
 * @package   profilefield_conditional
 * @copyright 2016 Shamim Rezaie {@link http://foodle.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_conditional;
defined('MOODLE_INTERNAL') || die();

/**
 * Required elements validation
 *
 * This class overrides MoodleQuickForm validation to allow hidden fields be empty
 *
 * @package   profilefield_conditional
 * @copyright 2016 Shamim Rezaie {@link http://foodle.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_required extends \MoodleQuickForm_Rule_Required {
    /**
     * This function returns Javascript code used to build client-side validation.
     * It checks if an element is not empty.
     *
     * @param int $format format of data which needs to be validated.
     * @return array
     */
    public function getValidationScript($format = null) {
        global $DB;

        static $js = '';

        if ($js == '') {
            $conditionalfields = $DB->get_records('user_info_field', array('datatype' => 'conditional'));
            $hiddensettings = array();
            foreach ($conditionalfields as $field) {
                foreach (json_decode($field->param5) as $option) {
                    foreach ($option->hiddenfields as $hiddenfield) {
                        $hiddensettings[$hiddenfield][$field->shortname][] = $option->option;
                    }
                }
            }

            $js = 'var hiddensettings={';
            foreach ($hiddensettings as $subject => $dependencies) {
                $js .= "profile_field_$subject:{";
                foreach ($dependencies as $field => $values) {
                    $js .= "{$field}:[";
                    foreach ($values as $value) {
                        $js .= "'" . addslashes($value) . "',";
                    }
                    $js .= '],';
                }
                $js .= '},';
            }
            $js .= '};
    var ruleenabled = true;
    if (element.name in hiddensettings) {
        for (var key in hiddensettings[element.name]) {
            if (hiddensettings[element.name].hasOwnProperty(key)) {
                if (document.getElementById("id_profile_field_" + key)) {
                    ruleenabled = false;
                    break;
                }
            }
        }
    }';
        }

        list($prefix, $rule) = parent::getValidationScript($format);

        return array($prefix . $js, "ruleenabled && $rule");
    }
}