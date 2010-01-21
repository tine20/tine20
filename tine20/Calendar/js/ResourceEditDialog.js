/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.ResourceEditDialog
 * @extends   Tine.widgets.dialog.EditDialog
 * Resources Grid Panel <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Calendar.ResourceEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    recordClass: Tine.Calendar.Model.Resource,
    windowNamePrefix: 'ResourceEditWindow_',
    evalGrants: false,
    showContainerSelector: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    //mode: 'local',
    
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[
                {               
                title: this.app.i18n.n_('Resource', 'Resources', 1),
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
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('Name'),
                        allowBlank: false,
                        name: 'name'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('Email'),
                        allowBlank: false,
                        name: 'email',
                        vtype: 'email'
                    }, {
                        xtype: 'checkbox',
                        fieldLabel: this.app.i18n._('Is a location'),
                        //boxLabel: this.app.i18n._('Is a location'),
                        name: 'is_location'
                    }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
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
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Calendar',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Calendar',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
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
 * Opens a new resource edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Calendar.ResourceEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 400,
        name: Tine.Calendar.ResourceEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Calendar.ResourceEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};