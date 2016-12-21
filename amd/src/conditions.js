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
     * Condition object.
     */
    var Conditions = function(fieldName, conditions, hideAll, reqHTML) {
        this.fieldName = fieldName;
        this.conditions = $.parseJSON(conditions);
        var allElements = [];
        this.conditions.forEach(function (option) {
            option.hiddenfields.forEach(function (field) {
                if ($.inArray(field, allElements) == -1) {
                    allElements.push(field);
                }
            });
            option.requiredfields.forEach(function (field) {
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
        $('#id_profile_field_' + fieldName).change(this.applyRule.bind(this));
        $('#id_profile_field_' + fieldName).closest('form').submit(this.beforeSubmit.bind(this));
    };

    Conditions.prototype.fieldName = null;
    Conditions.prototype.conditions = null;
    Conditions.prototype.allElements = null;
    Conditions.prototype.hiddenElements = null;
    Conditions.prototype.hideAll = null;
    /** @var {Array} options Menu options. */
    Conditions.prototype.options = null;

    /**
     *
     */
    Conditions.prototype.beforeSubmit = function(event) {
        if (!event.isDefaultPrevented()) {
            for (var field in this.hiddenElements) {
                this.hiddenElements[field] = $('#fitem_id_profile_field_' + field).html();
                $('#fitem_id_profile_field_' + field).html('');
            }
        }
    };

    /**
     *
     */
    Conditions.prototype.initReqStars = function() {
        var initReqFields = [];
        var initHiddenFields = [];

        this.allElements.forEach(function (element) {
            if ($('#fitem_id_profile_field_' + element).find('.fitemtitle .req').length !== 0) {
                initReqFields.push(element);
            }
        });

        if (this.hideAll) {
            this.conditions.forEach(function (value) {
                value.hiddenfields.forEach(function (field) {
                    initHiddenFields.push(field);
                });
            });
        }

        this.conditions.push({'option': '', 'hiddenfields': initHiddenFields, 'requiredfields': initReqFields});
    };

    /**
     *
     */
    Conditions.prototype.toggleReqStars = function() {
        var selectedValue = $('#id_profile_field_' + this.fieldName).val();
        var reqHTML = this.reqHTML;
        var allElements = this.allElements;
        var requiredfields;
        var node;

        this.conditions.forEach(function (option) {
            if (option.option == selectedValue) {
                requiredfields = option.requiredfields;
                allElements.forEach(function (element) {
                    if ($.inArray(element, requiredfields) != -1) {
                        node = $('#fitem_id_profile_field_' + element).find('.fitemtitle .req');
                        // The following check is required to prevent putting multiple asterisks.
                        if (node.length === 0) {
                            node = $(reqHTML).hide();
                            $('#fitem_id_profile_field_' + element).find('.fitemtitle label').append(node);
                        }
                        node.show('slow');
                    } else {
                        $('#fitem_id_profile_field_' + element).find('.fitemtitle .req').hide('slow', function () {
                            // The following check is required to prevent removing asterisk when the selected item
                            // is quickly changed.
                            if ($.inArray(element, requiredfields) != -1) {
                                $('#fitem_id_profile_field_' + element).find('.fitemtitle .req').remove();
                            }
                        });
                    }
                });
            }
        });
    };

    /**
     *
     */
    Conditions.prototype.applyRule = function() {
        var hiddenElements = this.hiddenElements;
        var selectedValue = $('#id_profile_field_' + this.fieldName).val();
        var tempArray;
        var hideAll = this.hideAll;
        var name = null;

        this.conditions.forEach(function (value) {
            if (selectedValue === '') {
                if (hideAll) {
                    tempArray = {};
                    value.hiddenfields.forEach(function (field) {
                        if (!(field in hiddenElements)) {
                            hiddenElements[field] = '';
                            $('#fitem_id_profile_field_' + field).hide('slow');
                        }
                    });
                } else {
                    for (name in hiddenElements) {
                        if (hiddenElements[name]) {
                            $('#fitem_id_profile_field_' + name).html(hiddenElements[name]);
                        }
                        $('#fitem_id_profile_field_' + name).show('slow');
                        delete hiddenElements[name];
                    }
                }
            }

            if (value.option == selectedValue) {
                for (name in hiddenElements) {
                    if ($.inArray(name, value.hiddenfields) == -1) {
                        if (hiddenElements[name]) {
                            $('#fitem_id_profile_field_' + name).html(hiddenElements[name]);
                        }
                        $('#fitem_id_profile_field_' + name).show('slow');
                        delete hiddenElements[name];
                    }
                }

                value.hiddenfields.forEach(function (field) {
                    if (!(field in hiddenElements)) {
                        hiddenElements[field] = '';
                        $('#fitem_id_profile_field_' + field).hide('slow');
                    }
                });
            }
        });

        this.hiddenElements = hiddenElements;

        this.toggleReqStars();
    };

    /**
     *
     */
    Conditions.prototype.initApplyRule = function() {
        var hiddenElements = this.hiddenElements;
        var hideAll = this.hideAll;

        if (hideAll) {
            this.conditions.forEach(function (value) {
                value.hiddenfields.forEach(function (field) {
                    if (!(field in hiddenElements)) {
                        hiddenElements[field] = '';
                        $('#fitem_id_profile_field_' + field).hide();
                    }
                });
            });
        }

        this.hiddenElements = hiddenElements;

        this.initReqStars();
        this.applyRule();
    };

    Conditions.prototype.getHiddenArea = function () {
        var hiddenArea;
        var formElement;

        hiddenArea = document.getElementById('id_profilefield_conditional_disable_area_' + this.fieldName);
        if (!hiddenArea) {
            formElement = $('#mform1');
            hiddenArea = document.createElement('fieldset');
            hiddenArea.id = 'id_profilefield_conditional_disable_area_' + this.fieldName;
            hiddenArea.class = 'hidden';

            formElement.append(hiddenArea);
        }

        hiddenArea.innerHTML = '';

        return hiddenArea;
    };

    return {

        /**
         * Main initialisation.
         *
         * @param {Number} fieldId The current fieldid.
         * @return {Conditions} A new instance of Conditions.
         * @method init
         */
        apply: function(fieldName, conditions, hideAll, reqHTML) {
            return new Conditions(fieldName, conditions, hideAll, reqHTML);
        }
    };
});
