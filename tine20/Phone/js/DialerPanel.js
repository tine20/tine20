/*
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Phone');

/**
 * dialer form
 * 
 * @todo use macaddress or description/name as display value?
 */
Tine.Phone.DialerPanel = Ext.extend(Ext.form.FormPanel, {
    
    id: 'dialerPanel',
    translation: null,
    
    // initial phone number
    number: null,
    
    // config settings
    defaults: {
        xtype: 'textfield',
        anchor: '100%',
        allowBlank: false
    },
    bodyStyle: 'padding:5px;',
    buttonAlign: 'right',
        
    phoneStore: null,
    linesStore: null,
    
    // private
    initComponent: function(){
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Phone');
        
        // set stores
        this.phoneStore = Tine.Phone.loadPhoneStore();
        
        //this.setLineStore(this.phoneStore.getAt(0).id);
        this.setLineStore(null);
        
        /***************** form fields *****************/
        
        this.items = [new Tine.widgets.customfields.CustomfieldsCombo({
                fieldLabel: this.translation._('Phone'),
                store: this.phoneStore,
                mode: 'local',
                editable: false,
                stateful: true,
                stateEvents: ['select'],
                displayField:'description',
                valueField: 'id',

                id: 'phoneId',
                name: 'phoneId',
                triggerAction: 'all',
                listeners: {
                    scope: this,
                    
                    // reload lines combo on change
                    select: function(combo, newValue, oldValue){
                        //console.log('set line store for ' + newValue.data.id);
                        this.setLineStore(newValue.data.id);
                    }
                }
            }),{
                xtype: 'combo',
                fieldLabel: this.translation._('Line'),
                name: 'lineId',
                displayField:'linenumber',
                valueField: 'id',
                mode: 'local',
                store: this.linesStore,
                triggerAction: 'all',
                disabled: (this.linesStore.getCount() <= 1)
            },{
                fieldLabel: this.translation._('Number'),
                name: 'phoneNumber'
            }
        ];
        
        /******************* action buttons ********************/
        
        // cancel action
        this.cancelAction = new Ext.Action({
            text: this.translation._('Cancel'),
            iconCls: 'action_cancel',
            handler : function(){
                Ext.getCmp('dialerWindow').close();
            }
        });
            
        // dial action
        this.dialAction = new Ext.Action({
            scope: this,
            text: this.translation._('Dial'),
            iconCls: 'action_DialNumber',
            handler : function(){
                var form = this.getForm();
                
                if (form.isValid()) {
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Phone.dialNumber',
                            number: form.findField('phoneNumber').getValue(),
                            phoneId: form.findField('phoneId').getValue(),
                            lineId: form.findField('lineId').getValue() 
                        },
                        success: function(_result, _request){
                            Ext.getCmp('dialerWindow').close();
                        },
                        failure: function(response, request){
                            var responseText = Ext.util.JSON.decode(response.responseText);
                            Ext.Msg.show({
                               title:   this.translation._('Error'),
                               msg:     (responseText.data.message) ? responseText.data.message : this.translation._('Not possible to dial.'),
                               icon:    Ext.MessageBox.ERROR,
                               buttons: Ext.Msg.OK
                            });
                        },
                        scope: this
                    });
                }
            }
        });

        this.buttons = [
            this.cancelAction,
            this.dialAction
        ];
        
        /************** other initialisation ****************/
        
        this.initMyFields.defer(300, this);

        Tine.Phone.DialerPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init form fields
     * 
     * @todo add prefered phone/line selections
     */
    initMyFields: function() {
        // focus number field or set initial value
        if (this.number != null) {
            this.getForm().findField('phoneNumber').setValue(this.number);
        } else {
            this.getForm().findField('phoneNumber').focus();
        }

        // get combos
        var phoneCombo = this.getForm().findField('phoneId');
        var lineCombo = this.getForm().findField('lineId');
        
        // select first combo values
        if(! phoneCombo.getState() && this.phoneStore.getAt(0)) {
            phoneCombo.setValue(this.phoneStore.getAt(0).id);
        } else {
            // update line store again (we need this, because it is changed when dlg is opened the second time)
            this.setLineStore(phoneCombo.getValue());
        }
        var firstLine = this.linesStore.getAt(0);
        if (firstLine) {
            this.getForm().findField('lineId').setValue(firstLine.id);
        }
    },
    
    /**
     * get values from phones store
     */
    setLineStore: function(phoneId) {
        
        if (this.linesStore == null) {
           this.linesStore = new Ext.data.Store({});
        } else {
            // empty store
            this.linesStore.removeAll();
        }
        
        var form = this.getForm();
        
        if (phoneId == null) {
            if (form) {
                phoneId = form.findField('phoneId').getValue();
            } else if (this.phoneStore.getAt(0)){
                // get first phone
                phoneId = this.phoneStore.getAt(0).id;
            } else {
                return;
            }
        }

        var phone = this.phoneStore.getById(phoneId);

        if(phone) {
            for(var i=0; i<phone.data.lines.length; i++) {
                var lineRecord = new Tine.Voipmanager.Model.SnomLine(phone.data.lines[i], phone.data.lines[i].id);
                this.linesStore.add(lineRecord);
            }
        }
        
        // disable lineCombo if only 1 line available
        if (form) {
            var lineCombo = form.findField('lineId');
            var count = this.linesStore.getCount();
            lineCombo.setDisabled(count <= 1);
            
            if (count) {
                // set first line
                lineCombo.setValue(this.linesStore.getAt(0).id);
            }
        }
    }
});