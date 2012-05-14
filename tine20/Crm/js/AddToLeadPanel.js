/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.AddToLeadPanel
 * @extends     Tine.widgets.dialog.AddToRecordPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.Crm.AddToLeadPanel = Ext.extend(Tine.widgets.dialog.AddToRecordPanel, {
    // private
    appName : 'Crm',    
    recordClass: Tine.Crm.Model.Lead,
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::isValid()
     */
    isValid: function() {
        var valid = true;
        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Lead to add the contacts to'));
            valid = false;
        }
        
        if(this.chooseRoleBox.getValue() == '') {
            this.chooseRoleBox.markInvalid(this.app.i18n._('Please select the attenders\' role'));
            valid = false;
        }
        
        return valid;
    },
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getRecordConfig()
     */
    getAddToRecords: function() {
        if(this.addRecords) {
            var relations = [];
            Ext.each(this.addRecords, function(contact) {
                relations.push({
                    own_backend: "Sql",
                    own_degree: "sibling",
                    own_id: this.searchBox.selectedRecord.get('id'),
                    own_model: "Crm_Model_Lead",
                    related_backend: "sql",
                    related_id: contact.id, 
                    related_model: "Addressbook_Model_Contact",
                    type: this.chooseRoleBox.getValue().toUpperCase(),
                    related_record: contact
                });
            }, this);
        }
        return relations;
    },
    
    /**
     * @see Tine.widgets.dialog.AddToRecordPanel::getFormItems()
     */
    getFormItems : function() {
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
                        {
                            xtype: 'crmleadpickercombobox',
                            fieldLabel: this.app.i18n._('Lead'),
                            emptyText: this.app.i18n._('Select Lead'),
                            anchor : '100% 100%',
                            ref: '../../../searchBox'
                        }, {
                            fieldLabel: this.app.i18n._('Role'),
                            amptyText: this.app.i18n._('Select Role'),
                            xtype: 'leadcontacttypecombo',
                            ref : '../../../chooseRoleBox',
                            anchor : '100% 100%'
                    }] 
                }]
            }]
        };
    }
});

Tine.Crm.AddToLeadPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : String.format(Tine.Tinebase.appMgr.get('Crm').i18n._('Adding {0} Contacts to lead'), config.addRecords.length),
        width : 250,
        height : 150,
        contentPanelConstructor : 'Tine.Crm.AddToLeadPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};
