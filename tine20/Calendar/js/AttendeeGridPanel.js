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
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Calendar.AttendeeGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    autoExpandColumn: 'user_id',
    clicksToEdit: 1,
    enableHdMenu: false,
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Calendar.Model.Event
     * @property record
     */
    record: null,
    
    /**
     * id of current account
     * 
     * @type Number
     * @property currentAccountId
     */
    currentAccountId: null,
    
    /**
     * ctx menu
     * 
     * @type Ext.menu.Menu
     * @property ctxMenu
     */
    ctxMenu: null,
    
    /**
     * store to hold all attendee
     * 
     * @type Ext.data.Store
     * @property attendeeStore
     */
    attendeeStore: null,
    
    stateful: true,
    stateId: 'cal-attendeegridpanel',
    //stateEvents: ['sortchange', ],
    //applyState: function(state) {
    //    console.log(state);
    //},
    
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Calendar');
        
        this.currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        this.title = this.app.i18n._('Attendee');
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Calendar.Model.Attender.getFieldDefinitions(),
            sortInfo: {field: 'user_id', direction: 'ASC'}
        });
        
        this.on('beforeedit', this.onBeforeAttenderEdit, this);
        this.on('afteredit', this.onAfterAttenderEdit, this);
        
        this.initColumns();
        
        Tine.Calendar.AttendeeGridPanel.superclass.initComponent.call(this);
    },
    
    initColumns: function() {
        this.columns = [{
            id: 'role',
            dataIndex: 'role',
            width: 70,
            sortable: true,
            hidden: true,
            header: this.app.i18n._('Role'),
            renderer: this.renderAttenderRole.createDelegate(this)
        },/* {
            id: 'quantity',
            dataIndex: 'quantity',
            width: 40,
            sortable: true,
            hidden: true,
            header: '&#160;',
            tooltip: this.app.i18n._('Quantity'),
            renderer: this.renderAttenderQuantity.createDelegate(this)
        },*/ {
            id: 'displaycontainer_id',
            dataIndex: 'displaycontainer_id',
            width: 200,
            sortable: false,
            hidden: true,
            header: Tine.Tinebase.translation._hidden('Saved in'),
            tooltip: this.app.i18n._('This is the calendar where the attender has saved this event in'),
            renderer: this.renderAttenderDispContainer.createDelegate(this),
            // disable for the moment, as updating calendarSelectWidget is not working in both directions
            editor2: new Tine.widgets.container.selectionComboBox({
                blurOnSelect: true,
                selectOnFocus: true,
                appName: 'Calendar',
                //startNode: 'personalOf', -> rework to startPath!
                getValue: function() {
                    if (this.selectedContainer) {
                        // NOTE: the store checks if data changed. If we don't overwrite to string, 
                        //  the check only sees [Object Object] wich of course never changes...
                        var container_id = this.selectedContainer.id;
                        this.selectedContainer.toString = function() {return container_id;};
                    }
                    return this.selectedContainer;
                },
                listeners: {
                    scope: this,
                    select: function(field, newValue) {
                        // the field is already blured, due to the extra chooser window. We need to change the value per hand
                        var selection = this.getSelectionModel().getSelectedCell();
                        if (selection) {
                            var row = selection[0];
                            this.store.getAt(row).set('displaycontainer_id', newValue);
                        }
                    }
                }
            })
        }, {
            id: 'user_type',
            dataIndex: 'user_type',
            width: 20,
            sortable: true,
            resizable: false,
            header: '&#160;',
            tooltip: this.app.i18n._('Type'),
            renderer: this.renderAttenderType.createDelegate(this),
            editor: new Ext.form.ComboBox({
                blurOnSelect  : true,
                expandOnFocus : true,
                mode          : 'local',
                store         : [
                    ['user',     this.app.i18n._('User')   ],
                    ['group',    this.app.i18n._('Group')  ],
                    ['resource', this.app.i18n._('Resource')]
                ]
            })
        }, {
            id: 'user_id',
            dataIndex: 'user_id',
            width: 300,
            sortable: true,
            header: this.app.i18n._('Name'),
            renderer: this.renderAttenderName.createDelegate(this),
            editor: true
        }, {
            id: 'status',
            dataIndex: 'status',
            width: 100,
            sortable: true,
            header: this.app.i18n._('Status'),
            renderer: this.renderAttenderStatus.createDelegate(this),
            editor: new Ext.form.ComboBox({
                blurOnSelect  : true,
                expandOnFocus : true,
                mode          : 'local',
                displayField  : 'status_name',
                valueField    : 'id',
                store         : Tine.Calendar.Model.Attender.getAttendeeStatusStore()
            })
        }];
    },
    
    onAfterAttenderEdit: function(o) {
        switch (o.field) {
            case 'user_id' :
                // detect duplicate entry
                var isDuplicate = false;
                this.store.each(function(attender) {
                    if (o.record.getUserId() == attender.getUserId()
                            && o.record.get('user_type') == attender.get('user_type')
                            && o.record != attender) {
                        var row = this.getView().getRow(this.store.indexOf(attender));
                        Ext.fly(row).highlight();
                        isDuplicate = true;
                        return false;
                    }
                }, this);
                
                if (isDuplicate) {
                    o.record.reject();
                    this.startEditing(o.row, o.column);
                } else if (o.value) {
                    var newAttender = new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 'new-' + Ext.id() );
                    this.store.add([newAttender]);
                    this.startEditing(o.row +1, o.column);
                }
                break;
                
            case 'user_type' :
                this.startEditing(o.row, o.column +1);
                break;
            
            case 'container_id':
                // check if displaycontainer of owner got changed
                if (o.record == this.eventOriginator) {
                    this.record.set('container_id', '');
                    this.record.set('container_id', o.record.get('displaycontainer_id'));
                }
                break;
        }
        
    },
    
    onBeforeAttenderEdit: function(o) {
        if (o.field == 'status') {
            // allow status setting if current user has editGrant to displaycontainer
            var dispContainer = o.record.get('displaycontainer_id');
            o.cancel = ! (dispContainer && dispContainer.account_grants && dispContainer.account_grants.editGrant);
            return;
        }
        
        if (o.field == 'displaycontainer_id') {
            if (! o.value || ! o.value.account_grants || ! o.value.account_grants.deleteGrant) {
                o.cancel = true;
            }
            return;
        }
        
        // for all other fields user need editGrant
        if (! this.record.get('editGrant')) {
            o.cancel = true;
            return;
        }
        
        // don't allow to set anything besides quantity for already set attendee
        if (o.record.get('user_id')) {
            o.cancel = true;
            if (o.field == 'quantity' && o.record.get('user_type') == 'resource') {
                o.cancel = false;
            }
            return;
        }
        
        if (o.field == 'user_id') {
            // switch editor
            var colModel = o.grid.getColumnModel();
            switch(o.record.get('user_type')) {
                case 'user' :
                colModel.config[o.column].setEditor(new Tine.Addressbook.SearchCombo({
                    blurOnSelect: true,
                    selectOnFocus: true,
                    renderAttenderName: this.renderAttenderName,
                    getValue: function() {
                        return this.selectedRecord ? this.selectedRecord.data : null;
                    }
                }));
                break;
                
                case 'group':
                colModel.config[o.column].setEditor(new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                    blurOnSelect: true,
                    recordClass: Tine.Tinebase.Model.Group,
                    getValue: function() {
                        return this.selectedRecord ? this.selectedRecord.data : null;
                    }
                }));
                break;
                
                case 'resource':
                colModel.config[o.column].setEditor(new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                    blurOnSelect: true,
                    recordClass: Tine.Calendar.Model.Resource,
                    getValue: function() {
                        return this.selectedRecord ? this.selectedRecord.data : null;
                    }
                }));
                break;
            }
            colModel.config[o.column].editor.selectedRecord = null;
        }
    },
    
    // NOTE: Ext docu seems to be wrong on arguments here
    onContextMenu: function(e, target) {
        e.preventDefault();
        var row = this.getView().findRowIndex(target);
        var attender = this.store.getAt(row);
        if (attender) {
            // don't delete 'add' row
            var attender = this.store.getAt(row);
            if (! attender.get('user_id')) {
                return;
            }
                        
            this.ctxMenu = new Ext.menu.Menu({
                items: [{
                    text: this.app.i18n._('Remove Attender'),
                    iconCls: 'action_delete',
                    scope: this,
                    disabled: !this.record.get('editGrant'),
                    handler: function() {
                        this.store.removeAt(row);
                    }
                    
                }]
            });
            this.ctxMenu.showAt(e.getXY());
        }
    },
    
    /**
     * loads this panel with data from given record
     * called by edit dialog when record is loaded
     * 
     * @param {Tine.Calendar.Model.Event} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        this.store.removeAll();
        var attendee = record.get('attendee');
        Ext.each(attendee, function(attender) {
            var record = new Tine.Calendar.Model.Attender(attender, attender.id);
            this.store.addSorted(record);
            
            if (attender.displaycontainer_id  && this.record.get('container_id') && attender.displaycontainer_id.id == this.record.get('container_id').id) {
                this.eventOriginator = record;
            }
        }, this);
        
        if (record.get('editGrant')) {
            this.store.add([new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 'new-' + Ext.id() )]);
        }
    },
    
    /**
     * Updates given record with data from this panel
     * called by edit dialog to get data
     * 
     * @param {Tine.Calendar.Model.Event} record
     */
    onRecordUpdate: function(record) {
        this.stopEditing(false);
        
        var attendee = [];
        this.store.each(function(attender) {
            var user_id = attender.get('user_id');
            if (user_id/* && user_id.id*/) {
                if (typeof user_id.get == 'function') {
                    attender.data.user_id = user_id.data;
                }
                
               attendee.push(attender.data);
            }
        }, this);
        
        record.set('attendee', '');
        record.set('attendee', attendee);
    },
    
    onKeyDown: function(e) {
        switch(e.getKey()) {
            
            case e.DELETE: 
                if (this.record.get('editGrant')) {
                    var selection = this.getSelectionModel().getSelectedCell();
                    
                    if (selection) {
                        var row = selection[0];
                        
                        // don't delete 'add' row
                        var attender = this.store.getAt(row);
                        if (! attender.get('user_id')) {
                            return;
                        }

                        this.store.removeAt(row);
                    }
                }
                break;
        }
    },
    
    renderAttenderName: function(name, metaData, record) {
        if (name) {
            var type = record ? record.get('user_type') : 'user';
            return this['renderAttender' + Ext.util.Format.capitalize(type) + 'Name'].apply(this, arguments);
        }
        
        // add new user:
        if (arguments[1]) {
            arguments[1].css = 'x-form-empty-field';
            return this.app.i18n._('Click here to invite another attender...');
        }
    },
    
    renderAttenderUserName: function(name) {
        if (typeof name.get == 'function' && name.get('n_fn')) {
            return Ext.util.Format.htmlEncode(name.get('n_fn'));
        }
        if (name.n_fn) {
            return Ext.util.Format.htmlEncode(name.n_fn);
        }
        if (name.accountDisplayName) {
            return Ext.util.Format.htmlEncode(name.accountDisplayName);
        }
        if (Ext.isString(name)) {
            return Ext.util.Format.htmlEncode(name);
        }
        // NOTE: this fn gets also called from other scopes
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('No Information');
    },
    
    renderAttenderGroupmemberName: function(name) {
        var name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderUserName.apply(this, arguments);
        return name + ' ' + Tine.Tinebase.appMgr.get('Calendar').i18n._('(as a group member)');
    },
    
    renderAttenderGroupName: function(name) {
        if (typeof name.getTitle == 'function') {
            return Ext.util.Format.htmlEncode(name.getTitle());
        }
        if (name.name) {
            return Ext.util.Format.htmlEncode(name.name);
        }
        if (Ext.isString(name)) {
            return Ext.util.Format.htmlEncode(name);
        }
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('No Information');
    },
    
    renderAttenderResourceName: function(name) {
        if (typeof name.getTitle == 'function') {
            return Ext.util.Format.htmlEncode(name.getTitle());
        }
        if (name.name) {
            return Ext.util.Format.htmlEncode(name.name);
        }
        if (Ext.isString(name)) {
            return Ext.util.Format.htmlEncode(name);
        }
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('No Information');
    },
    
    
    renderAttenderDispContainer: function(displaycontainer_id, metadata, attender) {
        metadata.attr = 'style = "overflow: none;"';
        
        if (displaycontainer_id) {
            if (displaycontainer_id.name) {
                return Ext.util.Format.htmlEncode(displaycontainer_id.name).replace(/ /g,"&nbsp;");
            } else {
                metadata.css = 'x-form-empty-field';
                return this.app.i18n._('No Information');
            }
        }
    },
    
    renderAttenderQuantity: function(quantity, metadata, attender) {
        return quantity > 1 ? quantity : '';
    },
    
    renderAttenderRole: function(role) {
        switch (role) {
            case 'REQ':
                return this.app.i18n._('Required');
                break;
            case 'OPT':
                return this.app.i18n._('Optional');
                break;
            default:
                return Ext.util.Format.htmlEncode(this.app.i18n._hidden(role));
                break;
        }
    },
    
    renderAttenderStatus: function(status, metadata, attender) {
        if (! attender.get('user_id')) {
            return '';
        }
        
        if (attender.get('displaycontainer_id')) {
            metadata.attr = 'style = "cursor:pointer;"';
        } else {
            metadata.css = 'x-form-empty-field';
        }
        
        var il8n = Tine.Tinebase.appMgr.get('Calendar').i18n;
        switch (status) {
            case 'NEEDS-ACTION':
                return il8n._('No response');
                break;
            case 'ACCEPTED':
                return il8n._('Accepted');
                break;
            case 'DECLINED':
                return il8n._('Declined');
                break;
            case 'TENTATIVE':
                return il8n._('Tentative');
                break;
            default:
                return Ext.util.Format.htmlEncode(il8n._hidden(status));
                break;
        }
    },
    
    renderAttenderType: function(type, metadata, attender) {
        switch (type) {
            case 'user':
                metadata.css = 'renderer_accountUserIcon';
                break;
            case 'group':
                metadata.css = 'renderer_accountGroupIcon';
                break;
            default:
                metadata.css = 'cal-attendee-type-' + type;
                break;
        }
        return '';
    }
});