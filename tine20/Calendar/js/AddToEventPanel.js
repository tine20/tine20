/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AddToEventPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.Calendar.AddToEventPanel = Ext.extend(Ext.FormPanel, {
    appName : 'Calendar',
    
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',    
    
    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    /**
     * init component
     */
    initComponent: function() {
        
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
            
        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: app: ', this.app);

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        
        // get items for this dialog
        this.items = this.getFormItems();

        
        Tine.Calendar.AddToEventPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : this.app.i18n._('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
        
        this.action_update = new Ext.Action({
            text : this.app.i18n._('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },
    
    /**
     * init buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },  
    
    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.Calendar.AddToEventPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onSend,
            scope : this
        } ]);

    },
       
    /**
     * closes the window
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    /**
     * checks validity and marks invalid fields
     * returns true on valid
     * @return boolean
     */
    isValid: function() {
        
        var valid = true;
        
        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Event to add the contacts to'));
            valid = false;
        }
          
        return valid;
    },
    
    /**
     * save record and close window
     */
    onUpdate: function() {

        if(this.isValid()) {    
            var recordId = this.searchBox.getValue(),
                e = this.searchBox.store.getById(recordId),
                ms = this.app.getMainScreen(),
                cp = ms.getCenterPanel(),
                role = this.chooseRoleBox.getValue(),
                status = this.chooseStatusBox.getValue();

            for (var index = 0; index < this.attendee.length; index++) {
                this.attendee[index].role = role;
                this.attendee[index].status = status;
            }
                
            var window = Tine.Calendar.EventEditDialog.openWindow({
                record: Ext.util.JSON.encode(e.data),
                recordId: e.data.id,
                attendee: Ext.util.JSON.encode(this.attendee),
                listeners: {
                    scope: cp,
                    update: function (eventJson) {

                        var updatedEvent = Tine.Calendar.backend.recordReader({responseText: eventJson});
                        updatedEvent.dirty = true;
                        updatedEvent.modified = {};
                        event.phantom = true;
                        
                        var panel = this.getCalendarPanel(this.activeView);
                        var store = panel.getStore();
                        
                        event = store.getById(event.id);
                        
                        store.replaceRecord(event, updatedEvent);
                        
                        this.onUpdateEvent(updatedEvent);
                    }
                }
            });

            window.on('close', function() {
                this.onCancel();
            }, this);   
        }
    },
    
    /**
     * create and return form items
     * @return Object
     */
    getFormItems : function() {
        this.searchBox = new Tine.Calendar.SearchCombo({});

        this.searchBox.on('filterupdate', function() {
            this.store.removeAll();
            this.store.load();
        });
       
        var startDate = new Date().clearTime(),
            store = new Ext.data.JsonStore({
                id: 'id',
                fields: Tine.Calendar.Model.Event,
                proxy: Tine.Calendar.backend,
                reader: new Ext.data.JsonReader({}),
                replaceRecord: function(o, n) {
                    var idx = this.indexOf(o);
                    this.remove(o);
                    this.insert(idx, n);
                }
            });
            
        this.datePicker = new Tine.Calendar.PagingToolbar({
            view: 'month',
            anchor: '100% 100%',
            store: store,
            dtStart: startDate,
            showReloadBtn: false,
            showTodayBtn: false,
            listeners: {
                scope: this,
                change: this.updateSearchBox,
                render: this.updateSearchBox
            }
        });
               
        var rrecords = [];

        Ext.each(this.app.getRegistry().get('config')['attendeeRoles'].value.records, function(el) {
            var label = el.i18nValue ? el.i18nValue : el.value;
            rrecords.push([el.id, label]);
        });
        
        this.chooseRoleBox = new Ext.form.ComboBox({
            mode: 'local',
            emptyText: this.app.i18n._('Select Role'),
            fieldLabel: this.app.i18n._('Role'),
            valueField: 'id',
            displayField: 'value',
            forceSelection: true,
            anchor : '100% 100%',
            margins: '10px 10px',
            itemSelector: 'div.search-item',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td>',                   
                                '{values.value}',
                            '</td>',
                        '</td></tr>',
                    '</table>',
                '</div></tpl>'
            ),
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: ['id','value'],
                data: rrecords
            })
        });
        var srecords = [];
        Ext.each(this.app.getRegistry().get('config')['attendeeStatus'].value.records, function(el) {
            var label = el.i18nValue ? el.i18nValue : el.value;
            srecords.push([el.id, label]);
        });
        
        this.chooseStatusBox = new Ext.form.ComboBox({
            mode: 'local',
            emptyText: this.app.i18n._('Select Status'),
            fieldLabel: this.app.i18n._('Select Status'),
            valueField: 'id',
            displayField: 'value',
            forceSelection: true,
            anchor : '100% 100%',
            margins: '10px 10px',
            itemSelector: 'div.search-item',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td>',                   
                                '{values.value}',
                            '</td>',
                        '</td></tr>',
                    '</table>',
                '</div></tpl>'
            ),
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: ['id','value'],
                data: srecords
            })
        });
        
        
        return {
            border: false,
            frame:  false,
            layout: 'border',

            items: [{
                region: 'center',
                border: false,
                frame:  false,
                layout : {
                    align: 'stretch',
                    type:  'vbox'
                    },
                items: [ this.datePicker, {
                    layout:  'form',
                    margins: '10px 10px',
                    border:  false,
                    frame:   false,
                    items: [ this.searchBox, this.chooseRoleBox, this.chooseStatusBox ] 
                    }]

            }]
        };
    },
    
    /**
     * creates filter 
     */
    updateSearchBox: function() {
      
         var year = this.datePicker.getPeriod().until.getYear() + 1900,
             yearEnd = year,
             month = this.datePicker.getPeriod().until.getMonth(),
             monthEnd = month + 1;       
 
        if(monthEnd > 12) {
            monthEnd = monthEnd - 12;
            yearEnd = yearEnd + 1;
        }
        
        if (month < 10) month = '0' + month;
        if (monthEnd < 10) monthEnd = '0' + monthEnd;
        
        var from = year + '-' + month + '-01 00:00:00',
            until = yearEnd + '-' + monthEnd + '-01 00:00:00';

        var filter = {
            field: 'period',
            operator: 'within',
            value: {
                from: from,
                until: until
            }
        };

        this.searchBox.setFilter(filter); 
    }    
});

Tine.Calendar.AddToEventPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        title : String.format(Tine.Tinebase.appMgr.get('Calendar').i18n._('Adding {0} Attendee to event'), config.attendee.length),
        width : 210,
        height : 250,
        contentPanelConstructor : 'Tine.Calendar.AddToEventPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};