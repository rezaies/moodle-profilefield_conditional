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
 * This file contains the conditional profile field class.
 *
 * Conditional profile field is very similar to dropdown menu except that you can disable a/some profile field(s) using
 * dropdown.
 *
 * @package    profilefield_conditional
 * @copyright  2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../menu/field.class.php');

/**
 * Class profile_field_conditional
 *
 * @copyright   2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_conditional extends profile_field_menu {

    /** @var array $disabledset */
    public $disabledset;

    /** @var array $requiredset */
    public $requiredset;

    /**
     * Constructor method.
     *
     * Pulls out the options for the conditional from the database and sets the the corresponding key for the data if it exists.
     *
     * @param int $fieldid
     * @param int $userid
     */
    public function __construct($fieldid = 0, $userid = 0) {
        // First call parent constructor.
        parent::__construct($fieldid, $userid);

        $this->disabledset = array();
        $this->requiredset = array();

        // Param 5 for conditional type is the conditions.
        if (isset($this->field->param5)) {
            $conditions = json_decode($this->field->param5);
        } else {
            $conditions = array();
        }

        foreach ($conditions as $key => $condition) {
            foreach ($condition->hiddenfields as $hiddenfield) {
                $this->disabledset[$condition->option][] = $hiddenfield;
            }
            $this->requiredset[$condition->option] = !empty($condition->requiredfields) ? $condition->requiredfields : array();
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param MoodleQuickForm $mform Moodle form instance
     */
    public function edit_field_add($mform) {
        global $PAGE;

        $mform->addElement('select', $this->inputname, format_string($this->field->name), $this->options);

        // MDL-57085: The following chunk would be moved into edit_after_data if edit_after_data were being called for signup form.
        foreach ($this->options as $key => $option) {
            if (!empty($this->disabledset[$key])) {
                foreach ($this->disabledset[$key] as $element) {
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

        // MDL-57085: The following line would be moved into edit_after_data if edit_after_data were being called for signup form.
        $PAGE->requires->js_call_amd('profilefield_conditional/conditions', 'apply',
                array($this->field->shortname, $this->field->param5, $this->field->param4, $mform->getReqHTML()));

        // MDL-57085: The following lines were not required if edit_after_data were being called for signup form.
        MoodleQuickForm::registerRule('required', null, 'profilefield_conditional\rule_required');
        MoodleQuickForm::registerRule('profilefield_conditional_rule', null, 'profilefield_conditional\rule_required_remove');
        $mform->addRule($this->inputname, get_string('extradata', 'profilefield_conditional'), 'profilefield_conditional_rule',
                array(&$mform, $this));
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     **/
    public function edit_validate_field($usernew) {
        global $DB;

        $errors = array();

        if (!empty($usernew->{$this->inputname}) and !empty($this->requiredset[$usernew->{$this->inputname}])) {
            foreach ($this->requiredset[$usernew->{$this->inputname}] as $requiredfield) {

                $data = new stdClass();
                $data->field1 = $this->field->name;
                $data->value1 = $this->options[$usernew->{$this->inputname}];
                $data->field2 = $requiredfield;

                if (isset($usernew->{'profile_field_' . $requiredfield})) {
                    if (is_array($usernew->{'profile_field_' . $requiredfield}) &&
                            isset($usernew->{'profile_field_' . $requiredfield}['text'])) {
                        $value = $usernew->{'profile_field_' . $requiredfield}['text'];
                    } else {
                        $value = $usernew->{'profile_field_' . $requiredfield};
                    }
                } else {
                    $value = '';
                }

                if (($value !== '0') && empty($value)) {
                    if (isset($usernew->{'profile_field_' . $requiredfield})) {
                        $errors['profile_field_' . $requiredfield] = get_string('requiredbycondition1', 'profilefield_conditional',
                                $data);
                    } else {
                        $data->field2 = $DB->get_field('user_info_field', 'name', array('shortname' => $requiredfield));
                        $errors[$this->inputname] = get_string('requiredbycondition2', 'profilefield_conditional', $data);
                    }
                }
            }
        }

        return $errors;
    }
}
