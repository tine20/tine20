/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */

Ext.namespace('Tine.Crm');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.AdminPanel
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Crm Admin Panel</p>
 * <p><pre>
 * TODO         generalize this?
 * TODO         set title
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.AdminPanel
 */
Tine.Crm.AdminPanel = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    //windowNamePrefix: 'LeadEditWindow_',
    appName: 'Crm',
    recordClass: Tine.Crm.Model.Settings,
    recordProxy: Tine.Crm.settingsBackend,
    evalGrants: false,

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
        console.log(this.record);
        if (! this.record.get('default_leadstate_id') ) {
            this.record.set('default_leadstate_id', this.record.data.defaults.leadstate_id);
            this.record.set('default_leadsource_id', this.record.data.defaults.leadsource_id);
            this.record.set('default_leadtype_id', this.record.data.defaults.leadtype_id);
        }
        
        Tine.Crm.AdminPanel.superclass.onRecordLoad.call(this);        
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Crm.AdminPanel.superclass.onRecordUpdate.call(this);
        
        var defaults = {
            leadstate_id: this.record.get('default_leadstate_id'), 
            leadsource_id: this.record.get('default_leadsource_id'), 
            leadtype_id: this.record.get('default_leadtype_id')
        };
        
        this.record.set('defaults', defaults);
        
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        return {
            layout: 'accordion',
            animate: true,
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
                items: [[{
                    fieldLabel: this.app.i18n._('Leadstate'), 
                    name:'default_leadstate_id',
                    store: Tine.Crm.LeadState.getStore(),
                    displayField:'leadstate',
                    lazyInit: false,
                    value: Tine.Crm.LeadState.getStore().getAt(0).id
                }, {
                    fieldLabel: this.app.i18n._('Leadsource'), 
                    name:'default_leadsource_id',
                    store: Tine.Crm.LeadSource.getStore(),
                    displayField:'leadsource',
                    lazyInit: false,
                    value: Tine.Crm.LeadSource.getStore().getAt(0).id
                }, {
                    fieldLabel: this.app.i18n._('Leadtype'), 
                    name:'default_leadtype_id',
                    store: Tine.Crm.LeadType.getStore(),
                    displayField:'leadtype',
                    lazyInit: false,
                    value: Tine.Crm.LeadType.getStore().getAt(0).id
                }]]
            }, {
                title: this.app.i18n._('Leadstates'),
                xtype: 'panel',
                frame: true,
                html: ''
            }, {
                title: this.app.i18n._('Leadsources'),
                xtype: 'panel',
                frame: true,
                html: ''
            }, {
                title: this.app.i18n._('Leadtypes'),
                xtype: 'panel',
                frame: true,
                html: ''
            }]            
        };                
    } // end of getFormItems
});

/**
 * Crm Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Crm.AdminPanel.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Crm.AdminPanel.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Crm.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
