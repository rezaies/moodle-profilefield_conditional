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
 * Handles applying conditions.
 *
 * @module     profilefield_conditional/conditions
 * @copyright  2016 Shamim Rezaie <http://foodle.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    /**
     * Get the jQuet object of the row that contains provided field.
     *
     * @param {String} field The name of a field.
     * @return {jQuery} The form row that contains the field.
     */
    var getFieldRow = function(field) {
        var fieldRow = $('#fitem_id_profile_field_' + field);
        if (fieldRow.length === 0) { // Boost style.
            fieldRow = $('.fitem [name=profile_field_' + field + '], .fitem [name^=profile_field_' + field + '\\[]')
                    .closest('.fitem.row');
        }
        return fieldRow;
    };

    /**
     * Condition object.
     *
     * @param {String} fieldName The conditional field name.
     * @constructor
     */
    var Conditions = function(fieldName) {
        this.fieldName = fieldName;
        var conditionalField = $('#id_profile_field_' + fieldName);
        this.conditions = conditionalField.data('conditions') || [];
        var allElements = [];
        this.conditions.forEach(function(option) {
            option.hiddenfields.forEach(function(field) {
                if ($.inArray(field, allElements) == -1) {
                    allElements.push(field);
                }
            });
            option.requiredfields.forEach(function(field) {
                if ($.inArray(field, allElements) == -1) {
                    allElements.push(field);
                }
            });
        });

        this.allElements = allElements;
        this.reqHTML = conditionalField.data('reqHtml');

        this.initReqStars();
        this.toggleReqStars();
        conditionalField.change(this.toggleReqStars.bind(this));
    };

    Conditions.prototype.fieldName = null;
    Conditions.prototype.conditions = null;
    Conditions.prototype.allElements = null;
    /** @var {Array} options Menu options. */
    Conditions.prototype.options = null;

    /**
     * Keep record of fields that are required when the conditional field is not set and add a required rule for this case.
     */
    Conditions.prototype.initReqStars = function() {
        var initReqFields = [];

        this.allElements.forEach(function(element) {
            if ((getFieldRow(element).find('.fitemtitle .req').length !== 0) ||
                    (getFieldRow(element).find('abbr.text-danger').length !== 0)) { // Support for Boost.
                initReqFields.push(element);
            }
        });

        this.conditions.push({'option': '', 'requiredfields': initReqFields});
    };

    /**
     * Display stars for new required fields and remove stars for fields that are no longer required.
     */
    Conditions.prototype.toggleReqStars = function() {
        var selectedValue = $('#id_profile_field_' + this.fieldName).val();
        var reqHTML = this.reqHTML;
        var allElements = this.allElements;
        var requiredfields;
        var fieldRow;
        var node;
        var oldStyle = null;

        this.conditions.forEach(function(option) {
            if (option.option == selectedValue) {
                requiredfields = option.requiredfields;
                allElements.forEach(function(element) {
                    fieldRow = getFieldRow(element);
                    if (oldStyle === null) {
                        oldStyle = fieldRow.has('.float-sm-right').length;
                    }
                    if ($.inArray(element, requiredfields) != -1) {
                        node = fieldRow.find('.text-danger');

                        // The following check is required to prevent putting multiple asterisks.
                        if (node.length === 0) {
                            node = $(reqHTML).hide();
                            if (oldStyle) {
                                fieldRow.find('.float-sm-right').append(node);
                            } else {
                                fieldRow.find('.col-form-label .align-items-center').append(node);
                            }
                        }

                        node.show();
                    } else {
                        fieldRow.find('.text-danger').hide(0, function() {
                            // The following check is required to prevent removing asterisk when the selected item
                            // is quickly changed.
                            if ($.inArray(element, requiredfields) != -1) {
                                getFieldRow(element).find('.text-danger').remove();
                            }
                        });
                    }
                });
            }
        });

        this.reqHTML = reqHTML; // In case it is updated above as a result of Boost style compatibility.
    };

    return {

        /**
         * Main initialisation.
         *
         * @param {String} fieldName The conditional field name.
         * @return {Conditions} A new instance of Conditions.
         * @method init
         */
        apply: function(fieldName) {
            return new Conditions(fieldName);
        }
    };
});
