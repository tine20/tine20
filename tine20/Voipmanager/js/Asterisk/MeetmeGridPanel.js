/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Meetme grid panel
 */
Tine.Voipmanager.AsteriskMeetmeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.AsteriskMeetme,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'confno', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'confno'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.AsteriskMeetmeBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.AsteriskMeetmeGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("id"),
            width: 10,
            sortable: true,
            hidden: true,
            dataIndex: 'id'
        }, {
            id: 'confno',
            header: this.app.i18n._("confno"),
            width: 80,
            sortable: true,
            dataIndex: 'confno',
            renderer: function(confno) {
                return Ext.util.Format.htmlEncode(confno);
            }
        }, {
            id: 'pin',
            header: this.app.i18n._("pin"),
            width: 80,
            sortable: true,
            dataIndex: 'pin',
            renderer: function(pin) {
                return Ext.util.Format.htmlEncode(pin);
            }
        }, {
            id: 'adminpin',
            header: this.app.i18n._("adminpin"),
            width: 80,
            sortable: true,
            dataIndex: 'adminpin',
            renderer: function(adminpin) {
                return Ext.util.Format.htmlEncode(adminpin);
            }
           }];
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
       
        return [

        ];
    } 
});
