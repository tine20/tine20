/*
 * Tine 2.0
 * lead type edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadType');

/**
 * @namespace Tine.Crm.LeadType
 * @class Tine.Crm.LeadType.Model
 * @extends Ext.data.Record
 * 
 * lead type model
 */ 
Tine.Crm.LeadType.Model = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'leadtype'}
], {
    appName: 'Crm',
    modelName: 'LeadType',
    idProperty: 'id',
    titleProperty: 'leadtype',
    // ngettext('Lead Type', 'Lead Types', n);
    recordName: 'Lead Type',
    recordsName: 'Lead Types'
});

/**
 * @namespace Tine.Crm.LeadType
 * 
 * get default data for a new leadtype
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Crm.LeadType.Model.getDefaultData = function() {
    
    var data = {
        id: Tine.Crm.Model.getRandomUnusedId(Ext.StoreMgr.get('CrmLeadTypeStore'))
    };
    
    return data;
};

/**
 * get lead type store
 * 
 * @return  {Ext.data.JsonStore}
 */
Tine.Crm.LeadType.getStore = function() {
    
    var store = Ext.StoreMgr.get('CrmLeadTypeStore');
    if (!store) {

        store = new Ext.data.JsonStore({
            fields: Tine.Crm.LeadType.Model,
            baseParams: {
                method: 'Crm.getLeadtypes',
                sort: 'LeadType',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if ( Tine.Crm.registry.get('leadtypes') ) {
            store.loadData(Tine.Crm.registry.get('leadtypes'));
        }
            
        Ext.StoreMgr.add('CrmLeadTypeStore', store);
    }
    return store;
};

/**
 * @namespace   Tine.Crm.LeadType
 * @class       Tine.Crm.LeadType.GridPanel
 * @extends     Tine.Crm.Admin.QuickaddGridPanel
 * 
 * lead types grid panel
 * 
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadType.GridPanel = Ext.extend(Tine.Crm.Admin.QuickaddGridPanel, {
    
    /**
     * @private
     */
    autoExpandColumn:'leadtype',
    quickaddMandatory: 'leadtype',

    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        
        this.store = Tine.Crm.LeadType.getStore();
        this.recordClass = Tine.Crm.LeadType.Model;
        
        Tine.Crm.LeadType.GridPanel.superclass.initComponent.call(this);
    },
    
    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
        {
            id:'leadtype_id', 
            header: "id", 
            dataIndex: 'id', 
            width: 25, 
            hidden: true 
        }, {
            id:'leadtype', 
            header: 'entries', 
            dataIndex: 'leadtype', 
            width: 170, 
            hideable: false, 
            sortable: false, 
            editor: new Ext.form.TextField({allowBlank: false}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a Leadtype...')
            })
        }]);
    }
});
