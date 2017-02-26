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
 * @package    profilefield_conditional
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
        if (fieldRow.length === 0) {    // Boost style.
            fieldRow = $('.fitem [name=profile_field_' + field + '], .fitem [name^=profile_field_' + field + '\\[]')
                    .closest('.fitem.row');
        }
        return fieldRow;
    };

    /**
     * Condition object.
     *
     * @param {String} fieldName The conditional field name.
     * @param {String} conditions Set of conditions as a json string.
     * @param {Boolean|Number} hideAll Whether to hide all fields initially or not.
     * @param {String} reqHTML The html string for specifying required fields.
     * @constructor
     */
    var Conditions = function(fieldName, conditions, hideAll, reqHTML) {
        this.fieldName = fieldName;
        this.conditions = $.parseJSON(conditions);
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
        this.hiddenElements = {};
        this.hideAll = hideAll == 1;
        this.reqHTML = reqHTML;

        this.initApplyRule();
        $('#id_profile_field_' + fieldName).change(this.applyRule.bind(this)).closest('form').submit(this.beforeSubmit.bind(this));
    };

    Conditions.prototype.fieldName = null;
    Conditions.prototype.conditions = null;
    Conditions.prototype.allElements = null;
    Conditions.prototype.hiddenElements = null;
    Conditions.prototype.hideAll = null;
    /** @var {Array} options Menu options. */
    Conditions.prototype.options = null;

    /**
     * Things need to be done prior to form submit: Remove hidden form elements from form.
     *
     * @param {Object} event
     */
    Conditions.prototype.beforeSubmit = function(event) {
        var temp;
        if (!event.isDefaultPrevented()) {
            for (var field in this.hiddenElements) {
                if (!this.hiddenElements.hasOwnProperty(field)) {
                    continue;
                }
                temp = getFieldRow(field).html();
                if (temp != '') {
                    this.hiddenElements[field] = temp;
                    getFieldRow(field).html('');
                }
            }
        }
    };

    /**
     * Keep record of fields that are required when the conditional field is not set and add a required rule for this case.
     */
    Conditions.prototype.initReqStars = function() {
        var initReqFields = [];
        var initHiddenFields = [];

        this.allElements.forEach(function(element) {
            if ((getFieldRow(element).find('.fitemtitle .req').length !== 0) ||
                    (getFieldRow(element).find('abbr.text-danger').length !== 0)) {     // Support for Boost.
                initReqFields.push(element);
            }
        });

        if (this.hideAll) {
            this.conditions.forEach(function(value) {
                value.hiddenfields.forEach(function(field) {
                    initHiddenFields.push(field);
                });
            });
        }

        this.conditions.push({'option': '', 'hiddenfields': initHiddenFields, 'requiredfields': initReqFields});
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
        var boostStyle = null;

        this.conditions.forEach(function(option) {
            if (option.option == selectedValue) {
                requiredfields = option.requiredfields;
                allElements.forEach(function(element) {
                    fieldRow = getFieldRow(element);
                    if (boostStyle === null) {
                        boostStyle = fieldRow.has('.pull-xs-right').length;
                    }
                    if ($.inArray(element, requiredfields) != -1) {
                        if (boostStyle) {
                            node = fieldRow.find('abbr.text-danger');
                            if (node.length !== 0) {
                                reqHTML = node.html();
                            } else {    // Fallback.
                                node = fieldRow.find('.req');
                            }
                        } else {
                            node = fieldRow.find('.req');
                        }

                        // The following check is required to prevent putting multiple asterisks.
                        if (node.length === 0) {
                            node = $(reqHTML).hide();
                            if (boostStyle) {
                                fieldRow.find('.pull-xs-right').append(node);
                            } else {
                                fieldRow.find('.fitemtitle label').append(node);
                            }
                        }

                        node.show('slow');
                    } else {
                        fieldRow.find('abbr.text-danger').hide('slow', function() {
                            // The following check is required to prevent removing asterisk when the selected item
                            // is quickly changed.
                            if ($.inArray(element, requiredfields) != -1) {
                                getFieldRow(element).find('abbr.text-danger').remove();
                            }
                        });
                        fieldRow.find('.req').hide('slow', function() {
                            // The following check is required to prevent removing asterisk when the selected item
                            // is quickly changed.
                            if ($.inArray(element, requiredfields) != -1) {
                                getFieldRow(element).find('.req').remove();
                            }
                        });
                    }
                });
            }
        });

        this.reqHTML = reqHTML; // In case it is updated above as a result of Boost style compatibility.
    };

    /**
     * Hide hidden fields and show fields that are no longer hidden.
     */
    Conditions.prototype.applyRule = function() {
        var hiddenElements = this.hiddenElements;
        var selectedValue = $('#id_profile_field_' + this.fieldName).val();
        var hideAll = this.hideAll;
        var name = null;

        this.conditions.forEach(function(value) {
            if (selectedValue === '') {
                if (hideAll) {
                    value.hiddenfields.forEach(function(field) {
                        if (!(field in hiddenElements)) {
                            hiddenElements[field] = '';
                            getFieldRow(field).children().hide('slow');
                        }
                    });
                } else {
                    for (name in hiddenElements) {
                        if (!hiddenElements.hasOwnProperty(name)) {
                            continue;
                        }
                        if (hiddenElements[name]) {
                            getFieldRow(name).html(hiddenElements[name]);
                        }
                        getFieldRow(name).children().show('slow');
                        delete hiddenElements[name];
                    }
                }
            }

            if (value.option == selectedValue) {
                for (name in hiddenElements) {
                    if (!hiddenElements.hasOwnProperty(name)) {
                        continue;
                    }
                    if ($.inArray(name, value.hiddenfields) == -1) {
                        if (hiddenElements[name]) {
                            getFieldRow(name).html(hiddenElements[name]);
                        }
                        getFieldRow(name).children().show('slow');
                        delete hiddenElements[name];
                    }
                }

                value.hiddenfields.forEach(function(field) {
                    if (!(field in hiddenElements)) {
                        hiddenElements[field] = '';
                        getFieldRow(field).children().hide('slow');
                    }
                });
            }
        });

        this.hiddenElements = hiddenElements;

        this.toggleReqStars();
    };

    /**
     * Hide hidden fields instantly.
     */
    Conditions.prototype.initApplyRule = function() {
        var hiddenElements = this.hiddenElements;
        var hideAll = this.hideAll;

        if (hideAll) {
            this.conditions.forEach(function(value) {
                value.hiddenfields.forEach(function(field) {
                    if (!(field in hiddenElements)) {
                        hiddenElements[field] = '';
                        // We use children here and in applyRule to overcome the disableif issue in non-boost themes.
                        getFieldRow(field).children().hide();
                    }
                });
            });
        }

        this.hiddenElements = hiddenElements;

        this.initReqStars();
        this.applyRule();
    };

    return {

        /**
         * Main initialisation.
         *
         * @param {String} fieldName The conditional field name.
         * @param {String} conditions Set of conditions as a json string.
         * @param {Boolean|Number} hideAll Whether to hide all fields initially or not.
         * @param {String} reqHTML The html string for specifying required fields.
         * @return {Conditions} A new instance of Conditions.
         * @method init
         */
        apply: function(fieldName, conditions, hideAll, reqHTML) {
            return new Conditions(fieldName, conditions, hideAll, reqHTML);
        }
    };
});
