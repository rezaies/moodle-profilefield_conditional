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
 * Conditional profile field webservice functions.
 *
 * @package    profilefield_conditional
 * @copyright  2016 Shamim Rezaie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'profilefield_conditional_get_other_fields' => array(
        'classname'    => 'profilefield_conditional\external',
        'methodname'   => 'get_other_fields',
        'classpath'    => '',
        'description'  => 'Load the list of other custom profile fields',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
);

