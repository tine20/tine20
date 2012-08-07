/*
 * Tine 2.0
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.AddToProjectPanel
 * @extends     Tine.widgets.dialog.AddToRecordPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

Tine.Projects.AddToProjectPanel = Ext.extend(Tine.widgets.dialog.AddToRecordPanel, {
    // private
    appName : 'Projects',
    recordClass: Tine.Projects.Model.Project,
    callingApp: 'Addressbook',
    callingModel: 'Contact',
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::isValid()
     */
    isValid: function() {
        
        var valid = true;
        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Project to add the contacts to'));
            valid = false;
        }
        if(this.chooseRoleBox.getValue() == '') {
            this.chooseRoleBox.markInvalid(this.app.i18n._('Please select the attenders\' role'));
            valid = false;
        }
        
        return valid;
    },
    
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getRelationConfig()
     */
    getRelationConfig: function() {
        var config = {
            type: this.chooseRoleBox.getValue() ? this.chooseRoleBox.getValue() : 'COWORKER'
        };
        return config;
    },

    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getFormItems()
     */
    getFormItems: function() {
        return {
            border : false,
            frame : false,
            layout : 'border',

            items : [ {
                region : 'center',
                border: false,
                frame:  false,
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                },
                items: [{
                    layout:  'form',
                    margins: '10px 10px',
                    border:  false,
                    frame:   false,
                    items: [ 
                        Tine.widgets.form.RecordPickerManager.get('Projects', 'Project', {fieldLabel: this.app.i18n._('Select Project'), anchor : '100% 100%', ref: '../../../searchBox'}),
                        {
                            fieldLabel: this.app.i18n._('Role'),
                            emptyText: this.app.i18n._('Select Role'),
                            anchor: '100% 100%',
                            xtype: 'widget-keyfieldcombo',
                            app:   'Projects',
                            value: 'COWORKER',
                            keyFieldName: 'projectAttendeeRole',
                            ref: '../../../chooseRoleBox'
                        }
                        
                        ] 
                    }]

            }]
        };
    }
});

Tine.Projects.AddToProjectPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : String.format(Tine.Tinebase.appMgr.get('Projects').i18n._('Adding {0} Participants to project'), config.count),
        width : 250,
        height : 150,
        contentPanelConstructor : 'Tine.Projects.AddToProjectPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};
