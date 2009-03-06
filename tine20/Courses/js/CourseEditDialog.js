/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountEditDialog.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Courses');

Tine.Courses.CourseEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'CourseEditWindow_',
    appName: 'Courses',
    recordClass: Tine.Courses.Model.Course,
    recordProxy: Tine.Courses.recordBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * members grid panel
     * @type 
     */
    membersGrid: null,
        
    /**
     * overwrite update toolbars function (we don't have record members yet)
     */
    updateToolbars: function() {

    },
    
    onRecordLoad: function() {
    	// you can do something here
        var members = this.record.get('members') || [];
        this.membersStore.loadData({results: members});
        
        console.log(this.record);
        
       	Tine.Courses.CourseEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    onRecordUpdate: function() {
        Tine.Courses.CourseEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('members', '');
        
        var members = [];
        this.membersStore.each(function(_record){
            members.push(_record.data);
        });
        
        this.record.set('members', members);
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
            items:[{               
                title: this.app.i18n._('Course'),
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
                        columnWidth: 1
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Course Name'), 
                        name:'name',
                        allowBlank: false
                    }, {
                        fieldLabel: this.app.i18n._('Course / School Type'), 
                        name:'type',
                        allowBlank: false
                    }, {
                        name: 'description',
                        fieldLabel: this.app.i18n._('Description'),
                        grow: false,
                        preventScrollbars:false,
                        height: 60
                    }, {
                        hideLabel: true,
                        boxLabel: this.app.i18n._('Internet'),
                        name: 'internet',
                        xtype: 'checkbox',
                        columnWidth: 0.33
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
                    items: [
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'Courses',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Courses',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            }, {
                title: this.app.i18n._('Members'),
                layout: 'fit',
                items: [this.getMembersGrid()]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * get the members grid panel
     * @return {}
     */
    getMembersGrid: function() {
        if (! this.membersGrid) {
            this.membersStore =  new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                fields: Tine.Tinebase.Model.Account
            });
            
            var columns = [];
            
            this.membersGrid = new Tine.widgets.account.ConfigGrid({
                accountPickerType: 'user',
                accountListTitle: this.app.i18n._('Members'),
                configStore: this.membersStore,
                configColumns: columns
            });
        }
        return this.membersGrid;
    }
});

/**
 * Courses Edit Popup
 */
Tine.Courses.CourseEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Courses.CourseEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Courses.CourseEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
