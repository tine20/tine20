/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Timetracker');

Tine.Timetracker.TimeaccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'TimeaccountEditWindow_',
    appName: 'Timetracker',
    recordClass: Tine.Timetracker.Model.Timeaccount,
    recordProxy: Tine.Timetracker.timeaccountBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[
                {               
                title: this.app.i18n._('Timeaccount'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        allowBlank: false
                        }, {
                        columnWidth: .666,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                        }], [{
                        columnWidth: 1,
                        xtype: 'tabpanel',
                        height: 270,
                        activeItem: 0,
                        items: [{
                            title: 'Access',
                            //items: []
                            html: ''
                        }, {
                            title: 'Budget',
                            //items: []
                            html: ''
                        }]}], [{
                            fieldLabel: this.app.i18n._('Unit'),
                            name: 'unit'
                        }, {
                            fieldLabel: this.app.i18n._('Unit Price'),
                            name: 'unitprice'
                        }, {
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'is_open',
                            xtype: 'combo'
                            //checkboxLabel: this.app.i18n._('Times could be added')
                        
                        }]] 
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    margins: '0 5 0 5',
                    border: true,
                    items: [new Ext.Panel({
                        // @todo generalise!
                        title: this.app.i18n._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        border: false,
                        items: [{
                            style: 'margin-top: -4px; border 0px;',
                            labelSeparator: '',
                            xtype:'textarea',
                            name: 'description',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100% 100%',
                            emptyText: this.app.i18n._('Enter description')                            
                        }]
                    }),
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'Timetracker',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.Timetracker.TimeaccountEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Timetracker.TimeaccountEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Timetracker.TimeaccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};