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
 * Handle opening a dialogue to configure condition data.
 *
 * @module     profilefield_conditional/conditionconfig
 * @package    profilefield_conditional
 * @copyright  2016 Shamim Rezaie <http://foodle.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'core/ajax',
        'profilefield_conditional/dialogue', 'profilefield_conditional/otherfields'],
    function($, notification, templates, ajax, Dialogue, ModOtherFields) {

        /**
         * Condition config object.
         * @param {String} selectSelector The select box selector.
         * @param {String} inputSelector The hidden input field selector.
         * @param {String} triggerSelector The trigger selector.
         * @param {Number} fieldId Current field's ID.
         */
        var ConditionConfig = function(selectSelector, inputSelector, triggerSelector, fieldId) {
            this.selectSelector = selectSelector;
            this.inputSelector = inputSelector;
            this.triggerSelector = triggerSelector;
            this.fieldId = fieldId;

            $(triggerSelector).click(this.showConfig.bind(this));
        };

        /** @var {String} The select box selector. */
        ConditionConfig.prototype.selectSelector = null;
        /** @var {String} The hidden field selector. */
        ConditionConfig.prototype.inputSelector = null;
        /** @var {String} The trigger selector. */
        ConditionConfig.prototype.triggerSelector = null;
        /** @var {Number} fieldId Field ID. */
        ConditionConfig.prototype.fieldId = null;
        /** @var {Array} otherFields ID and name of the scales. */
        ConditionConfig.prototype.otherFields = null;
        /** @var {Array} options Menu options. */
        ConditionConfig.prototype.options = null;
        /** @var {Dialogue} Reference to the popup. */
        ConditionConfig.prototype.popup = null;

        /**
         * Displays the condition configuration dialogue.
         *
         * @method showConfig
         */
        ConditionConfig.prototype.showConfig = function() {
            var self = this;

            this.options = [];
            $(this.selectSelector).val().replace(/\r\n/, '\n').split('\n').forEach(function(value, index) {
                if (value) {
                    self.options.push({index: index, option: value});
                }
            });
            /*
            if (this.options.length == 0) {
                // This should not happen.
                return;
            }
            */

            this.getOtherFields(this.fieldId).done(function() {

                var context = {
                    options: self.options,
                    fields: self.otherFields
                };

                // Dish up the form.
                templates.render('profilefield_conditional/condition_configuration_page', context)
                    .done(function(html) {
                        new Dialogue(
                            '',
                            html,
                            self.initConditionConfig.bind(self)
                        );
                    }).fail(notification.exception);
            }).fail(notification.exception);
        };

        /**
         * Gets the condition configuration if it was set.
         *
         * @method retrieveConditionConfig
         * @return {Object|String} condition configuration or empty string.
         */
        ConditionConfig.prototype.retrieveConditionConfig = function() {
            var jsonstring = $(this.inputSelector).val();
            if (jsonstring !== '') {
                return $.parseJSON(jsonstring);
            }
            return '';
        };

        ConditionConfig.prototype.applyRestriction = function(source) {
            var sourceid = source.id;
            var sourceatt = $(source).attr('data-field');
            var targetatt = '';
            var targetid = '';

            if ($(source).hasClass('profilefield_conditional_field_required')) {
                targetatt = sourceatt.replace('profilefield_conditional_field_required_', 'profilefield_conditional_field_hidden_');
                targetid = sourceid.replace('required_', 'hidden_');
            } else if ($(source).hasClass('profilefield_conditional_field_hidden')) {
                targetatt = sourceatt.replace('profilefield_conditional_field_hidden_', 'profilefield_conditional_field_required_');
                targetid = sourceid.replace('hidden_', 'required_');
            }

            if (targetid === '' || targetid == sourceid) {
                return;
            }

            var slashedtargetatt = targetatt
                    .replace(/\\/g, '\\\\')
                    .replace(/'/g, '\\\'')
                    .replace(/"/g, '\\"')
                    .replace(/\0/g, '\\0');

            if ($(source).is(':checked')) {
                $(source).parent().parent().find('[data-field="' + slashedtargetatt + '"]').attr('checked', false);
                $(source).parent().parent().find('[data-field="' + slashedtargetatt + '"]').prop('disabled', true);
            } else {
                $(source).parent().parent().find('[data-field="' + slashedtargetatt + '"]').prop('disabled', false);
            }
        };

        /**
         * Initialises the condition configuration dialogue.
         *
         * @method initConditionConfig
         * @param {Dialogue} popup Dialogue object to initialise.
         */
        ConditionConfig.prototype.initConditionConfig = function(popup) {
            this.popup = popup;
            var self = this;
            var body = $(popup.getContent());
            // Set up the popup to show the current configuration.
            var currentconfig = this.retrieveConditionConfig();
            // Set up the form only if there is configuration settings to set.
            if (currentconfig !== '') {
                currentconfig.forEach(function(option) {
                    var slashedoption = option.option
                            .replace(/\\/g, '\\\\')
                            .replace(/'/g, '\\\'')
                            .replace(/"/g, '\\"')
                            .replace(/\0/g, '\\0');
                    option.requiredfields.forEach(function(field) {
                        body.find('[data-field="profilefield_conditional_field_required_' + slashedoption + '_' + field + '"]')
                                .attr('checked', true);
                        body.find('[data-field="profilefield_conditional_field_required_' + slashedoption + '_' + field + '"]')
                                .each(
                            function() {
                                self.applyRestriction(this);
                            }
                        );
                    });
                    option.hiddenfields.forEach(function(field) {
                        body.find('[data-field="profilefield_conditional_field_hidden_' + slashedoption + '_' + field + '"]')
                                .attr('checked', true);
                        body.find('[data-field="profilefield_conditional_field_hidden_' + slashedoption + '_' + field + '"]').each(
                            function() {
                                self.applyRestriction(this);
                            }
                        );
                    });
                });
            }
            body.on('click', '[data-action="close"]', function() {
                this.setConditionConfig();
                popup.close();
            }.bind(this));
            body.on('click', '[data-action="cancel"]', function() {
                popup.close();
            });
            body.on('click', '[type="checkbox"]', function(e) {
                this.applyRestriction(e.target);
            }.bind(this));
        };

        /**
         * Set the condition configuration back into a JSON string in the hidden element.
         *
         * @method setConditionConfig
         */
        ConditionConfig.prototype.setConditionConfig = function() {
            var self = this;
            var body = $(this.popup.getContent());
            // Get the data.
            var data = [];
            this.options.forEach(function(option) {
                var requiredfields = [];
                var hiddenfields = [];
                var slashedoption = option.option
                        .replace(/\\/g, '\\\\')
                        .replace(/'/g, '\\\'')
                        .replace(/"/g, '\\"')
                        .replace(/\0/g, '\\0');
                self.otherFields.forEach(function(field) {
                    if (body.find(
                            '[data-field="profilefield_conditional_field_required_' + slashedoption + '_' + field.shortname + '"]'
                            ).is(':checked')) {
                        requiredfields.push(field.shortname);
                    }
                    if (body.find(
                            '[data-field="profilefield_conditional_field_hidden_' + slashedoption + '_' + field.shortname + '"]'
                            ).is(':checked')) {
                        hiddenfields.push(field.shortname);
                    }
                });
                data.push({
                    option: option.option,
                    requiredfields: requiredfields,
                    hiddenfields: hiddenfields
                });
            });
            var datastring = JSON.stringify(data);
            // Send to the hidden field on the form.
            $(this.inputSelector).val(datastring);
        };

        /**
         * Get all existing custom profile fields except the current field.
         *
         * @method getOtherFields
         * @param {Number} fieldId The id of current field.
         * @return {Promise} A deffered object with field information.
         */
        ConditionConfig.prototype.getOtherFields = function(fieldId) {
            return ModOtherFields.getFields(fieldId).then(function(values) {
                this.otherFields = values;
                return values;
            }.bind(this));
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} selectSelector The select box selector.
             * @param {String} inputSelector The hidden input field selector.
             * @param {String} triggerSelector The trigger selector.
             * @param {Number} fieldId The current fieldid.
             * @return {ConditionConfig} A new instance of ConditionConfig.
             * @method init
             */
            init: function(selectSelector, inputSelector, triggerSelector, fieldId) {
                return new ConditionConfig(selectSelector, inputSelector, triggerSelector, fieldId);
            }
        };
    }
);
