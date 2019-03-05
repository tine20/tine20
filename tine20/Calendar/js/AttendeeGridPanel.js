/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

require('./AttendeePickerCombo');
require('./ResourcePickerCombo');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.AttendeeGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    autoExpandColumn: 'user_id',
    clicksToEdit: 1,
    enableHdMenu: false,
    canonicalName: 'AttendeeGrid',
    
    /**
     * @cfg defaut text for new attendee combo
     * i18n._('Click here to invite another attender...')
     */
    addNewAttendeeText: 'Click here to invite another attender...',
    
    /**
     * @cfg {Boolean} showGroupMemberType
     * show user_type groupmember in type selection
     */
    showMemberOfType: false,
    
    /**
     * @cfg {Boolean} showNamesOnly
     * true to only show types and names in the list
     */
    showNamesOnly: false,

    /**
     * @cfg {Boolean} showAttendeeRole
     * true to show roles in the list
     */
    showAttendeeRole: false,


    /**
     * @cfg {String} defaultAttendeeRole
     * attendee role for new attendee row
     */
    defaultAttendeeRole: 'REQ',

    /**
     * @cfg {Bool} requireFreeBusyGrantOnly
     *  freebusy grant is sufficient to find ressource (instead of inviteGrant)
     */
    requireFreeBusyGrantOnly: null,

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

    attendeeTypeCombo: null,
    
    /**
     * grid panel phone hook for calling attendee
     * 
     * @type Tine.Phone.AddressbookGridPanelHook
     */
    phoneHook: null,
    
    stateful: true,
    stateId: 'cal-attendeegridpanel',
    
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Calendar');
        
        this.currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        this.title = this.hasOwnProperty('title') ? this.title : this.app.i18n._('Attendee');
        this.plugins = this.plugins || [];
        if (! this.showNamesOnly) {
            this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        }
        
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Calendar.Model.Attender.getFieldDefinitions().concat('sort'),
            sortInfo: {field: 'user_id', direction: 'ASC'},
            sortData : function(f, direction){
                direction = direction || 'ASC';
                var st = this.fields.get(f).sortType;
                var fn = function(r1, r2){
                    // make sure new-attendee line is on the bottom
                    if (!r1.data.user_id) return direction == 'ASC';
                    if (!r2.data.user_id) return direction != 'ASC';
                    
                    var v1 = st(r1.data[f]), v2 = st(r2.data[f]);
                    return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
                };
                this.data.sort(direction, fn);
                if(this.snapshot && this.snapshot != this.data){
                    this.snapshot.sort(direction, fn);
                }
            }
        });
        
        this.on('beforeedit', this.onBeforeAttenderEdit, this);
        this.on('afteredit', this.onAfterAttenderEdit, this);
        this.on('rowcontextmenu', this.onRowContextMenu, this);
        this.addEvents('beforenewattendee');

        this.initColumns();
        
        this.mon(Ext.getBody(), 'click', this.stopEditingIf, this);
        
        this.viewConfig = {
            getRowClass: this.getRowClass
        };
        
        Tine.Calendar.AttendeeGridPanel.superclass.initComponent.call(this);

        this.initPhoneGridPanelHook();
    },
    
    initColumns: function() {
        var attendeeTypes = new Ext.data.ArrayStore({
            fields: ['name', 'value'],
            idIndex: 0 // id for each record will be the first element
        });
        
        attendeeTypes.loadData([
            ['any',      '...'                     ],
            ['user',     this.app.i18n._('User')   ],
            ['group',    this.app.i18n._('Group')  ],
            ['resource', this.app.i18n._('Resource')]
        ].concat(this.showMemberOfType ? [['memberOf', this.app.i18n._('Member of group')  ]] : []));
        
        this.attendeeTypeCombo = new Ext.form.ComboBox({
            blurOnSelect  : true,
            expandOnFocus : true,
            listWidth     : 100,
            mode          : 'local',
            valueField: 'name',
            displayField: 'value',
            store         : attendeeTypes
        });
        
        this.columns = [{
            id: 'role',
            dataIndex: 'role',
            width: 70,
            sortable: true,
            hidden: !this.showAttendeeRole || this.showNamesOnly,
            header: this.app.i18n._('Role'),
            renderer: this.renderAttenderRole.createDelegate(this),
            editor: {
                xtype: 'widget-keyfieldcombo',
                app:   'Calendar',
                keyFieldName: 'attendeeRoles'
            }
        }, {
            id: 'displaycontainer_id',
            dataIndex: 'displaycontainer_id',
            width: 200,
            sortable: false,
            hidden: this.showNamesOnly || true,
            header: i18n._hidden('Saved in'),
            tooltip: this.app.i18n._('This is the calendar where the attender has saved this event in'),
            renderer: this.renderAttenderDispContainer.createDelegate(this),
            // disable for the moment, as updating calendarSelectWidget is not working in both directions
            editor2: new Tine.widgets.container.SelectionComboBox({
                blurOnSelect: true,
                selectOnFocus: true,
                appName: 'Calendar',
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
            width: 50,
            sortable: true,
            header: this.app.i18n._('Type'),
            tooltip: this.app.i18n._('Click icon to change'),
            renderer: this.renderAttenderType.createDelegate(this),
            editor: this.attendeeTypeCombo
        }, {
            id: 'user_id',
            dataIndex: 'user_id',
            width: 300,
            sortable: true,
            header: this.app.i18n._('Name'),
            renderer: this.renderAttenderName.createDelegate(this),
            editor: true
        }, {
            id: 'fbInfo',
            dataIndex: 'fbInfo',
            width: 20,
            hidden: this.showNamesOnly,
            header: '&nbsp',
            tooltip: this.app.i18n._('Availability of Attendee'),
            fixed: true,
            sortable: false,
            renderer: function(v, m, r) {
                // NOTE: already encoded
                return r.get('user_id') ? v : '';
            }
        }, {
            id: 'status',
            dataIndex: 'status',
            width: 100,
            sortable: true,
            header: this.app.i18n._('Status'),
            hidden: this.showNamesOnly,
            renderer: this.renderAttenderStatus.createDelegate(this),
            editor: {
                xtype: 'widget-keyfieldcombo',
                app:   'Calendar',
                keyFieldName: 'attendeeStatus'
            }
        }];
    },

    onEditComplete: function(ed, value, startValue) {
        var _ = window.lodash,
            attendeeData = _.get(ed, 'field.selectedRecord.data.user_id'),
            type = _.get(ed, 'field.selectedRecord.data.user_type'),
            fbInfo = _.get(ed, 'field.selectedRecord.data.fbInfo');

        // attendeePickerCombo
        if (attendeeData && type) {
            if (this.showMemberOfType && 'group' == type) {
                var row = ed.row,
                    col = ed.col,
                    selectedRecord = _.get(ed, 'field.selectedRecord');

                Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                    title: this.app.i18n._('Whole Group or each Member of Group'),
                    questionText: this.app.i18n._('Choose "Group" to filter for the whole group itself. Choose "Member of Group" to filter for each member of the group'),
                    height: 170,
                    scope: this,
                    options: [
                        {text: this.app.i18n._('Group'), name: 'sel_group'},
                        {text: this.app.i18n._('Member of Group'), name: 'sel_memberOf'}
                    ],

                    handler: function(option) {
                        this.startEditing(row, col);
                        selectedRecord.set('user_type', option);
                        selectedRecord.groupType = option;
                        this.activeEditor.field.selectedRecord = selectedRecord;
                        this.stopEditing();
                    }
                });

                // abort normal flow
                value = startValue;
            } else {
                value = attendeeData;
                ed.record.set('user_type', type.replace(/^sel_/, ''));
                ed.record.set('fbInfo', fbInfo);
                ed.record.commit();
            }
        }

        Tine.Calendar.AttendeeGridPanel.superclass.onEditComplete.call(this, ed, value, startValue);
    },

    onAfterAttenderEdit: function(o) {
        switch (o.field) {
            case 'user_id' :
                // detect duplicate entry
                // TODO detect duplicate emails, too 
                var isDuplicate = false;
                this.store.each(function(attender) {
                    if (o.record.getUserId() == attender.getUserId()
                            && o.record.get('user_type') == attender.get('user_type')
                            && o.record != attender) {
                        attender.set('checked', true);
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
                    // set status authkey for contacts and recources so user can edit status directly
                    // NOTE: we can't compute if the user has editgrant to the displaycontainer of an account here!
                    //       WELL we could add the info to search attendee somehow
                    if (   (o.record.get('user_type') == 'user' && ! o.value.account_id )
                        || (o.record.get('user_type') == 'resource' && o.record.get('user_id') && o.record.get('user_id').container_id && o.record.get('user_id').container_id.account_grants && o.record.get('user_id').container_id.account_grants.editGrant)) {
                        o.record.set('status_authkey', Tine.Tinebase.data.Record.generateUID());
                    }
                    
                    o.record.explicitlyAdded = true;
                    o.record.set('checked', true);
                    
                    // Set status if the resource has a specific default status
                    if (o.record.get('user_type') == 'resource' && o.record.get('user_id') && o.record.get('user_id').status) {
                        o.record.set('status', o.record.get('user_id').status);
                    }

                    // resolve groupmembers
                    if (o.record.get('user_type') == 'group' && !this.showMemberOfType) {
                        this.resolveListMembers(o.record.get('user_id'));
                    } else {
                        this.addNewAttendeeRow();
                        this.startEditing(o.row + 1, o.column);
                    }
                }
                break;
                
            case 'user_type':
                this.startEditing(o.row, this.getColumnModel().getIndexById('user_id'));
                break;

            case 'role':
                if (o.row == this.store.getCount()-1) {
                    this.setDefaultAttendeeRole(o.record.get('role'));
                    this.startEditing(o.row, this.getColumnModel().getIndexById('user_id'));
                }

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

    resolveListMembers: function() {
        if (this.showMemberOfType) return;

        var _ = window.lodash,
            members = Tine.Calendar.Model.Attender.getAttendeeStore.getData(this.store),
            fbInfoUpdate = [];

        var mask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Loading Groupmembers...")});
        mask.show();

        Tine.Calendar.resolveGroupMembers(members, function(attendeesData) {
            var attendees = Tine.Calendar.Model.Attender.getAttendeeStore(attendeesData);

            // remove not longer existing attendee
            this.store.each(function(attendee) {
                if (! Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendees, attendee)) {
                    this.store.remove(attendee);
                }
            });

            // add new attendee
            attendees.each(function(attendee) {
                if (! Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.store, attendee)) {
                    attendee.set('role', this.defaultAttendeeRole);
                    this.fireEvent('beforenewattendee', this, attendee, this.record);
                    this.store.add([attendee]);
                    fbInfoUpdate.push(attendee.id);
                }
            }, this);

            this.updateFreeBusyInfo(fbInfoUpdate);
            mask.hide();
            this.addNewAttendeeRow();
        }, this);
    },

    onBeforeAttenderEdit: function(o) {
        if (o.field == 'status') {
            // allow status setting if status authkey is present
            o.cancel = ! o.record.get('status_authkey');
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
        
        // don't allow to set anything besides quantity and role for already set attendee
        if (o.record.get('user_id')) {
            o.cancel = true;
            if (o.field == 'quantity' && o.record.get('user_type') == 'resource') {
                o.cancel = false;
            }
            if (o.field == 'role') {
                o.cancel = false;
            }
            return;
        }
        
        if (o.field == 'user_id') {
            // switch editor
            var colModel = o.grid.getColumnModel(),
                type = o.record.get('user_type');

            type = type == 'memberOf' ? 'group' : type;

            var attendeePickerCombo = new Tine.Calendar.AttendeePickerCombo({
                minListWidth: 370,
                blurOnSelect: true,
                eventRecord: this.record,
                requireFreeBusyGrantOnly: this.requireFreeBusyGrantOnly,
                additionalFilters: type != 'any' ? [{field: 'type', operator: 'oneof', value: [type]}] : null
            });

            this.fireEvent('beforenewattendeepickercombo', this, attendeePickerCombo, o);
            colModel.config[o.column].setEditor(attendeePickerCombo);


            
            colModel.config[o.column].editor.selectedRecord = null;
        }
    },
    
    /**
     * give new attendee an extra cls
     */
    getRowClass: function(record, index) {
        if (! record.get('user_id')) {
            return 'x-cal-add-attendee-row';
        }
    },
    
    /**
     * stop editing if user clicks else where
     * 
     * FIXME this breaks the paging in search combos, maybe we should check if search combo paging buttons are clicked, too
     */
    stopEditingIf: function(e) {
        if (! e.within(this.getGridEl())) {
            //this.stopEditing();
        }
    },

    onBeforeRemoveAttendee: function() {
        return true;
    },

    onRowContextMenu: function(grid, row, e) {
        e.stopEvent();

        var me = this,
            attender = this.store.getAt(row),
            type = attender.get('user_type');

        if (attender && ! this.disabled) {
            // don't delete 'add' row
            var attender = this.store.getAt(row);
            if (! attender.get('user_id')) {
                return;
            }
            
            // select name cell
            if (Ext.isFunction(this.getSelectionModel().select)) {
                this.getSelectionModel().select(row, 3);
            } else {
                // west panel attendee filter grid
                this.getSelectionModel().selectRow(row);
            }
            
            Tine.log.debug('onContextMenu - attender:');
            Tine.log.debug(attender);
            
            var items = [{
                text: this.app.i18n._('Remove Attender'),
                iconCls: 'action_delete',
                scope: this,
                disabled: ! this.record.get('editGrant') || type == 'groupmember',
                handler: function() {
                    if(this.onBeforeRemoveAttendee(attender, type) === false) {
                        return false;
                    }
                    
                    this.store.remove(attender);
                    if (type == 'group' && !this.showMemberOfType) {
                        this.resolveListMembers()
                    }
                }
            }, '-'];

            var felamimailApp = Tine.Tinebase.appMgr.get('Felamimail');
            if (felamimailApp && attender.get('user_type') == 'user') {
                Tine.log.debug('Adding email compose hook for attender');
                items = items.concat(new Ext.Action({
                    text: felamimailApp.i18n._('Compose email'),
                    iconCls: felamimailApp.getIconCls(),
                    disabled: false,
                    scope: this,
                    handler: function() {
                        var _ = window.lodash;
                        var email = Tine.Felamimail.getEmailStringFromContact(new Tine.Addressbook.Model.Contact(attender.get('user_id')));
                        var record = new Tine.Felamimail.Model.Message({
                            subject: _.get(this.record, 'data.poll.name', _.get(this.record, 'data.summary', '') , ''),
                            body: this.record.hasPoll() ? String.format(this.app.i18n._('Poll URL: {0}'), this.record.getPollUrl()) : '',
                            massMailingFlag: this.record.hasPoll(),
                            to: [email]
                        }, 0);
                        var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                            record: record
                        });
                    }
                }));
            }

            if (attender.get('user_type') == 'resource') {
                Tine.log.debug('Adding resource hook for attender');
                var resourceId = attender.get('user_id').id,
                    resource = new Tine.Calendar.Model.Resource(attender.get('user_id'), resourceId),
                    grants = lodash.get(resource, 'data.container_id.account_grants', {});

                items = items.concat(new Ext.Action({
                    text: grants.resourceEditGrant || Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources') ?
                        this.app.i18n._('Edit Resource') :
                        this.app.i18n._('View Resource'),
                    iconCls: 'cal-resource',
                    scope: this,
                    handler: function() {
                        Tine.Calendar.ResourceEditDialog.openWindow({record: resource});
                    }
                }));

                var exportAction = Tine.widgets.exportAction.getExportButton(
                    Tine.Calendar.Model.Resource, {
                        getExportOptions: function() {
                            var options = {
                                recordData: attender.get('user_id')
                            };

                            // do we have a 'real' event?
                            if (me.record.data.dtstart) {
                                options.additionalRecords = {
                                    Event: {
                                        model: 'Calendar_Model_Event',
                                            recordData: me.record.data
                                    }
                                };
                            }
                            return options;
                        }
                    },
                    Tine.widgets.exportAction.SCOPE_SINGLE
                );

                items = items.concat(exportAction);
            }

            var plugins = [{
                ptype: 'ux.itemregistry',
                key:   'Attendee-GridPanel-ContextMenu'
            }, {
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }];

            // add phone call action via item registry
            if (this.phoneHook && attender.get('user_type') == 'user') {
                var contact = new Tine.Addressbook.Model.Contact(attender.get('user_id'));
                this.phoneHook.setContactAndUpdateAction(contact);
            }
            
            Tine.log.debug(items);
            
            this.ctxMenu = new Ext.menu.Menu({
                items: items,
                plugins: plugins,
                listeners: {
                    scope: this,
                    hide: function() {
                        this.getSelectionModel().clearSelections();
                    }
                }
            });

            var actionUpdater = new Tine.widgets.ActionUpdater({
                evalGrants: false
            });
            actionUpdater.addAction(exportAction);
            actionUpdater.updateActions([resource]);

            this.ctxMenu.showAt(e.getXY());
        }
    },
    
    /**
     * init phone grid panel hook if Phone app is available
     */
    initPhoneGridPanelHook: function() {
        var phoneApp = Tine.Tinebase.appMgr.get('Phone');
        if (phoneApp) {
            Tine.log.debug('Adding Phone call hook');
            this.phoneHook = new Tine.Phone.AddressbookGridPanelHook({
                app: phoneApp,
                keyPrefix: 'Attendee',
                useActionUpdater: false
            });
        }
    },
    
    /**
     * loads this panel with data from given record
     * called by edit dialog when record is loaded
     * 
     * @param {Tine.Calendar.Model.Event} record
     * @param Array addAttendee
     */
    onRecordLoad: function(record) {
        this.record = record;
        this.store.removeAll();
        var attendee = record.get('attendee'),
            resolveListMembers = false;

        Ext.each(attendee, function(attender) {
            var record = new Tine.Calendar.Model.Attender(attender, attender.id);
            this.store.addSorted(record);
            
            if (attender.displaycontainer_id  && this.record.get('container_id') && attender.displaycontainer_id.id == this.record.get('container_id').id) {
                this.eventOriginator = record;
            }

            if (String(record.get('user_type')).match(/^group/)) {
                resolveListMembers = true;
            }
        }, this);

        this.updateFreeBusyInfo();

        if (resolveListMembers) {
            this.resolveListMembers();
        }

        else if (record.get('editGrant')) {
            this.addNewAttendeeRow();
        }
    },

    updateFreeBusyInfo: function(force) {
        if (this.showMemberOfType) return;

        var _ = window.lodash,
            schedulingInfo = this.record.getSchedulingData(),
            encodedSchedulingInfo = Ext.encode(schedulingInfo);

        if (encodedSchedulingInfo == this.encodedSchedulingInfo && !force) {
            return;
        }

        if (! schedulingInfo.dtend || ! schedulingInfo.dtstart) {
            // dtend & dtstart are required
            return;
        }

        // @TODO have load spinner?
        this.encodedSchedulingInfo = encodedSchedulingInfo;

        // clear state
        this.store.each(function(attendee) {
            if (Ext.isArray(force) && force.indexOf(attendee.id) < 0) {
                return;
            }

            attendee.set('fbInfo', '...');
            attendee.commit();
        }, this);

        Tine.Calendar.getFreeBusyInfo(
            Tine.Calendar.Model.Attender.getAttendeeStore.getData(this.store),
            [schedulingInfo],
            [this.record.get('uid')],
            function(freeBusyData) {
                // outdated data
                if (encodedSchedulingInfo != this.encodedSchedulingInfo) return;

                var fbInfo = new Tine.Calendar.FreeBusyInfo(_.values(freeBusyData)[0]);

                this.store.each(function(attendee) {
                    attendee.set('fbInfo', fbInfo.getStateOfAttendee(attendee, this.record));
                    attendee.commit();
                }, this);

        }, this);
    },

    // Add new attendee
    addNewAttendeeRow: function() {
        this.newAttendee = new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 'new-' + Ext.id());
        this.newAttendee.set('role', this.defaultAttendeeRole);
        this.fireEvent('beforenewattendee', this, this.newAttendee, this.record);
        this.store.add([this.newAttendee]);
    },

    setDefaultAttendeeRole:function(role) {
        this.defaultAttendeeRole = role;
        if (this.newAttendee) {
            this.newAttendee.set('role', role);
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

        this.updateFreeBusyInfo();
        Tine.Calendar.Model.Attender.getAttendeeStore.getData(this.store, record);
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
            var type = record ? record.get('user_type') : 'user',
                fn = this['renderAttender' + Ext.util.Format.capitalize(type) + 'Name'];

            return fn ? fn.apply(this, arguments): '';
        }
        
        // add new user:
        if (arguments[1]) {
            arguments[1].css = 'x-form-empty-field';
            return this.app.i18n._(this.addNewAttendeeText);
        }
    },

    /**
     * render attender user name
     *
     * @param name
     * @returns {*}
     */
    renderAttenderUserName: function(name) {
        name = name || "";
        var result = "",
            email = "";

        if (typeof name.get == 'function' && name.get('n_fileas')) {
            result = name.get('n_fileas');
        } else if (name.n_fileas) {
            result = name.n_fileas;
        } else if (name.accountDisplayName) {
            result = name.accountDisplayName;
        } else if (Ext.isString(name) && ! name.match('^[0-9a-f-]{40,}$') && ! parseInt(name, 10)) {
            // how to detect hash/string ids
            result = name;
        }

        // add email address if available
        // need to create a "dummy" app to call featureEnabled()
        // TODO: should be improved
        var tinebaseApp = new Tine.Tinebase.Application({
            appName: 'Tinebase'
        });
        if (tinebaseApp.featureEnabled('featureShowAccountEmail')) {
            if (typeof name.getPreferredEmail == 'function') {
                email = name.getPreferredEmail();
            } else if (name.email) {
                email = name.email;
            } else if (name.accountEmailAddress) {
                email = name.accountEmailAddress;
            }
            if (email !== '') {
                result += ' (' + email + ')';
            }
        }

        if (result === '') {
            result = Tine.Tinebase.appMgr.get('Calendar').i18n._('No Information')
        } else {
            result = Ext.util.Format.htmlEncode(result)
        }

        // NOTE: this fn gets also called from other scopes
        return result;
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
    
    renderAttenderMemberofName: function(name) {
        return Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderGroupName.apply(this, arguments);
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
    
    renderAttenderRole: function(role, metadata, attender) {
        var i18n = Tine.Tinebase.appMgr.get('Calendar').i18n,
            renderer = Tine.widgets.grid.RendererManager.get('Calendar', 'Attender', 'role', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);

        if (this.record && this.record.get('editGrant')) {
            metadata.attr = 'style = "cursor:pointer;"';
        } else {
            metadata.css = 'x-form-empty-field';
        }
        
        return renderer(role);
    },
    
    renderAttenderStatus: function(status, metadata, attender) {
        var i18n = Tine.Tinebase.appMgr.get('Calendar').i18n,
            renderer = Tine.widgets.grid.RendererManager.get('Calendar', 'Attender', 'status', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
        
        if (! attender.get('user_id')) {
            return '';
        }
        
        if (attender.get('status_authkey')) {
            metadata.attr = 'style = "cursor:pointer;"';
        } else {
            metadata.css = 'x-form-empty-field';
        }
        
        return renderer(status);
    },
    
    renderAttenderType: function(type, metadata, attender) {
        metadata.css = 'tine-grid-cell-no-dirty';
        var cssClass = 'tine-grid-row-action-icon ',
            qtipText =  '',
            userId = attender.get('user_id'),
            hasAccount = userId && ((userId.get && userId.get('account_id')) || userId.account_id);
            
        switch (type) {
            case 'user':
                cssClass += hasAccount || ! userId ? 'renderer_typeAccountIcon' : 'renderer_typeContactIcon';
                qtipText = hasAccount || ! userId ? '' : Tine.Tinebase.appMgr.get('Calendar').i18n._('External Attendee');
                break;
            case 'group':
                cssClass += 'renderer_accountGroupIcon';
                break;
            default:
                cssClass += 'cal-attendee-type-' + type;
                break;
        }
        
        var qtip = qtipText ? 'ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtipText) + '" ': '';
        
        var result = '<div ' + qtip + 'style="background-position:0px;" class="' + cssClass + '">&#160</div>';
        
        if (! attender.get('user_id')) {
            result = Tine.Tinebase.common.cellEditorHintRenderer(result);
        }
        
        return result;
    },

    /**
     * disable contents not panel
     */
    setDisabled: function(v) {
        if (v) {
            // remove "add new attender" row
            this.store.filterBy(function(r) {return ! (r.id && r.id.match(/^new-/))});
        } else {
            this.store.clearFilter();
        }
    }
});
