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
 * Strings for component 'profilefield_conditional', language 'en'
 *
 * @package   profilefield_conditional
 * @copyright 2014 Shamim Rezaie {@link http://foodle.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['apply'] = 'OK';
$string['conditionalhelp'] = 'How to enter the options?';
$string['conditionalhelp_help'] = 'Please specify the menu options by entering one option per line. You can then specify which fields should be hidden or required when each option is selected.';
$string['configurecondition'] = 'Configure conditions';
$string['emptycondition'] = 'You need to configure option conditions.';
$string['extradata'] = 'The submitted data contains values for fields that should be left blank based on the selected option here.';
$string['hidden'] = 'Hidden';
$string['hiddenandcleared'] = 'Hidden and cleared';
$string['hiddeninitially'] = 'Hide initially';
$string['hiddeninitially_help'] = '* Yes - None of the fields that may hide in some conditions is displayed initially. Some new fields may appear after users make a choice.
* No - All fields will be displayed to users initially. When they select a value for the conditional field, some of the existing fields hide';
$string['hiddenrequired'] = 'There is at least one field that has been set to hidden whilst also being required!';
$string['menuoption'] = 'Menu option';
$string['notaprofilefield'] = 'Some fields that are referred to in condition configuration do not exist. Please verify conditions. Remember to press "OK" if the configuration looks fine to you.';
$string['notice'] = '<p><strong>Warning:</strong> Multiple conditional fields may cause conflicts. Hidden required fields may prevent the form from being submitted. To avoid this, please ensure that users do not encounter a situation where a field is required by one conditional field and hidden by another at the same time.</p><p><strong>Note:</strong> "Hidden and cleared" is an experimental feature. It has not been tested with all field types, so please check whether it works with the field type you intend to use.</p>';
$string['optionconditionmismatch'] = 'You have made some modifications to the menu options after the last time you configured option conditions. Please verify that your conditions are up to date.';
$string['pluginname'] = 'Conditional field';
$string['privacy:metadata:profilefield_conditional:data'] = 'Conditional user profile field user data';
$string['privacy:metadata:profilefield_conditional:dataformat'] = 'The format of the conditional user profile field user data';
$string['privacy:metadata:profilefield_conditional:fieldid'] = 'The ID of the profile field';
$string['privacy:metadata:profilefield_conditional:tableexplanation'] = 'Additional profile data';
$string['privacy:metadata:profilefield_conditional:userid'] = 'The ID of the user whose data is stored by the conditional user profile field';
$string['required'] = 'Required';
$string['requiredbycondition1'] = 'This field cannot be left empty when the "{$a->field1}" field is set to "{$a->value1}".';
$string['requiredbycondition2'] = 'Please fill the "{$a->field2}" field. It cannot be left empty based on the value you selected here.';
