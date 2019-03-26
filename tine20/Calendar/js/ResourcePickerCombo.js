/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.ResourcePickerCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.ResourcePickerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    recordClass: Tine.Calendar.Model.Resource,

    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for=".">',
                    '<div class="x-combo-list-item">',
                        '<table>',
                            '<tr>',
                                '<td style="min-width: 20px;" class="tine-grid-row-action-icon cal-attendee-type-resource">&nbsp</td>',
                                '<td width="100%">{[Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderResourceName(values)]}</td>',
                            '</tr>',
                            '<tr>',
                                '<td style="min-width: 20px;">&nbsp</td>',
                                '<td class="cal-attendee-resource-hierarchy">{[this.encodeHierarchy(values.hierarchy)]}</td>',
                            '</tr>',
                        '</table>',
                        '{[Tine.widgets.path.pathsRenderer(values.paths, this.lastQuery)]}',
                    '</div>',
                '</tpl>', {
                    encodeHierarchy: function(hierarchy) {
                        hierarchy = String(hierarchy).replace(/\//g, ' » ');
                        if (hierarchy == 'null') {
                            return '';
                        }
                        return Tine.Tinebase.EncodingHelper.encode(hierarchy);
                    }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Calendar', 'Resource', Tine.Calendar.ResourcePickerCombo);