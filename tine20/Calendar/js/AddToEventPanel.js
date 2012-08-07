/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AddToEventPanel
 * @extends     Tine.widgets.dialog.AddToRecordPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.Calendar.AddToEventPanel = Ext.extend(Tine.widgets.dialog.AddToRecordPanel, {
    // private
    appName: 'Calendar',
    recordClass: Tine.Calendar.Model.Event,
    
    callingApp: 'Addressbook',
    callingModel: 'Contact',
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::isValid()
     */
    isValid: function() {

        var valid = true;

        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Event to add the contacts to'));
            valid = false;
        }

        return valid;
    },
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getRelationConfig()
     */
    getRelationConfig: function() {
        var config = {
            role: this.chooseRoleBox.getValue(),
            status: this.chooseStatusBox.getValue()
        }
        return config;
    },
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getFormItems()
     */
    getFormItems: function() {
        return {
            border: false,
            frame:  false,
            layout: 'border',

            items: [{
                region: 'center',
                border: false,
                frame:  false,
                layout : {
                    align: 'stretch',
                    type:  'vbox'
                    },
                items: [{
                    layout:  'form',
                    margins: '10px 10px',
                    border:  false,
                    frame:   false,
                    items: [ 
                        Tine.widgets.form.RecordPickerManager.get('Calendar', 'Event', {ref: '../../../searchBox'}),
                        {
                            fieldLabel: this.app.i18n._('Role'),
                            emptyText: this.app.i18n._('Select Role'),
                            xtype: 'widget-keyfieldcombo',
                            app:   'Calendar',
                            value: 'REQ',
                            anchor : '100% 100%',
                            margins: '10px 10px',
                            keyFieldName: 'attendeeRoles',
                            ref: '../../../chooseRoleBox'
                        },{
                            fieldLabel: this.app.i18n._('Status'),
                            emptyText: this.app.i18n._('Select Status'),
                            xtype: 'widget-keyfieldcombo',
                            app:   'Calendar',
                            value: 'NEEDS-ACTION',
                            anchor : '100% 100%',
                            margins: '10px 10px',
                            keyFieldName: 'attendeeStatus',
                            ref: '../../../chooseStatusBox'
                        }
                     ] 
                }]

            }]
        };
    } 
});

Tine.Calendar.AddToEventPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : String.format(Tine.Tinebase.appMgr.get('Calendar').i18n._('Adding {0} Attendee to event'), config.count),
        width : 240,
        height : 250,
        contentPanelConstructor : 'Tine.Calendar.AddToEventPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};
