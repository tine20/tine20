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
                                '<td style="min-width: 20px;">{[this.encodeIconTypes(values.type)]}</td>',
                                '<td width="100%">{[Tine.Tinebase.EncodingHelper.encode(values.name)]}</td>',
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
                    },
                    encodeIconTypes: function(type) {
                            icon = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes')
                                .getById(type).get('icon');

                            return '<img class="tine-grid-row-action-icon" src="' + icon + '"/>';
                    }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Calendar', 'Resource', Tine.Calendar.ResourcePickerCombo);