/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.ProjectEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Project Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Projects.ProjectEditDialog
 */
Tine.Projects.ProjectEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ProjectEditWindow_',
    appName: 'Projects',
    recordClass: Tine.Projects.Model.Project,
    recordProxy: Tine.Projects.recordBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: true,
    showContainerSelector: true,
    
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
    	// you can do something here

    	Tine.Projects.ProjectEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Projects.ProjectEditDialog.superclass.onRecordUpdate.call(this);
        
        // you can do something here    
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
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{               
                title: this.app.i18n._('Project'),
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
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                        }], [new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Projects',
                            keyFieldName: 'projectStatus',
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'status',
                            columnWidth: 0.5
                        })]
                    ] 
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
                        app: 'Projects',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Projects',
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
 * Projects Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Projects.ProjectEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Projects.ProjectEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Projects.ProjectEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
