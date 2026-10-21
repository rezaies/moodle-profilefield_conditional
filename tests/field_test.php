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

namespace profilefield_conditional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/field/conditional/field.class.php');

/**
 * Tests for the server-side validation of the conditional profile field.
 *
 * @package    profilefield_conditional
 * @copyright  2014 Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\profile_field_conditional::class)]
final class field_test extends \advanced_testcase {
    /** @var string Name of the form input of the conditional (controller) field. */
    private const CONTROLLER = 'profile_field_profession_adc_test';
    /** @var string Name of the form input of the first dependent field, a menu. */
    private const STAGE = 'profile_field_adc_exam_stage';
    /** @var string Name of the form input of the second dependent field, a menu (a text field in the "text" variant). */
    private const VALIDITY = 'profile_field_written_validity_status';

    /** @var string[] Options of the stage menu. */
    private const STAGES = ['First Written', 'Written Retake', 'Practical Preparation'];
    /** @var string[] Options of the validity menu. */
    private const VALIDITIES = ['Not Applicable', 'Valid', 'Expiring Soon', 'Expired'];

    /** @var array Submitted values that are not an option of the validity menu, or not even a scalar. */
    private const MALFORMED_MENU_VALUES = [
        'Fabricated', 'valid', 'Valid ', ' Valid', '0', ['Valid'], ['text' => 'Valid'],
        ['text' => 'Valid', 'format' => FORMAT_HTML], [['Valid']], [], true, 1.5,
    ];

    /**
     * Creates the profession field, its two dependent fields and an unrelated unique field.
     *
     * The "adc" variant has two menus, like the real configuration. The "text" and "textarea" variants have a menu with
     * numeric options (including "0") and a text or textarea field, to cover values that the menus of the real configuration
     * cannot have.
     *
     * @param string $variant One of "adc", "text" or "textarea".
     * @param array $dependentsettings Additional settings for both dependent fields.
     */
    private function create_fields(string $variant = 'adc', array $dependentsettings = []): void {
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'menu',
            'shortname' => 'adc_exam_stage',
            'name' => 'ADC exam stage',
            'param1' => $variant === 'adc' ? implode("\n", self::STAGES) : "0\n1\n2",
            'signup' => 1,
        ] + $dependentsettings);
        $generator->create_custom_profile_field(
            [
                'datatype' => $variant === 'adc' ? 'menu' : $variant,
                'shortname' => 'written_validity_status',
                'name' => 'Written validity status',
                'signup' => 1,
            ] + ($variant === 'adc' ? ['param1' => implode("\n", self::VALIDITIES)] : []) + $dependentsettings
        );
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'plain',
            'name' => 'Plain field',
            'signup' => 1,
            'forceunique' => 1,
        ]);

        $dependents = ['adc_exam_stage', 'written_validity_status'];
        $generator->create_custom_profile_field([
            'datatype' => 'conditional',
            'shortname' => 'profession_adc_test',
            'name' => 'Profession',
            'param1' => "Dentist\nGeneral Practitioner\nOthers",
            'param4' => 0,
            'param5' => json_encode([
                ['option' => 'Dentist', 'requiredfields' => $dependents],
                ['option' => 'General Practitioner', 'hiddenfields' => $dependents],
                ['option' => 'Others', 'hiddenfields' => $dependents],
            ]),
            'signup' => 1,
        ]);
    }

    /**
     * Builds the user object that is passed to the validation, like the signup and edit forms do.
     *
     * @param int $userid 0 for the signup form.
     * @param array $profiledata Submitted profile field values. A missing key means the field was not submitted.
     * @return stdClass
     */
    private function get_usernew(int $userid, array $profiledata): stdClass {
        return (object) ([
            'id' => $userid,
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'testuser@example.com',
        ] + $profiledata);
    }

    /**
     * Builds the user object of a dentist, which requires both of the dependent fields.
     *
     * @param int $userid 0 for the signup form.
     * @param array $profiledata Submitted values of the dependent fields. A missing key means the field was not submitted.
     * @return stdClass
     */
    private function get_dentist(int $userid, array $profiledata = []): stdClass {
        return $this->get_usernew($userid, [self::CONTROLLER => 'Dentist'] + $profiledata);
    }

    /**
     * Runs the validation of all the profile fields, as the signup and the profile forms do.
     *
     * @param stdClass $usernew
     * @return array
     */
    private function validate(stdClass $usernew): array {
        return profile_validation($usernew, []);
    }

    /**
     * Runs the validation of the conditional field only.
     *
     * @param stdClass $usernew
     * @return array
     */
    private function validate_directly(stdClass $usernew): array {
        foreach (profile_get_user_fields_with_data((int) $usernew->id) as $field) {
            if ($field instanceof \profile_field_conditional) {
                return $field->edit_validate_field($usernew);
            }
        }
        $this->fail('The conditional field was not found.');
    }

    /**
     * Sets the current user and returns the id the validated object has in that context.
     *
     * @param bool $loggedin False for the signup form, true for the profile edit form.
     * @return int
     */
    private function prepare_context(bool $loggedin): int {
        $this->resetAfterTest();
        if ($loggedin) {
            $user = $this->getDataGenerator()->create_user();
            $this->setUser($user);
            return (int) $user->id;
        }
        $this->setUser(0);
        return 0;
    }

    /**
     * Saves (or replaces) a value for a user as it would be stored by a profile edit, without any validation.
     *
     * @param int $userid
     * @param string $shortname
     * @param string $value
     */
    private function store_value(int $userid, string $shortname, string $value): void {
        global $DB;

        $conditions = [
            'userid' => $userid,
            'fieldid' => $DB->get_field('user_info_field', 'id', ['shortname' => $shortname], MUST_EXIST),
        ];
        $DB->delete_records('user_info_data', $conditions);
        $DB->insert_record('user_info_data', $conditions + ['data' => $value, 'dataformat' => 0]);
    }

    /**
     * The contexts in which the validation must work.
     *
     * @return array
     */
    public static function context_provider(): array {
        return [
            'signup' => [false],
            'edit profile' => [true],
        ];
    }

    // Validation through profile_validation(), with the two menus of the real configuration.

    #[DataProvider('context_provider')]
    public function test_required_fields_valid(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        // Every combination of options is accepted.
        foreach (self::STAGES as $stage) {
            foreach (self::VALIDITIES as $validity) {
                $usernew = $this->get_dentist($userid, [self::STAGE => $stage, self::VALIDITY => $validity]);
                $this->assertSame([], $this->validate($usernew), "$stage / $validity");
            }
        }
    }

    #[DataProvider('context_provider')]
    public function test_blank_fields_are_rejected(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => 'Written Retake', self::VALIDITY => '']);
        $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)));

        $usernew = $this->get_dentist($userid, [self::STAGE => '', self::VALIDITY => 'Valid']);
        $this->assertSame([self::STAGE], array_keys($this->validate($usernew)));
    }

    #[DataProvider('context_provider')]
    public function test_omitted_fields_are_rejected(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        // Both keys missing.
        $errors = $this->validate($this->get_dentist($userid));
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($errors));

        // One key missing.
        $usernew = $this->get_dentist($userid, [self::STAGE => 'Written Retake']);
        $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)));
        $usernew = $this->get_dentist($userid, [self::VALIDITY => 'Valid']);
        $this->assertSame([self::STAGE], array_keys($this->validate($usernew)));

        // Null counts as missing.
        $usernew = $this->get_dentist($userid, [self::STAGE => null, self::VALIDITY => null]);
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($this->validate($usernew)));
    }

    #[DataProvider('context_provider')]
    public function test_whitespace_only_is_rejected(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => ' ', self::VALIDITY => " \t\n"]);
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($this->validate($usernew)));
    }

    #[DataProvider('context_provider')]
    public function test_fabricated_menu_options_are_rejected(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        foreach (self::MALFORMED_MENU_VALUES as $fabricated) {
            $usernew = $this->get_dentist($userid, [self::STAGE => 'Written Retake', self::VALIDITY => $fabricated]);
            $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)), json_encode($fabricated));

            $usernew = $this->get_dentist($userid, [self::STAGE => $fabricated, self::VALIDITY => 'Valid']);
            $this->assertSame([self::STAGE], array_keys($this->validate($usernew)), json_encode($fabricated));
        }
    }

    #[DataProvider('context_provider')]
    public function test_hidden_fields_remain_optional(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        foreach (['General Practitioner', 'Others'] as $profession) {
            // Omitted or blank: neither is a conditional validation error.
            $this->assertSame([], $this->validate($this->get_usernew($userid, [self::CONTROLLER => $profession])));
            $usernew = $this->get_usernew($userid, [self::CONTROLLER => $profession, self::STAGE => '', self::VALIDITY => '']);
            $this->assertSame([], $this->validate($usernew));
        }
    }

    public function test_no_condition_selected(): void {
        $this->create_fields();
        $this->prepare_context(false);

        $this->assertSame([], $this->validate($this->get_usernew(0, [])));
        $this->assertSame([], $this->validate($this->get_usernew(0, [self::CONTROLLER => ''])));
        $this->assertSame([], $this->validate($this->get_usernew(0, [self::CONTROLLER => 'Unknown'])));
        $this->assertSame([], $this->validate($this->get_usernew(0, [self::CONTROLLER => ['Dentist']])));
    }

    /**
     * A field that is also hidden for the selected option is never required.
     */
    public function test_field_hidden_and_required_is_optional(): void {
        global $DB;

        $this->create_fields();
        $this->prepare_context(false);

        $record = $DB->get_record('user_info_field', ['shortname' => 'profession_adc_test'], '*', MUST_EXIST);
        $conditions = json_decode($record->param5);
        $conditions[0]->hiddenfields = ['adc_exam_stage'];
        $DB->set_field('user_info_field', 'param5', json_encode($conditions), ['id' => $record->id]);

        $errors = $this->validate($this->get_dentist(0));
        $this->assertSame([self::VALIDITY], array_keys($errors));
    }

    /**
     * A dependent field that no longer exists cannot be validated.
     */
    public function test_missing_dependent_field_is_ignored(): void {
        global $DB;

        $this->create_fields();
        $DB->delete_records('user_info_field', ['shortname' => 'written_validity_status']);
        $this->prepare_context(false);

        $errors = $this->validate($this->get_dentist(0));
        $this->assertSame([self::STAGE], array_keys($errors));
    }

    /**
     * An object that cannot be a complete form submission is not validated.
     */
    public function test_incomplete_user_object(): void {
        $this->create_fields();
        $this->prepare_context(false);

        $usernew = (object) ['id' => 0, self::CONTROLLER => 'Dentist'];
        $this->assertSame([], $this->validate($usernew));
    }

    // Values that menus cannot have: "0" and the array submitted by editors.

    #[DataProvider('context_provider')]
    public function test_zero_is_a_valid_value(bool $loggedin): void {
        $this->create_fields('text');
        $userid = $this->prepare_context($loggedin);

        // "0" is both a valid menu option and a valid text value.
        $usernew = $this->get_dentist($userid, [self::STAGE => '0', self::VALIDITY => '0']);
        $this->assertSame([], $this->validate($usernew));
        $this->assertSame([], $this->validate_directly($usernew));
    }

    #[DataProvider('context_provider')]
    public function test_text_field_takes_scalar_values(bool $loggedin): void {
        $this->create_fields('text');
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => '1', self::VALIDITY => 'Valid']);
        $this->assertSame([], $this->validate($usernew));
        $this->assertSame([], $this->validate_directly($usernew));

        // The array of the editors is not what a text field submits.
        foreach ([['text' => 'Valid', 'format' => FORMAT_HTML], ['text' => 'Valid'], ['Valid'], []] as $malformed) {
            $usernew = $this->get_dentist($userid, [self::STAGE => '1', self::VALIDITY => $malformed]);
            $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)), json_encode($malformed));
            $this->assertSame([self::VALIDITY], array_keys($this->validate_directly($usernew)), json_encode($malformed));
        }

        foreach (['', '  ', null] as $empty) {
            $usernew = $this->get_dentist($userid, [self::STAGE => '1', self::VALIDITY => $empty]);
            $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)), json_encode($empty));
        }
    }

    #[DataProvider('context_provider')]
    public function test_textarea_field_takes_editor_values(bool $loggedin): void {
        $this->create_fields('textarea');
        $userid = $this->prepare_context($loggedin);

        $valid = [
            ['text' => '<p>Valid</p>', 'format' => FORMAT_HTML],
            ['text' => 'Valid', 'format' => (string) FORMAT_HTML, 'itemid' => 123],
            ['text' => '0', 'format' => FORMAT_MARKDOWN],
            'Valid',
            '0',
        ];
        foreach ($valid as $value) {
            $usernew = $this->get_dentist($userid, [self::STAGE => '1', self::VALIDITY => $value]);
            $this->assertSame([], $this->validate($usernew), json_encode($value));
            $this->assertSame([], $this->validate_directly($usernew), json_encode($value));
        }

        $malformed = [
            ['text' => '', 'format' => FORMAT_HTML],
            ['text' => " \n ", 'format' => FORMAT_HTML],
            ['text' => 'Valid'],
            ['format' => FORMAT_HTML],
            ['text' => 'Valid', 'format' => 99],
            ['text' => 'Valid', 'format' => 'html'],
            ['text' => 'Valid', 'format' => [FORMAT_HTML]],
            ['text' => ['Valid'], 'format' => FORMAT_HTML],
            ['text' => null, 'format' => FORMAT_HTML],
            ['Valid'],
            [],
            '',
            ' ',
        ];
        foreach ($malformed as $value) {
            $usernew = $this->get_dentist($userid, [self::STAGE => '1', self::VALIDITY => $value]);
            $this->assertSame([self::VALIDITY], array_keys($this->validate($usernew)), json_encode($value));
            $this->assertSame([self::VALIDITY], array_keys($this->validate_directly($usernew)), json_encode($value));
        }
    }

    /**
     * A menu with numeric options takes the key of the option as a string or an integer, but not as a boolean or a float.
     */
    #[DataProvider('context_provider')]
    public function test_numeric_menu_options(bool $loggedin): void {
        $this->create_fields('text');
        $userid = $this->prepare_context($loggedin);

        foreach (['0', 0, '1', 1, '2'] as $valid) {
            $usernew = $this->get_dentist($userid, [self::STAGE => $valid, self::VALIDITY => 'Valid']);
            $this->assertSame([], $this->validate($usernew), var_export($valid, true));
            $this->assertSame([], $this->validate_directly($usernew), var_export($valid, true));
        }

        foreach ([true, false, 1.0, 0.0, 1.5, '1.0', '01', '3', ['1'], ['text' => '1']] as $invalid) {
            $usernew = $this->get_dentist($userid, [self::STAGE => $invalid, self::VALIDITY => 'Valid']);
            $this->assertSame([self::STAGE], array_keys($this->validate($usernew)), var_export($invalid, true));
            $this->assertSame([self::STAGE], array_keys($this->validate_directly($usernew)), var_export($invalid, true));
        }
    }

    // Fields that the user cannot fill in: the submitted data is not trusted, the saved data is.

    /**
     * A field that is not on the signup form never has a saved value, so a value that is submitted for it is forged.
     */
    public function test_forged_value_for_field_not_on_signup_form(): void {
        global $DB;

        $this->create_fields();
        $DB->set_field('user_info_field', 'signup', 0, ['shortname' => 'written_validity_status']);
        $this->prepare_context(false);

        $usernew = $this->get_dentist(0, [self::STAGE => 'Written Retake']);
        $errors = $this->validate($usernew);
        $this->assertSame([self::CONTROLLER], array_keys($errors));
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);

        // Supplying a valid value does not help.
        $usernew->{self::VALIDITY} = 'Valid';
        $errors = $this->validate($usernew);
        $this->assertSame([self::CONTROLLER], array_keys($errors));
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);
        $this->assertSame($errors, $this->validate_directly($usernew));
    }

    /**
     * Several missing fields that cannot be filled in are all named in a single error.
     */
    public function test_missing_unfillable_fields_are_aggregated(): void {
        global $DB;

        $this->create_fields();
        $DB->set_field('user_info_field', 'signup', 0, ['shortname' => 'adc_exam_stage']);
        $DB->set_field('user_info_field', 'signup', 0, ['shortname' => 'written_validity_status']);
        $this->prepare_context(false);

        $usernew = $this->get_dentist(0, [self::STAGE => 'First Written', self::VALIDITY => 'Valid']);
        $errors = $this->validate($usernew);
        $this->assertSame([self::CONTROLLER], array_keys($errors));
        $this->assertStringContainsString('ADC exam stage', $errors[self::CONTROLLER]);
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);

        // The fields that can be filled in are still reported on their own inputs.
        $DB->set_field('user_info_field', 'signup', 1, ['shortname' => 'adc_exam_stage']);
        $errors = $this->validate($this->get_dentist(0));
        $this->assertEqualsCanonicalizing([self::STAGE, self::CONTROLLER], array_keys($errors));
        $this->assertStringNotContainsString('ADC exam stage', $errors[self::CONTROLLER]);
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);
    }

    /**
     * Locked fields are frozen for users, so the value submitted for them is ignored and the saved one is used.
     */
    public function test_locked_fields_use_the_saved_values(): void {
        global $DB;

        $this->resetAfterTest();
        $this->create_fields('adc', ['locked' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $userid = (int) $user->id;

        // Nothing saved: omitting the values or forging them is rejected, naming both fields in one error.
        $forged = [self::STAGE => 'Written Retake', self::VALIDITY => 'Valid'];
        foreach ([[], $forged] as $submission) {
            $usernew = $this->get_dentist($userid, $submission);
            $errors = $this->validate($usernew);
            $this->assertSame([self::CONTROLLER], array_keys($errors));
            $this->assertStringContainsString('ADC exam stage', $errors[self::CONTROLLER]);
            $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);
            $this->assertSame($errors, $this->validate_directly($usernew));
        }

        // A default value is not a saved value.
        $DB->set_field('user_info_field', 'defaultdata', 'Valid', ['shortname' => 'written_validity_status']);
        $this->assertSame([self::CONTROLLER], array_keys($this->validate($this->get_dentist($userid, $forged))));

        // A saved blank value does not satisfy the requirement, and only the field it belongs to is named.
        $this->store_value($userid, 'adc_exam_stage', 'Written Retake');
        $this->store_value($userid, 'written_validity_status', '');
        $errors = $this->validate($this->get_dentist($userid, $forged));
        $this->assertSame([self::CONTROLLER], array_keys($errors));
        $this->assertStringNotContainsString('ADC exam stage', $errors[self::CONTROLLER]);
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);

        // A saved value that is not an option does not satisfy the requirement.
        $this->store_value($userid, 'adc_exam_stage', 'Fabricated');
        $this->store_value($userid, 'written_validity_status', 'Fabricated');
        $errors = $this->validate($this->get_dentist($userid, $forged));
        $this->assertStringContainsString('ADC exam stage', $errors[self::CONTROLLER]);
        $this->assertStringContainsString('Written validity status', $errors[self::CONTROLLER]);

        // Saved valid values are enough, whatever is submitted for the frozen inputs.
        $this->store_value($userid, 'adc_exam_stage', 'Written Retake');
        $this->store_value($userid, 'written_validity_status', 'Expired');
        $this->assertSame([], $this->validate($this->get_dentist($userid)));
        $this->assertSame([], $this->validate($this->get_dentist($userid, $forged)));
        $garbage = [self::STAGE => '', self::VALIDITY => 'Fabricated'];
        $this->assertSame([], $this->validate($this->get_dentist($userid, $garbage)));
    }

    /**
     * Locked fields can be changed by those who can update users, so these have to submit valid values.
     */
    public function test_locked_fields_are_fillable_by_administrators(): void {
        $this->resetAfterTest();
        $this->create_fields('adc', ['locked' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $userid = (int) $user->id;

        $errors = $this->validate($this->get_dentist($userid));
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($errors));

        $usernew = $this->get_dentist($userid, [self::STAGE => 'First Written', self::VALIDITY => 'Valid']);
        $this->assertSame([], $this->validate($usernew));
    }

    // Direct calls of the validator of the conditional field.

    #[DataProvider('context_provider')]
    public function test_validator_valid_values(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => 'First Written', self::VALIDITY => 'Valid']);
        $this->assertSame([], $this->validate_directly($usernew));
    }

    #[DataProvider('context_provider')]
    public function test_validator_empty_values(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => '', self::VALIDITY => '  ']);
        $errors = $this->validate_directly($usernew);
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($errors));
        $this->assertStringContainsString('Profession', $errors[self::STAGE]);
        $this->assertStringContainsString('Dentist', $errors[self::STAGE]);
    }

    #[DataProvider('context_provider')]
    public function test_validator_missing_values(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $errors = $this->validate_directly($this->get_dentist($userid));
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($errors));

        $usernew = $this->get_dentist($userid, [self::VALIDITY => 'Expiring Soon']);
        $this->assertSame([self::STAGE], array_keys($this->validate_directly($usernew)));
    }

    /**
     * Only the scalar key of an option is valid for a menu, and malformed values do not cause errors other than validation ones.
     */
    #[DataProvider('context_provider')]
    public function test_validator_menu_values(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => 'First Written', self::VALIDITY => 'Valid']);
        $this->assertSame([], $this->validate_directly($usernew));

        foreach (self::MALFORMED_MENU_VALUES as $malformed) {
            $usernew = $this->get_dentist($userid, [self::STAGE => 'First Written', self::VALIDITY => $malformed]);
            $this->assertSame([self::VALIDITY], array_keys($this->validate_directly($usernew)), json_encode($malformed));
            $usernew = $this->get_dentist($userid, [self::STAGE => $malformed, self::VALIDITY => 'Valid']);
            $this->assertSame([self::STAGE], array_keys($this->validate_directly($usernew)), json_encode($malformed));
        }
    }

    #[DataProvider('context_provider')]
    public function test_validator_tampered_values(bool $loggedin): void {
        $this->create_fields();
        $userid = $this->prepare_context($loggedin);

        $usernew = $this->get_dentist($userid, [self::STAGE => 'Fabricated', self::VALIDITY => ['Valid']]);
        $this->assertEqualsCanonicalizing([self::STAGE, self::VALIDITY], array_keys($this->validate_directly($usernew)));

        // Hidden for the other professions: tampering with the inputs that are not required is of no consequence.
        $usernew = $this->get_usernew($userid, [self::CONTROLLER => 'Others', self::STAGE => 'Fabricated']);
        $this->assertSame([], $this->validate_directly($usernew));
    }

    // Integration with the other validations of the profile fields.

    public function test_profile_validation_integration(): void {
        $this->create_fields();
        $userid = $this->prepare_context(true);
        $other = $this->getDataGenerator()->create_user();
        profile_save_data((object) ['id' => $other->id, 'profile_field_plain' => 'taken']);

        $usernew = $this->get_dentist($userid, ['profile_field_plain' => 'taken']);
        $errors = $this->validate($usernew);
        $this->assertEqualsCanonicalizing(['profile_field_plain', self::STAGE, self::VALIDITY], array_keys($errors));
        $this->assertStringContainsString('Profession', $errors[self::STAGE]);
        $this->assertStringContainsString('Dentist', $errors[self::STAGE]);
    }

    /**
     * Fields without any condition keep their behaviour: omitting them is fine, duplicates are still caught.
     */
    public function test_regular_fields_are_unaffected(): void {
        $this->create_fields();
        $userid = $this->prepare_context(true);
        $other = $this->getDataGenerator()->create_user();
        profile_save_data((object) ['id' => $other->id, 'profile_field_plain' => 'taken']);

        $this->assertSame([], $this->validate($this->get_usernew($userid, [self::CONTROLLER => 'Others'])));
        $this->assertSame([], $this->validate($this->get_usernew($userid, [
            self::CONTROLLER => 'Others',
            'profile_field_plain' => 'unique',
        ])));
        $errors = $this->validate($this->get_usernew($userid, [
            self::CONTROLLER => 'Others',
            'profile_field_plain' => 'taken',
        ]));
        $this->assertSame(['profile_field_plain'], array_keys($errors));
    }
}
