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
 * If you use profilefield_conditional instead of the dropdown menu, you will be able to hide one/some of the profile fields.
 * To do so, you have to enter the options of the dropdown in a specific way.
 *
 * @package    profilefield_conditional
 * @copyright  2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../menu/define.class.php');

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
        global $OUTPUT;

        $a = new stdClass();
        $a->donations = 4;
        $a->years = 2025 - 2016;
        $a->donationlink = 'https://ko-fi.com/A31043G';

        $element = $form->createElement(
            'html',
            $OUTPUT->notification(
                get_string('donationalert', 'profilefield_conditional', $a),
                \core\output\notification::NOTIFY_INFO
            )
        );
        $form->insertElementBefore($element, $form->_elements[0]->getName());

        parent::define_form_specific($form);

        $form->addHelpButton('param1', 'conditionalhelp', 'profilefield_conditional');
        $form->addRule('param1', get_string('profilemenunooptions', 'admin'), 'required', null, 'client');

        $form->addElement('button', 'conditionconfigbutton', get_string('configurecondition', 'profilefield_conditional'));

        // Param 5 for conditional type contains all the conditions in JSON format.
        $form->addElement('hidden', 'param5', '', ['id' => 'profilefield_conditional_conditionconfiguration']);
        $form->setType('param5', PARAM_RAW);

        // Param 4 for conditional type determines if all hidden fields are going to be initially hidden or not.
        $form->addElement('selectyesno', 'param4', get_string('hiddeninitially', 'profilefield_conditional'));
        $form->addHelpButton('param4', 'hiddeninitially', 'profilefield_conditional');
        $form->setDefault('param4', 1); // Defaults to 'yes'.
        $form->setType('param4', PARAM_INT);
    }

    #[\Override]
    public function define_after_data(&$mform) {
        $fieldid = (int) $mform->getElementValue('id');
        $script = html_writer::script("require(['profilefield_conditional/conditionconfig'], function(conditionConfig) {
            conditionConfig.init('[name=\"param1\"]', '#profilefield_conditional_conditionconfiguration',
                '[name=\"conditionconfigbutton\"]', $fieldid);
        });");
        $mform->addElement('html', $script);
    }

    /**
     * Returns the profile fields in the system.
     *
     * @return string[] Short names of the profile fields.
     */
    private function profile_fields() {
        global $DB;
        $pfields = [];
        // Store the profile fields in an array called "names".
        if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
            foreach ($categories as $category) {
                if ($fields = $DB->get_records('user_info_field', ['categoryid' => $category->id], 'sortorder ASC')) {
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
    private function is_profilefield(string $tobechecked) {
        $tobechecked = explode('[', $tobechecked)[0];

        $profilefields = $this->profile_fields();
        if (in_array($tobechecked, $profilefields)) {
            $isprofilefield = true;
        } else {
            $isprofilefield = false;
        }
        return $isprofilefield;
    }

    #[\Override]
    public function define_validate_specific($data, $files) {
        $err = parent::define_validate_specific($data, $files);

        if (empty($err)) {
            $data->param1 = str_replace("\r", '', $data->param1);
            $options = explode("\n", $data->param1);

            if (empty($data->param5)) {
                $err['conditionconfigbutton'] = get_string('emptycondition', 'profilefield_conditional');
            } else {
                $conditions = json_decode($data->param5);
                $conditionoptions = [];
                foreach ($conditions as $condition) {
                    $conditionoptions[] = $condition->option;
                }
                if (array_diff($options, $conditionoptions)) {
                    $err['conditionconfigbutton'] = get_string('optionconditionmismatch', 'profilefield_conditional');
                }

                foreach ($conditions as $condition) {
                    foreach (['requiredfields', 'hiddenfields', 'hiddenclearedfields'] as $property) {
                        foreach ($condition->$property as $field) {
                            if (!$this->is_profilefield($field)) {
                                $err['conditionconfigbutton'] = get_string('notaprofilefield', 'profilefield_conditional');
                                break 2;
                            }
                        }
                    }
                    if (
                        array_intersect($condition->requiredfields, $condition->hiddenfields) ||
                        array_intersect($condition->requiredfields, $condition->hiddenclearedfields)
                    ) {
                        $err['conditionconfigbutton'] = get_string('hiddenrequired', 'profilefield_conditional');
                    }
                }
            }
        }
        return $err;
    }
}
