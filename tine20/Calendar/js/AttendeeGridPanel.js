/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Calendar');

Tine.Calendar.AttendeeGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    autoExpandColumn: 'user_id',
    clicksToEdit: 1,
    enableHdMenu: false,
    
    record: null,
    
    /**
     * @property {Ext.data.Store}
     */
    attendeeStore: null,
    
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Calendar');
        
        
        this.title = this.app.i18n._('Attendee');
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Calendar.Model.AttenderArray,
            sortInfo: {field: 'user_id', direction: 'ASC'}
        });
        
        this.on('beforeedit', this.onBeforeAttenderEdit, this);
        
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
        }, {
            id: 'quantity',
            dataIndex: 'quantity',
            width: 40,
            sortable: true,
            hidden: true,
            header: '&#160;',
            tooltip: this.app.i18n._('Quantity'),
            renderer: this.renderAttenderQuantity.createDelegate(this)
        }, {
            id: 'user_type',
            dataIndex: 'user_type',
            width: 20,
            sortable: true,
            resizable: false,
            header: '&#160;',
            tooltip: this.app.i18n._('Type'),
            renderer: this.renderAttenderType.createDelegate(this)
        }, {
            id: 'user_id',
            dataIndex: 'user_id',
            width: 300,
            sortable: true,
            header: this.app.i18n._('Name'),
            renderer: this.renderAttenderName.createDelegate(this),
            editor: new Tine.Addressbook.SearchCombo({
                blurOnSelect: true,/*
                setValue: function(name) {
                    if (name) {
                        if (typeof name.get == 'function' && name.get('n_fn')) {
                            name = name.get('n_fn');
                        } else if (name.accountDisplayName) {
                            name = name.accountDisplayName;
                        }
                    }
                    Tine.Addressbook.SearchCombo.prototype.setValue.call(this, name);
                },*/
                getValue: function() {
                    return this.selectedRecord;
                },
                listeners: {
                    scope: this,
                    select: this.onContactSelect
                }
            })
            //editor: new Tine.widgets.AccountpickerField({})
        }, {
            id: 'status',
            dataIndex: 'status',
            width: 100,
            sortable: true,
            header: this.app.i18n._('Status'),
            renderer: this.renderAttenderStatus.createDelegate(this),
            editor: new Ext.form.ComboBox({
                typeAhead     : false,
                triggerAction : 'all',
                lazyRender    : true,
                editable      : false,
                mode          : 'local',
                value         : null,
                forceSelection: true,
                store         : [
                    ['NEEDS-ACTION', ('No response')],
                    ['ACCEPTED',     ('Accepted')   ],
                    ['DECLINED',     ('Declined')   ],
                    ['TENTATIVE',    ('Tentative')  ]
                ], 
                listeners: {
                    scope: this,
                    select: function(field) {
                        field.blur(true);
                        field.fireEvent('blur', field);
                    }
                }
                
            })
        }];
    },
    
    onBeforeAttenderEdit: function(o) {
        if (o.field == 'status') {
            // status setting is not always allowed
            if (!o.record.getUserId() == Tine.Tinebase.registry.get('currentAccount').accountId && !o.record.get('status_authkey')) {
                o.cancel = true;
            }
            return;
        }
        
        if (! this.record.get('editGrant')) {
            o.cancel = true;
            return;
        }
        
        // don't allow to set anything besides quantity for persistent attendee
        if (o.record.get('id')) {
            o.cancel = true;
            if (o.field == 'quantity' && o.record.get('user_type') == 'resource') {
                o.cancel = false;
            }
            return;
        }
        
        if (o.field == 'user_id') {
            // here we are!
            console.log(this.renderAttenderName(o.value));
            o.value = this.renderAttenderName(o.value)
        }
    },
    onContactSelect: function(contact) {
        var name = contact;
        
        if (name) {
            if (typeof name.get == 'function' && name.get('n_fn')) {
                name = name.get('n_fn');
            } else if (name.accountDisplayName) {
                name = name.accountDisplayName;
            }
        }
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        this.store.removeAll(attendee);
        var attendee = record.get('attendee');
        Ext.each(attendee, function(attender) {
            this.store.add(new Tine.Calendar.Model.Attender(attender, attender.id));
        }, this);
        
        if (record.get('editGrant')) {
            this.store.add([new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 0)]);
        }
    },
    
    onRecordUpdate: function(record) {
        
        var attendee = [];
        this.store.each(function(attender) {
            var user_id = attender.getUserId();
            if (user_id) {
                var data = attender.data;
                data.user_id = user_id;
                attendee.push(data);
            }
        }, this);
        
        record.set('attendee', '');
        record.set('attendee', attendee);
    },
    
    renderAttenderName: function(name) {
        if (name) {
            if (typeof name.get == 'function' && name.get('n_fn')) {
                return name.get('n_fn');
            }
            if (name.accountDisplayName) {
                return name.accountDisplayName;
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
                return this.app.i18n._hidden(role);
                break;
        }
    },
    
    renderAttenderStatus: function(status, metadata, attender) {
        switch (status) {
            case 'NEEDS-ACTION':
                return this.app.i18n._('No response');
                break;
            case 'ACCEPTED':
                return this.app.i18n._('Accepted');
                break;
            case 'DECLINED':
                return this.app.i18n._('Declined');
                break;
            case 'TENTATIVE':
                return this.app.i18n._('Tentative');
                break;
            default:
                return this.app.i18n._hidden(status);
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