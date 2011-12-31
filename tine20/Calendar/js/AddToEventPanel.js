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
    
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },  
    
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
       
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    isValid: function() {
        
        var valid = true;
        
        if(this.searchBox.getValue() == '') {
            this.searchBox.markInvalid(this.app.i18n._('Please choose the Event to add the contacts to'));
            valid = false;
        }
          
        return valid;
    },
    
    onUpdate: function() {

        if(this.isValid()) {    
            var recordId = this.searchBox.getValue();
                e = this.searchBox.store.getById(recordId),
                attendee = e.get('attendee'); 
        
            Ext.each(this.attendee, function(contact) {
                attendee.push(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                    user_id: contact
                }));
            });

            var window = Tine.Calendar.EventEditDialog.openWindow({recordId: recordId, record: e, attendee: attendee});
        
            window.on('close', function() {
                    this.onCancel();
            },this);   
        }
    },
    
    getFormItems : function() {
                
        this.searchBox = new Tine.Calendar.SearchCombo({});

        this.searchBox.on('filterupdate', function() {
            this.store.removeAll();
            this.store.load();
        });
        
        this.datePicker = new Ext.DatePicker({
            plugins: 'monthPickerPlugin',
            width: 177,
            showToday: false,
            listeners: {
                scope: this,
                change: this.updateSearchBox
            }
        });
                
        return {
            border : false,
            frame : true,
            layout : 'form',

            items : [ {
                region : 'center',
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                }

            }, this.datePicker, this.searchBox]
        };
    },
    
    updateSearchBox: function() {
        var year = this.datePicker.activeDate.getYear() + 1900,
            yearEnd = year,
            month = this.datePicker.activeDate.getMonth() + 1,
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
        title : Tine.Tinebase.appMgr.get('Calendar').i18n._('Choose Event'),
        width : 250,
        height : 150,
        contentPanelConstructor : 'Tine.Calendar.AddToEventPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};