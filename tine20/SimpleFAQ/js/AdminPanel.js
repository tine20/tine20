/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ');

/**
 * Create a new Tine.SimpleFAQ.AdminPanel
 */
Tine.SimpleFAQ.AdminPanel = Ext.extend(Tine.widgets.dialog.EditDialog, {

    appName: 'SimpleFAQ',
    recordClass: Tine.SimpleFAQ.Model.Settings,
    recordProxy: Tine.SimpleFAQ.settingsBackend,

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {
        
    },

    /**
     * executed after record got updated from proxy
     *
     * @private
     */
    onRecordLoad: function() {
        if(!this.record.get('default_faqstatus_id')) {
            this.record.set('default_faqstatus_id', this.record.data.defaults.faqstatus_id);
            this.record.set('default_faqtype_id', this.record.data.defaults.faqtype_id);
        }
        if (this.fireEvent('load', this) !== false) {
            this.getForm().loadRecord(this.record);
            this.updateToolbars(this.record, this.recordClass.getMeta('containerProperty'));

            this.loadMask.hide();
        }
    },

    /**
     * executed when a record was updated from form
     *
     * @private
     */
    onRecordUpdate: function() {
        Tine.SimpleFAQ.AdminPanel.superclass.onRecordUpdate.call(this);
        var defaults = {
            faqstatus_id: this.record.get('default_faqstatus_id'),
            faqtype_id: this.record.get('default_faqtype_id')
        };

        this.record.set('defaults', defaults);

        this.record.set('faqstatuses', this.getFromStore(this.faqstatusPanel.store));
        this.record.set('faqtypes', this.getFromStore(this.faqtypePanel.store));

    },

     /**
      * get values from store (as array)
      *
      * @param {Ext.data.JsonStore} store
      * @return {Array}
      */
    getFromStore: function(store) {
        var result = [];
        store.each(function(record) {
            result.push(record.data);
        }, this);
        store.commitChanges();

        return result;
    },

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initalisation is done
     *
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        this.faqstatusPanel = new Tine.SimpleFAQ.FaqStatus.GridPanel({
            title: this.app.i18n._('FAQ Status')
        });
        
        this.faqtypePanel = new Tine.SimpleFAQ.FaqType.GridPanel({
            title: this.app.i18n._('FAQ Type')
        });

        return {
            xtype: 'tabpanel',
            activeTab: 0,
            border: true,
            items: [{
                title: this.app.i18n._('Defaults'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'combo',
                    anchor: '90%',
                    labelSeparator: '',
                    columnWidth: 1,
                    valueField:'id',
                    typeAhead: true,
                    mode: 'local',
                    triggerAction: 'all',
                    editable: false,
                    allowBlank: false,
                    forceSelection: true
                },
                items: [[
                        {
                    fieldLabel: this.app.i18n._('FAQ Status'),
                    name:'default_faqstatus_id',
                    store: Tine.SimpleFAQ.FaqStatus.getStore(),
                    displayField:'faqstatus',
                    lazyInit: false,
                    value: Tine.SimpleFAQ.FaqStatus.getStore().getAt(0).id
                }, {
                    fieldLabel: this.app.i18n._('FAQ Type'),
                    name:'default_faqtype_id',
                    store: Tine.SimpleFAQ.FaqType.getStore(),
                    displayField:'faqtype',
                    lazyInit: false,
                    value: Tine.SimpleFAQ.FaqType.getStore().getAt(0).id
                }
            ]]
            },
                this.faqstatusPanel,
                this.faqtypePanel
            ]
        };
    }
});

/**
 * admin panel on update function
 *
 * TODO         update registry without reloading the mainscreen
 */
Tine.SimpleFAQ.AdminPanel.onUpdate = function() {
    // reload mainscreen to make sure registry gets updated
    window.location = window.location.href.replace(/#+.*/, '');
}

/**
 * SimpleFAQ admin settings popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.SimpleFAQ.AdminPanel.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 400,
        name: Tine.SimpleFAQ.AdminPanel.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.SimpleFAQ.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};

Ext.ns('Tine.SimpleFAQ.Admin');

/**
 * @namespace   Tine.SimpleFAQ.Admin
 * @class       Tine.SimpleFAQ.Admin.QuickaddGridPanel
 * @extends     Tine.widgets.grid.QuickaddGridPanel
 *
 * admin config option quickadd grid panel
 */
Tine.SimpleFAQ.Admin.QuickaddGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('SimpleFAQ');

        Tine.SimpleFAQ.Admin.QuickaddGridPanel.superclass.initComponent.call(this);
    }
});
