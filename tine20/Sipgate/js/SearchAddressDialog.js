/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version $Id: SearchAddressDialog.js 26 2011-05-03 01:42:01Z alex $
 * 
 */

Ext.namespace('Tine.Sipgate');

Tine.Sipgate.SearchAddressDialog = Ext.extend(Ext.FormPanel, {

    // private
    appName : 'Sipgate',
    
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',    
    
    bodyStyle : 'padding:5px',
    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,

    // private
    initComponent : function() {
        this.addEvents('cancel', 'send', 'close');
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

        Tine.Sipgate.SearchAddressDialog.superclass.initComponent.call(this);
    },

    /**
     * init actions
     */
    initActions : function() {
        this.action_send = new Ext.Action({
            text : this.app.i18n._('Add Number'),
            minWidth : 70,
            scope : this,
            handler : this.onSend,
            id : 'action-add-number',
            iconCls : 'action_AddNumber'
        });
        this.action_cancel = new Ext.Action({
            text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            id : 'action-cancel-addnumber',
            iconCls : 'action_cancel'
        });
        this.action_close = new Ext.Action({
            text : this.app.i18n._(this.cancelButtonText) ? this.app.i18n._(this.cancelButtonText) : _('Close'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            id : 'action-close-addnumber',
            iconCls : 'action_saveAndClose',
            // x-btn-text
            hidden : true
        });
    },

    initButtons : function() {

        this.fbar = [ '->', this.action_cancel, this.action_send, this.action_close ];

    },

    onRender : function(ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onSend,
            scope : this
        } ]);

    },

    onSend : function() {
        
        if(Tine.Addressbook.ContactEditDialog.openWindow({
                record: this.searchBox.selectedRecord,
                field: this.chooseField.getValue(),
                listeners : {
                    scope : this,
                    'load' : function(editdlg) {
                        
                        editdlg.record.set(this.chooseField.getValue(), this.number);
                    }
                }
            })) {
        
            
    this.onCancel();
            }
    },

    onCancel : function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    getFormItems : function() {
        
        this.fieldStore = new Ext.data.SimpleStore({
                  id: 'sipgate-addnumber-type',
                  fields: ['value', 'text'],
                  data : [
                      ['tel_work', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Phone')], 
                      ['tel_cell', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Mobile')],
                      ['tel_fax', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Fax')],
                      ['tel_car', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Car phone')],
                      ['tel_pager', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Pager')],
                      ['tel_home', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Phone (private)')],
                      ['tel_fax_home', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Fax (private)')],
                      ['tel_cell_private', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Mobile (private)')]
                  ]
             }),
        
        this.searchBox = new Tine.Addressbook.SearchCombo();
        this.searchBox.emptyText = this.app.i18n._('-- create new --');
         this.searchBox.fieldLabel = this.app.i18n._('Select existing contact or create a new one');
         
         this.searchBox.on('change', function() {

             Tine.log.debug(this.selectedRecord);
             var newStore = new Ext.data.SimpleStore({
                  id: 'sipgate-addnumber-type',
                  fields: ['value', 'text'],
                  data : [
                      ['tel_work', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Phone') + ((this.selectedRecord.data.tel_work) ? ' ' + this.selectedRecord.data.tel_work : '')], 
                      ['tel_cell', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Mobile') + ((this.selectedRecord.data.tel_cell) ? ' ' + this.selectedRecord.data.tel_cell : '')],
                      ['tel_fax', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Fax') + ((this.selectedRecord.data.tel_fax) ? ' ' + this.selectedRecord.data.tel_fax : '')],
                      ['tel_car', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Car phone') + ((this.selectedRecord.data.tel_car) ? ' ' + this.selectedRecord.data.tel_car : '')],
                      ['tel_pager', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Pager') + ((this.selectedRecord.data.tel_pager) ? ' ' + this.selectedRecord.data.tel_pager : '')],
                      ['tel_home', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Phone (private)') + ((this.selectedRecord.data.tel_home) ? ' ' + this.selectedRecord.data.tel_home : '')],
                      ['tel_fax_home', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Fax (private)') + ((this.selectedRecord.data.tel_fax_home) ? ' ' + this.selectedRecord.data.tel_fax_home : '')],
                      ['tel_cell_private', Tine.Tinebase.appMgr.get('Addressbook').i18n._('Mobile (private)') + ((this.selectedRecord.data.tel_cell_private) ? ' ' + this.selectedRecord.data.tel_cell_private : '')]
                  ]
             });
             Ext.getCmp('sipgate-number-field').bindStore(newStore);
         });
         
        this.chooseField = new Ext.form.ComboBox({
            autoSelect: true,
            allowBlank: false,
            lazyInit: false,
             name: 'number_field',
             editable: false,
             disableKeyFilter: true,
             forceSelection: true,
             id: 'sipgate-number-field',
             triggerAction: 'all',
             
             mode: 'local',
             store: this.fieldStore,
            fieldLabel: this.app.i18n._('Choose Device'),
             valueField: 'value',
             displayField: 'text'
        });
        
        return {
            border : false,
            id : 'sipgate-addnumber-form',
            frame : true,
            layout : 'form',
            items : [ {
                region : 'center',
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                }

            }, this.searchBox, this.chooseField ]
        };
    }

});

/**
 * Addressbook-Add Window (preselect)
 * 
 * @param {Object} number
 * @return {Ext.ux.Window}
 */
Tine.Sipgate.SearchAddressDialog.openWindow = function(config) {
    var window = Tine.WindowFactory.getExtWindow({
        title : String.format(Tine.Tinebase.appMgr.get('Sipgate').i18n._('Add number {0} to the addressbook'), config.number),
        width : 300,
        height : 200,
        contentPanelConstructor : 'Tine.Sipgate.SearchAddressDialog',
        contentPanelConstructorConfig : config
    });
    return window;
};
