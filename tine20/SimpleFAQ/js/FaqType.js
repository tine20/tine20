/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ', 'Tine.SimpleFAQ.FaqType');

/**
 * @namespace Tine.SimpleFAQ.FaqType
 * @class Tine.SimpleFAQ.FaqType.Model
 * @extends Ext.data.Record
 *
 * faq type model
 */
Tine.SimpleFAQ.FaqType.Model = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'faqtype'}
], {
    appName: 'SimpleFAQ',
    modelName: 'FaqType',
    idProperty: 'id',
    // ngettext('FAQ Type', 'FAQ Types', n);
    titleProperty: 'faqtype',
    recordName: 'FAQ Type',
    recordsName: 'FAQ Type'
});

/**
 * @namespace Tine.SimpleFAQ.FaqType
 *
 * get default data for a new faqtype
 *
 * @return {Object} default data
 * @static
 */
Tine.SimpleFAQ.FaqType.Model.getDefaultData = function() {

    var data = {
        id: Tine.SimpleFAQ.Model.getRandomUnusedId(Ext.StoreMgr.get('SimpleFAQFaqTypeStore'))
    };

    return data;
};

/**
 * get faq type store
 * if available, load data from FaqType
 *
 * @return {Ext.data.JsonStore}
 */

Tine.SimpleFAQ.FaqType.getStore = function() {

    var store = Ext.StoreMgr.get('SimpleFAQFaqTypeStore');
    if(!store)
        {
            store = new Ext.data.JsonStore({
                fields: Tine.SimpleFAQ.FaqType.Model,
                baseParams: {
                    method: 'SimpleFAQ.getFaqTypes',
                    sort: 'FaqType',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                remoteSort: false
            });

            if(Tine.SimpleFAQ.registry.get('faqtypes')) {
                store.loadData(Tine.SimpleFAQ.registry.get('faqtypes'));
            }

            Ext.StoreMgr.add('SimpleFAQFaqTypeStore', store);
        }
        return store;
};

/**
 * faq type renderer
 *
 * @param   {Number} _faqtypeId
 * @return  {String} faqtype
 */
Tine.SimpleFAQ.FaqType.Renderer = function(_faqtypeId) {
    store = Tine.SimpleFAQ.FaqType.getStore();
    record = store.getById(_faqtypeId);

    if (record) {
        return record.data.faqtype;
    } else {
            return 'undefined';
    }
};

/**
 * @namespace   Tine.SimpleFAQ.FaqType
 * @class       Tine.SimpleFAQ.FaqType.GridPanel
 * @extends     Tine.SimpleFAQ.Admin.QuickaddGridPanel
 *
 * faq type grid panel
 */
Tine.SimpleFAQ.FaqType.GridPanel = Ext.extend(Tine.SimpleFAQ.Admin.QuickaddGridPanel, {

    /**
     * @private
     */
    autoExpandColumn: 'faqtype',
    quickaddMandatory: 'faqtype',

    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('SimpleFAQ');

        this.store = Tine.SimpleFAQ.FaqType.getStore();
        this.recordClass = Tine.SimpleFAQ.FaqType.Model;

        Tine.SimpleFAQ.FaqType.GridPanel.superclass.initComponent.call(this);
    },

    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
        {
            id: 'faqtype_id',
            header: 'Id',
            dataIndex: 'id',
            width: 20,
            hidden: true
        }, {
            id: 'faqtype',
            header: 'Type',
            dataIndex: 'faqtype',
            width: 150,
            hideable: false,
            sortable: false,
            editor: new Ext.form.TextField({allowBlank: false}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a new FAQ Type...')
            })
        }]);
    }
});