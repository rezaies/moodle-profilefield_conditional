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
 * Privacy class for requesting user data.
 *
 * @package    profilefield_conditional
 * @copyright  2021 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_conditional\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy class for requesting user data.
 *
 * @copyright  2021 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        return $collection->add_database_table('user_info_data', [
            'userid' => 'privacy:metadata:profilefield_conditional:userid',
            'fieldid' => 'privacy:metadata:profilefield_conditional:fieldid',
            'data' => 'privacy:metadata:profilefield_conditional:data',
            'dataformat' => 'privacy:metadata:profilefield_conditional:dataformat',
        ], 'privacy:metadata:profilefield_conditional:tableexplanation');
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                  JOIN {context} ctx ON ctx.instanceid = uda.userid
                       AND ctx.contextlevel = :contextlevel
                 WHERE uda.userid = :userid
                       AND uif.datatype = :datatype";
        $params = [
            'userid' => $userid,
            'contextlevel' => CONTEXT_USER,
            'datatype' => 'conditional',
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $sql = "SELECT uda.userid
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif
                       ON uda.fieldid = uif.id
                 WHERE uda.userid = :userid
                       AND uif.datatype = :datatype";

        $params = [
            'userid' => $context->instanceid,
            'datatype' => 'conditional',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            // Check if the context is a user context.
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                $results = static::get_records($user->id);
                foreach ($results as $result) {
                    $data = (object) [
                        'name' => $result->name,
                        'description' => $result->description,
                        'data' => $result->data,
                    ];
                    \core_privacy\local\request\writer::with_context($context)->export_data([
                        get_string('pluginname', 'profilefield_conditional')], $data);
                }
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // Delete data only for user context.
        if ($context->contextlevel == CONTEXT_USER) {
            static::delete_data($context->instanceid);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            static::delete_data($context->instanceid);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            // Check if the context is a user context.
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                static::delete_data($context->instanceid);
            }
        }
    }

    /**
     * Delete data related to a userid.
     *
     * @param  int $userid The user ID
     */
    protected static function delete_data(int $userid): void {
        global $DB;

        $params = [
            'userid' => $userid,
            'datatype' => 'conditional',
        ];

        $DB->delete_records_select('user_info_data', "fieldid IN (
                SELECT id FROM {user_info_field} WHERE datatype = :datatype)
                AND userid = :userid", $params);
    }

    /**
     * Get records related to this plugin and user.
     *
     * @param  int $userid The user ID
     * @return array An array of records.
     */
    protected static function get_records(int $userid): array {
        global $DB;

        $sql = "SELECT *
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                 WHERE uda.userid = :userid
                       AND uif.datatype = :datatype";
        $params = [
            'userid' => $userid,
            'datatype' => 'conditional',
        ];

        return $DB->get_records_sql($sql, $params);
    }
}
