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
 * This file contains the conditional profile field definition class.
 *
 * If you use profilefield_conditional instead of dropdown menu, you will be abe to hide one/some of the profile fields.
 * In order to do so, you have to enter the options of the dropdown in a specific way.
 *
 * @package    profilefield_conditional
 * @copyright  2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../menu/define.class.php');

/**
 * Class profilefield_conditional
 *
 * @copyright  2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_conditional extends profile_define_menu {

    /**
     * Adds elements to the form for creating/editing this type of profile field.
     * @param MoodleQuickForm $form
     */
    public function define_form_specific($form) {
        global $PAGE;

        parent::define_form_specific($form);

        $form->addHelpButton('param1', 'conditionalhelp', 'profilefield_conditional');
        $form->addRule('param1', get_string('profilemenunooptions', 'admin'), 'required', null, 'client');

        $form->addElement('button', 'conditionconfigbutton', get_string('configurecondition', 'profilefield_conditional'));
        $fieldid = optional_param('id', 0, PARAM_INT);

        // Param 5 for conditional type contains all the conditions in JSON format.
        $form->addElement('hidden', 'param5', '', array('id' => 'profilefield_conditional_conditionconfiguration'));
        $form->setType('param5', PARAM_RAW);
        $PAGE->requires->js_call_amd('profilefield_conditional/conditionconfig', 'init', array('#id_param1',
            '#profilefield_conditional_conditionconfiguration', '#id_conditionconfigbutton', $fieldid));

        // Param 4 for conditional type determines if all hidden fields are going to be initially hidden or not.
        $form->addElement('selectyesno', 'param4', get_string('hiddeninitially', 'profilefield_conditional'));
        $form->addHelpButton('param4', 'hiddeninitially', 'profilefield_conditional');
        $form->setDefault('param4', 1); // Defaults to 'yes'.
        $form->setType('param4', PARAM_INT);
    }

    /**
     * Returns an array of the short names of the profile fields.
     * @return array $pfields
     */
    private function profile_fields() {
        global $DB;
        // Store the profile fields in an array called "names".
        if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
            foreach ($categories as $category) {
                if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                    foreach ($fields as $field) {
                        $pfields[] = format_string($field->shortname);
                    }
                }
            }
        }
        return $pfields;
    }

    /**
     * Checks whether the input is a profile field or not.
     * @param string $tobechecked
     * @return boolean $isprofilefield
     */
    public function is_profilefield($tobechecked) {
        $tobechecked = explode('[', $tobechecked)[0];

        $profilefields = $this->profile_fields();
        if (in_array($tobechecked, $profilefields)) {
            $isprofilefield = true;
        } else {
            $isprofilefield = false;
        }
        return $isprofilefield;
    }

    /**
     * Validates data for the profile field.
     *
     * @param array $data
     * @param array $files
     * @return array $err
     */
    public function define_validate_specific($data, $files) {
        $err = parent::define_validate_specific($data, $files);

        if (empty($err)) {
            $data->param1 = str_replace( "\r", '', $data->param1 );
            $options = explode("\n", $data->param1);

            if (empty($data->param5)) {
                $err['conditionconfigbutton'] = get_string('emptycondition', 'profilefield_conditional');
            } else {
                $conditions = json_decode($data->param5);
                $conditionoptions = array();
                foreach ($conditions as $condition) {
                    $conditionoptions[] = $condition->option;
                }
                if (!empty(array_diff($options, $conditionoptions))) {
                    $err['conditionconfigbutton'] = get_string('optionconditionmismatch', 'profilefield_conditional');
                }

                foreach ($conditions as $condition) {
                    foreach ($condition->requiredfields as $field) {
                        if (!$this->is_profilefield($field)) {
                            $err['conditionconfigbutton'] = get_string('notaprofilefield', 'profilefield_conditional');
                        }
                    }
                    foreach ($condition->hiddenfields as $field) {
                        if (!$this->is_profilefield($field)) {
                            $err['conditionconfigbutton'] = get_string('notaprofilefield', 'profilefield_conditional');
                        }
                    }
                    if (array_intersect($condition->requiredfields, $condition->hiddenfields)) {
                        $err['conditionconfigbutton'] = get_string('hiddenrequired', 'profilefield_conditional');
                    }
                }
            }
        }
        return $err;
    }
}

