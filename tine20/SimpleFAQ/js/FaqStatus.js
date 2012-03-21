/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ', 'Tine.SimpleFAQ.FaqStatus');

/**
 * @namespace Tine.SimpleFAQ.FaqStatus
 * @class Tine.SimpleFAQ.FaqStatus.Model
 * @extends Ext.data.Record
 *
 * faq status model
 */
Tine.SimpleFAQ.FaqStatus.Model = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'faqstatus'}
], {
    appName: 'SimpleFAQ',
    modelName: 'FaqStatus',
    idProperty: 'id',
    // ngettext('FAQ Status', 'FAQ Status', n);
    titleProperty: 'faqstatus',
    recordName: 'FAQ Status',
    recordsName: 'FAQ Status'
});

/**
 * @namespace Tine.SimpleFAQ.FaqStatus
 *
 * get default data for a new faqstatus
 *
 * @return {Object} default data
 * @static
 */
Tine.SimpleFAQ.FaqStatus.Model.getDefaultData = function() {
    var data = {
        id: Tine.SimpleFAQ.Model.getRandomUnusedId(Ext.StoreMgr.get('SimpleFAQFaqStatusStore'))
    };

    return data;
};

/**
 * get faq status store
 * if available, load data from FaqStatus
 *
 * @return {Ext.data.JsonStore}
 */

Tine.SimpleFAQ.FaqStatus.getStore = function() {
    var store = Ext.StoreMgr.get('SimpleFAQFaqStatusStore');
    if(!store)
        {
            store = new Ext.data.JsonStore({
                fields: Tine.SimpleFAQ.FaqStatus.Model,
                baseParams: {
                    method: 'SimpleFAQ.getFaqStatuses',
                    sort: 'FaqStatus',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                remoteSort: false
            });

            if(Tine.SimpleFAQ.registry.get('faqstatuses')) {
                store.loadData(Tine.SimpleFAQ.registry.get('faqstatuses'));
            }

            Ext.StoreMgr.add('SimpleFAQFaqStatusStore', store);
        }
        return store;
};

/**
 * faq status renderer
 *
 * @param   {Number} _faqstatusId
 * @return  {String} faqstatus
 */
Tine.SimpleFAQ.FaqStatus.Renderer = function(_faqstatusId) {
    store = Tine.SimpleFAQ.FaqStatus.getStore();
    record = store.getById(_faqstatusId);

    if (record) {
       return record.data.faqstatus;
    } else {
        return 'undefined';
    }
};

/**
 * @namespace   Tine.SimpleFAQ.FaqStatus
 * @class       Tine.SimpleFAQ.FaqStatus.GridPanel
 * @extends     Tine.SimpleFAQ.Admin.QuickaddGridPanel
 *
 * faq status grid panel
 */
Tine.SimpleFAQ.FaqStatus.GridPanel = Ext.extend(Tine.SimpleFAQ.Admin.QuickaddGridPanel, {
    /**
     * @private
     */
    autoExpandColumn: 'faqstatus',
    quickaddMandatory: 'faqstatus',

    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('SimpleFAQ');

        this.store = Tine.SimpleFAQ.FaqStatus.getStore();
        this.recordClass = Tine.SimpleFAQ.FaqStatus.Model;

        Tine.SimpleFAQ.FaqStatus.GridPanel.superclass.initComponent.call(this);
    },

    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
        {
            id: 'faqstatus_id',
            header: 'Id',
            dataIndex: 'id',
            width: 20,
            hidden: true
        }, {
            id: 'faqstatus',
            header: 'Status',
            dataIndex: 'faqstatus',
            width: 150,
            hideable: false,
            sortable: false,
            editor: new Ext.form.TextField({allowBlank: false}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a new FAQ Status...')
            })
        }]);
    }
});