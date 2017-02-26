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
 * This is the external API for this profile field.
 *
 * @package    profilefield_conditional
 * @copyright  2016 Shamim Rezaie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_conditional;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * This is the external API for this profile field.
 *
 * @copyright  2016 Shamim Rezaie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {

    /**
     * Returns description of get_other_fields() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_other_fields_parameters() {
        $fieldid = new \external_value(PARAM_INT, 'Current field id', VALUE_OPTIONAL);
        $params = array('fieldid' => $fieldid);
        return new \external_function_parameters($params);
    }

    /**
     * Get custom profile fields.
     *
     * @param int $fieldid The field ID
     * @return array Field records
     */
    public static function get_other_fields($fieldid) {
        global $DB;
        $params = self::validate_parameters(self::get_other_fields_parameters(),
            array(
                'fieldid' => $fieldid,
            )
        );
        $context = \context_system::instance();
        self::validate_context($context);

        $fields = $DB->get_records_select('user_info_field', 'id NOT IN (?)', array($fieldid), '', 'id, shortname, name');

        return $fields;
    }

    /**
     * Returns description of get_other_fields() result value.
     *
     * @return \external_description
     */
    public static function get_other_fields_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(array(
                'id' => new \external_value(PARAM_INT, 'Field ID'),
                'shortname' => new \external_value(PARAM_RAW, 'Field short name'),
                'name' => new \external_value(PARAM_RAW, 'Field name')
            ))
        );
    }
}
