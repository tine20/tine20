/*
 * Tine 2.0
 * lead source edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadSource');

/**
 * @namespace Tine.Crm.LeadSource
 * @class Tine.Crm.LeadSource.Model
 * @extends Ext.data.Record
 * 
 * lead source model
 */ 
Tine.Crm.LeadSource.Model = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'leadsource'}
], {
    appName: 'Crm',
    modelName: 'LeadState',
    idProperty: 'id',
    titleProperty: 'leadsource',
    // ngettext('Lead Source', 'Lead Sources', n);
    recordName: 'Lead Source',
    recordsName: 'Lead Sources'
});

/**
 * @namespace Tine.Crm.LeadSource
 * 
 * get default data for a new leadsource
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Crm.LeadSource.Model.getDefaultData = function() {
    
    var data = {
        id: Tine.Crm.Model.getRandomUnusedId(Ext.StoreMgr.get('CrmLeadSourceStore'))
    };
    
    return data;
};

/**
 * get lead source store
 * if available, load data from LeadSources
 * 
 * @return {Ext.data.JsonStore}
 */
Tine.Crm.LeadSource.getStore = function() {
    
    var store = Ext.StoreMgr.get('CrmLeadSourceStore');
    if (!store) {

        store = new Ext.data.JsonStore({
            fields: Tine.Crm.LeadSource.Model,
            baseParams: {
                method: 'Crm.getLeadsources',
                sort: 'LeadSource',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if ( Tine.Crm.registry.get('leadsources') ) {
            store.loadData(Tine.Crm.registry.get('leadsources'));
        }
            
        Ext.StoreMgr.add('CrmLeadSourceStore', store);
    }
    return store;
};


/**
 * @namespace   Tine.Crm.LeadSource
 * @class       Tine.Crm.LeadSource.GridPanel
 * @extends     Tine.Crm.Admin.QuickaddGridPanel
 * 
 * lead sources grid panel
 * 
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadSource.GridPanel = Ext.extend(Tine.Crm.Admin.QuickaddGridPanel, {
    
    /**
     * @private
     */
    autoExpandColumn:'leadsource',
    quickaddMandatory: 'leadsource',

    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        
        this.store = Tine.Crm.LeadSource.getStore();
        this.recordClass = Tine.Crm.LeadSource.Model;
        
        Tine.Crm.LeadSource.GridPanel.superclass.initComponent.call(this);
    },
    
    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
        {
            id:'leadsource_id', 
            header: "id", 
            dataIndex: 'id', 
            width: 25, 
            hidden: true 
        }, {
            id:'leadsource', 
            header: 'entries', 
            dataIndex: 'leadsource', 
            width: 170, 
            hideable: false, 
            sortable: false, 
            editor: new Ext.form.TextField({allowBlank: false}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a Leadsource...')
            })
        }]);
    }
});
