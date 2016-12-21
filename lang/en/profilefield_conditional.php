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
$string['conditionalhelp_help'] = 'Please specifiy the menue options by entering them one per line here. you can then specify which other fields you want to be hidden or required if each option is selected.';
$string['configurecondition'] = 'Configure conditions';
$string['emptycondition'] = 'You need to configure option conditions.';
$string['extradata'] = 'The submitted data contain value for fields that should be left blank based on the selected option here.';
$string['hidden'] = 'Hidden';
$string['hiddeninitially'] = 'Hidden initially';
$string['hiddenrequired'] = 'There is at least one required field that you set to be hidden as well!';
$string['menuoption'] = 'Menu option';
$string['notaprofilefield'] = 'Some fields that are referred to in condition configuration do not exist. Please verify conditions. Remember to press "OK" if the configuration looks fine to you.';
$string['notice'] = 'Please pay more attention if you are having more than one conditional field, as they may interfere each other. Please check that you don\'t fall into a situation where a field is required and hidden at the same time.';
$string['optionconditionmismatch'] = 'You have made some modifications to the menu options after the last time you configured option conditions. Please verify that your conditions are up to date.';
$string['pluginname'] = 'Conditional field';
$string['required'] = 'Required';
$string['requiredbycondition1'] = 'This field cannot be left empty when {$a->field1} is {$a->value1}';
$string['requiredbycondition2'] = 'Please fill the {$a->field2} field. It cannot be left empty based on the value you selected here.';