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
    /** @var array Array of fields that should be cleared for each option */
    protected array $clearedset = [];

    /** @var array Array of fields that should be hidden for each option */
    public array $disabledset = [];

    /** @var array Array of fields that should be required for each option */
    public array $requiredset = [];

    public function __construct($fieldid = 0, $userid = 0, $fielddata = null) {
        // First, call parent constructor.
        parent::__construct($fieldid, $userid, $fielddata);

        // Param 5 for the conditional type is the conditions.
        if (isset($this->field->param5)) {
            $conditions = json_decode($this->field->param5);
        } else {
            $conditions = [];
        }

        foreach ($conditions as $condition) {
            $this->disabledset[$condition->option] = array_unique(array_merge(
                $condition->hiddenfields ?? [],
                $condition->hiddenclearedfields ?? [],
            ));
            $this->clearedset[$condition->option] = $condition->hiddenclearedfields ?? [];
            $this->requiredset[$condition->option] = $condition->requiredfields ?? [];
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param MoodleQuickForm $mform Moodle form instance
     */
    #[\Override]
    public function edit_field_add($mform) {
        global $PAGE;

        $mform->addElement(
            'select',
            $this->inputname,
            format_string($this->field->name),
            $this->options,
            [
                'data-conditions' => $this->field->param5,
                'data-req-html' => $mform->getReqHTML(),
            ]
        );

        // MDL-57085: The following chunk would be moved into edit_after_data if edit_after_data were being called during signup.
        if ($this->field->param4) { // The 'hide all' option is selected.
            $flatelements = array_unique(array_merge(...(array_values($this->disabledset))));
            foreach ($flatelements as $element) {
                $mform->hideIf("profile_field_{$element}", $this->inputname, 'eq', '');
            }
        }
        foreach (array_keys($this->options) as $option) {
            if (!empty($this->disabledset[$option])) {
                foreach ($this->disabledset[$option] as $element) {
                    $mform->hideIf("profile_field_{$element}", $this->inputname, 'eq', $option);

                    // Remove from the "required" list in case it is defined as required.
                    // This takes care of the elements that are previously defined.
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

        // MDL-57085: The following line would be moved into edit_after_data if edit_after_data were being called during signup.
        $PAGE->requires->js_call_amd(
            'profilefield_conditional/conditions',
            'apply',
            [$this->field->shortname]
        );

        // MDL-57085: The following lines were not required if edit_after_data were being called during signup.
        // This is for the future fields that are defined as required in their settings.
        MoodleQuickForm::registerRule('required', null, 'profilefield_conditional\rule_required');
        MoodleQuickForm::registerRule('profilefield_conditional_rule', null, 'profilefield_conditional\rule_required_remove');
        $mform->addRule(
            $this->inputname,
            get_string('extradata', 'profilefield_conditional'),
            'profilefield_conditional_rule',
            [&$mform, $this]
        );
    }

    #[\Override]
    public function edit_after_data($mform) {
        // It's OK that edit_after_data is not called during signup.
        // Removing fields is only needed when the conditional field already has a value, and it's hidden or locked.
        // This is not the case for the signup form.
        if (!$this->is_editable() || ($this->is_locked() && !has_capability('moodle/user:update', context_system::instance()))) {
            if (!empty($this->disabledset[$this->data])) {
                foreach ($this->disabledset[$this->data] as $element) {
                    if ($mform->elementExists("profile_field_{$element}")) {
                        $mform->removeElement("profile_field_{$element}");
                    }
                }
            }
        }

        return parent::edit_after_data($mform);
    }

    #[\Override]
    public function edit_save_data($usernew) {
        global $DB;

        parent::edit_save_data($usernew);

        if (isset($usernew->{$this->inputname})) {
            if (!empty($this->clearedset[$usernew->{$this->inputname}])) {
                $clearedkeys = array_flip($this->clearedset[$usernew->{$this->inputname}]);
                $profilefields = profile_get_user_fields_with_data(0);
                foreach ($profilefields as $profilefield) {
                    $shortname = str_replace('profile_field_', '', $profilefield->inputname);
                    if (isset($clearedkeys[$shortname])) {
                        $DB->delete_records('user_info_data', ['fieldid' => $profilefield->fieldid, 'userid' => $this->userid]);
                        unset($usernew->{$profilefield->inputname});
                    }
                }
            }
        }
    }

    #[\Override]
    public function edit_validate_field($usernew) {
        $errors = [];

        $selected = $usernew->{$this->inputname} ?? null;
        if (
            !is_scalar($selected)
            || empty($this->requiredset[$selected])
            || count((array) $usernew) <= 2  // If not, we have an incomplete user object, and a validation check is not possible.
        ) {
            return $errors;
        }

        $userid = (int) ($usernew->id ?? 0);
        $dependentfields = null;
        $unfillable = [];
        foreach ($this->requiredset[$selected] as $requiredfield) {
            // A field that is also hidden for the selected option is never required.
            if (in_array($requiredfield, $this->disabledset[$selected] ?? [], true)) {
                continue;
            }

            // The fields are looked up lazily, so that no queries are made when there is nothing to check.
            $dependentfields ??= $this->get_dependent_fields($userid);
            if (!isset($dependentfields[$requiredfield])) {
                continue;
            }
            $dependent = $dependentfields[$requiredfield];
            $inputname = 'profile_field_' . $requiredfield;

            if ($this->is_fillable($dependent)) {
                if (!$this->has_valid_value($dependent, $usernew->{$inputname} ?? null)) {
                    $data = new stdClass();
                    $data->field1 = format_string($this->field->name);
                    $data->value1 = $this->options[$selected] ?? $selected;
                    $data->field2 = $requiredfield;
                    $errors[$inputname] = get_string('requiredbycondition1', 'profilefield_conditional', $data);
                }
            } else if (!$this->has_valid_value($dependent, $this->get_stored_value($dependent, $userid))) {
                // Whatever was submitted for a field the user cannot fill in is not trusted. Only the stored value counts.
                $unfillable[] = format_string($dependent->field->name);
            }
        }

        if ($unfillable) {
            // The inputs are not displayed, so the error can only be reported on the conditional field.
            $data = new stdClass();
            $data->field1 = format_string($this->field->name);
            $data->value1 = $this->options[$selected] ?? $selected;
            $data->field2 = implode('", "', $unfillable);
            $errors[$this->inputname] = get_string('requiredbycondition2', 'profilefield_conditional', $data);
        }

        return $errors;
    }

    /**
     * Returns the profile fields of the given user (or of the signup form for 0), indexed by shortname.
     *
     * @param int $userid
     * @return profile_field_base[]
     */
    protected function get_dependent_fields(int $userid): array {
        $fields = [];
        foreach (profile_get_user_fields_with_data($userid) as $field) {
            $fields[$field->field->shortname] = $field;
        }
        return $fields;
    }

    /**
     * Whether the current user is given an editable element for the field in the form being validated.
     *
     * Errors for fields that are not fillable cannot be attached to their (absent or frozen) elements.
     *
     * @param profile_field_base $field
     * @return bool
     */
    protected function is_fillable(profile_field_base $field): bool {
        if (empty($field->userid)) {
            // Signup form: only the fields flagged for signup are displayed.
            return $field->is_signup_field() && $field->is_editable();
        }
        // Profile edit forms: locked fields are frozen for users without the update capability.
        return $field->is_editable()
            && (!$field->is_locked() || has_capability('moodle/user:update', context_system::instance()));
    }

    /**
     * Returns the value saved in the database for the user, ignoring the default value of the field.
     *
     * @param profile_field_base $field
     * @param int $userid
     * @return string|null Null when nothing is saved, or when there is no user yet.
     */
    protected function get_stored_value(profile_field_base $field, int $userid): ?string {
        global $DB;

        if ($userid <= 0) {
            return null;
        }
        $value = $DB->get_field('user_info_data', 'data', ['userid' => $userid, 'fieldid' => $field->field->id]);
        return $value === false ? null : $value;
    }

    /**
     * Checks that a value, as submitted or saved, satisfies the requirement of a field.
     *
     * A missing, null, empty and whitespace-only value is not valid, but "0" is. The value of a menu must be the key of one of
     * its options, submitted as a string or an integer. The editor of a textarea field submits an array holding the text and
     * its format. Any other field type submits a scalar.
     *
     * @param profile_field_base $field
     * @param mixed $value
     * @return bool
     */
    protected function has_valid_value(profile_field_base $field, $value): bool {
        if ($field instanceof profile_field_menu) {
            return (is_string($value) || is_int($value))
                && trim((string) $value) !== ''
                && array_key_exists((string) $value, $field->options);
        }
        if (is_array($value)) {
            if (!($field instanceof profile_field_textarea)) {
                return false;
            }
            $formats = array_map('strval', [FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_MARKDOWN]);
            return is_string($value['text'] ?? null)
                && trim($value['text']) !== ''
                && is_scalar($value['format'] ?? null)
                && in_array((string) $value['format'], $formats, true);
        }
        return is_scalar($value) && !is_bool($value) && trim((string) $value) !== '';
    }
}
