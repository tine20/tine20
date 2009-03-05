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
    
    /**
     * var handlers
     * 
     * @todo do we need those?
     */
    /*
     handlers: {
        removeAccount: function(_button, _event) { 
            var groupGrid = Ext.getCmp('groupMembersGrid');
            var selectedRows = groupGrid.getSelectionModel().getSelections();
            
            var groupMembersStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                groupMembersStore.remove(selectedRows[i]);
            }
                
        },
        
        addAccount: function(account) {
            var groupGrid = Ext.getCmp('groupMembersGrid');
            
            var dataStore = groupGrid.getStore();
            var selectionModel = groupGrid.getSelectionModel();
            
            if (dataStore.getById(account.data.data.accountId) === undefined) {
                var record = new Tine.Tinebase.Model.User({
                    accountId: account.data.data.accountId,
                    accountDisplayName: account.data.data.accountDisplayName
                }, account.data.data.accountId);
                dataStore.addSorted(record);
            }
            selectionModel.selectRow(dataStore.indexOfId(account.data.data.accountId));            
        }
     },
     */
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    onRecordLoad: function() {
    	// you can do something here

    	Tine.Courses.CourseEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    onRecordUpdate: function() {
        Tine.Courses.CourseEditDialog.superclass.onRecordUpdate.call(this);
        
        // you can do something here    
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
                        labelSeparator: ''
                        //columnWidth: .333
                    },
                    items: [{
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
                    }]
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
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * function getFormContents
     * 
     * @todo add that later / use generic account picker grid
     */
    getMembersPickerGrid: function() {

        this.actions = {
            addAccount: new Ext.Action({
                text: this.app.i18n._('add account'),
                disabled: true,
                scope: this,
                handler: this.handlers.addAccount,
                iconCls: 'action_addContact'
            }),
            removeAccount: new Ext.Action({
                text: this.app.i18n._('remove account'),
                disabled: true,
                scope: this,
                handler: this.handlers.removeAccount,
                iconCls: 'action_deleteContact'
            })
        };
        
        /******* account picker panel ********/
        
        var accountPicker =  new Tine.widgets.account.PickerPanel ({            
            enableBbar: true,
            region: 'west',
            height: 200,
            //bbar: this.userSelectionBottomToolBar,
            selectAction: function() {              
                this.account = account;
                this.handlers.addAccount(account);
            }  
        });
                
        accountPicker.on('accountdblclick', function(account){
            this.account = account;
            this.handlers.addAccount(account);
        }, this);
        

        /******* load data store ********/

        this.dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: Tine.Tinebase.Model.User
        });

        Ext.StoreMgr.add('GroupMembersStore', this.dataStore);
        
        this.dataStore.setDefaultSort('accountDisplayName', 'asc');        
        
        var groupMembers = this.record.get('groupMembers');
        if (!groupMembers || groupMembers.length === 0) {
            this.dataStore.removeAll();
        } else {
            this.dataStore.loadData(groupMembers);
        }

        /******* column model ********/

        var columnModel = new Ext.grid.ColumnModel([{ 
            resizable: true, id: 'accountDisplayName', header: this.app.i18n._('Name'), dataIndex: 'accountDisplayName', width: 30 
        }]);

        /******* row selection model ********/

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.removeAccount.setDisabled(true);
            } else {
                // only one row selected
                this.actions.removeAccount.setDisabled(false);
            }
        }, this);
       
        /******* bottom toolbar ********/

        var membersBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });

        /******* group members grid ********/
        
        var groupMembersGridPanel = new Ext.grid.EditorGridPanel({
            id: 'groupMembersGrid',
            region: 'center',
            title: this.app.i18n._('Group Members'),
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //autoExpandColumn: 'accountLoginName',
            autoExpandColumn: 'accountDisplayName',
            bbar: membersBottomToolbar,
            border: true
        }); 
        
        /******* THE edit dialog ********/
        
        var editGroupDialog = [
            accountPicker, 
            groupMembersGridPanel
        ];
        
        return editGroupDialog;
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
