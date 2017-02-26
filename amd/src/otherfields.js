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
 * Module to get other profile fields.
 *
 * @package    profilefield_conditional
 * @copyright  2016 Shamim Rezaie (http://foodle.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, ajax) {
    var localCache = [];

    return /** @alias module:profilefield_conditional/otherfields */ {

        /**
         * Return a promise object that will be resolved into a string eventually (maybe immediately).
         *
         * @method getFields
         * @param {Number} fieldid The field id
         * @return [] {Promise}
         */

        getFields: function(fieldid) {

            var deferred = $.Deferred();

            if (typeof localCache[fieldid] === 'undefined') {
                ajax.call([{
                    methodname: 'profilefield_conditional_get_other_fields',
                    args: {fieldid: fieldid},
                    done: function(fieldinfo) {
                        localCache[fieldid] = fieldinfo;
                        deferred.resolve(fieldinfo);
                    },
                    fail: (deferred.reject)
                }]);
            } else {
                deferred.resolve(localCache[fieldid]);
            }

            return deferred.promise();
        }
    };
});
