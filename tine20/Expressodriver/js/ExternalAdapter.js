/*
 * Tine 2.0
 * external storage adapters grid panel
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

Ext.namespace('Tine.Expressodriver', 'Tine.Expressodriver.ExternalAdapter');

/**
 * @namespace Tine.Expressodriver.ExternalAdapter
 * @class Tine.Crm.ExternalAdapter.Model
 * @extends Ext.data.Record
 *
 * external adapters model
 */
Tine.Expressodriver.ExternalAdapter.Model = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'name'},
   {name: 'adapter'},
   {name: 'url'},
   {name: 'useEmailAsLoginName', type: 'bool'}
], {
    appName: 'Expressodriver',
    modelName: 'ExternalAdapter',
    idProperty: 'id',
    titleProperty: 'external adapters',
    recordName: 'External Adapter',
    recordsName: 'External Adapters'
});

/**
 * get default data from external adapter
 * @returns {Tine.Expressodriver.ExternalAdapter.Model.getDefaultData.data}
 */
Tine.Expressodriver.ExternalAdapter.Model.getDefaultData = function() {

    var data = {
        id: Tine.Expressodriver.Model.getRandomUnusedId(Ext.StoreMgr.get('ExternalAdapterStore'))
    };

    return data;
};


/**
 * get external adapter store
 *
 * @return {Ext.data.JsonStore}
 */
Tine.Expressodriver.ExternalAdapter.getStore = function() {

    var store = Ext.StoreMgr.get('ExternalAdapterStore');
    if (!store) {

        store =  new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Expressodriver.ExternalAdapter.Model
        });

        Ext.StoreMgr.add('ExternalAdapterStore', store);
    }
    return store;
};

/**
 * set external adapter from backend
 * @type Tine.Tinebase.data.RecordProxy
 */
Tine.Expressodriver.ExternalAdapter.Backend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Expressodriver',
    modelName: 'ExternalAdapter',
    recordClass: Tine.Expressodriver.ExternalAdapter.Model
});


/**
 * @namespace   Tine.Expressodriver.ExternalAdapter
 * @class       Tine.Expressodriver.ExternalAdapter.GridPanel
 * @extends     Tine.
 *
 * external adapters grid panel
 *
 * <p>
 * </p>
 *
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 *
 */
Tine.Expressodriver.ExternalAdapterGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * @private
     */
    autoExpandColumn: 'url',

    hasQuickSearchFilterToolbarPlugin: false,

    recordClass: Tine.Expressodriver.ExternalAdapter.Model,

    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: false,

    usePagingToolbar: false,

    disableDeleteActionCheckServiceMap: Ext.emptyFn,

    autoRefreshInterval: 300000,

    stateful: false,


    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Expressodriver');

        this.gridConfig = {
        };

        this.gridConfig.columns = [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 150,
            sortable: true,
            dataIndex: 'name'
            }, {
                id: 'adapter',
                header: this.app.i18n._("Adapter"),
                width: 150,
                sortable: true,
                dataIndex: 'adapter',
                renderer: this.adapterRenderer.createDelegate(this)

        },{
                id: 'url',
                header: this.app.i18n._("Url"),
                width: 100,
                sortable: true,
                dataIndex: 'url'
        }, new Ext.ux.grid.CheckColumn({
                header: this.app.i18n._("Use e-mail as login name"),
                width: 55,
                sortable: true,
                dataIndex: 'useEmailAsLoginName'
        })];

        Tine.Expressodriver.ExternalAdapterGridPanel.superclass.initComponent.call(this);

    },
    adapterRenderer: function (adapter) {
        return Tine.Tinebase.widgets.keyfield.Renderer.render('Expressodriver', 'externalDrivers', adapter);
    },

    initStore: function() {

        this.store = Tine.Expressodriver.ExternalAdapter.getStore();

    },

    initFilterPanel: function() {},

    initLayout: function() {
        this.supr().initLayout.call(this);

        this.items.push({
            region : 'north',
            height : 55,
            border : false,
            items  : this.actionToolbar
        });
    },

    loadGridData: function(options) {
        // do nothing here
    },

    loadAdapters: function(items){

        var recordProxy = Tine.Expressodriver.ExternalAdapter.Backend;

        if (Ext.isArray(items)) {
                    Ext.each(items, function(item) {
                        var record = recordProxy.recordReader({responseText: Ext.encode(item)});
                        this.store.addSorted(record);
                    }, this);
            }
    },

    /**
     * on update after edit
     *
     * @param {String|Tine.Tinebase.data.Record} record
     */
    onUpdateRecord: function (record) {
        //Tine.Expressodriver.ExternalAdapterGridPanel.superclass.onUpdateRecord.apply(this, arguments);

        console.log('on update record');
        console.log(record);


        var myRecord = this.store.getById(record.id);

        if (myRecord) {
            // copy values from edited record
            myRecord.beginEdit();
            for (var p in record.data) {
                myRecord.set(p, record.get(p));
            }
            myRecord.endEdit();

        } else {
            this.store.add(record);
        }

    }

});
