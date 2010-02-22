/*
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager');

/**
 * Account Picker GridPanel
 * 
 * @namespace   Tine.Voipmanager
 * @class       Tine.Voipmanager.CallForwardPanel
 * @extends     Ext.form.FormPanel
 * 
 * <p>Call Forward Form Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Voipmanager.CallForwardPanel
 */
Tine.Voipmanager.CallForwardPanel = Ext.extend(Ext.form.FormPanel, {

    /**
     * @cfg
     */
    border: false,
    frame: true,
    anchor: '100%',
                
    /**
     * @type Tine.Tinebase.data.Record
     */
    record: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.items = this.getFormItems();
        
        Tine.Voipmanager.CallForwardPanel.superclass.initComponent.call(this);
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * TODO add form items
     */
    getFormItems: function() {
        return [{
            title: this.app.i18n._('Forward immediately'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
            labelAlign: 'top',
            defaults: {
                anchor: '100%'
            },
            items: [{
                name: 'cfi_mode',
                xtype: 'combo',
                fieldLabel: this.app.i18n._('Mode'),
                typeAhead: true,
                triggerAction: 'all',
                lazyRender:true,
                triggerAction: 'all',
                allowBlank: false,
                editable: false,
                blurOnSelect: true,
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        // disable number if != number
                        this.getForm().findField('cfi_number').setDisabled(record.data.field1 != 'number');
                    }
                }
            }, {
                name: 'cfi_number',    
                fieldLabel: this.app.i18n._('Forward number'),
                xtype: 'textfield'
            }]
        }, {
            title: this.app.i18n._('Forward busy'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
            labelAlign: 'top',
            defaults: {
                anchor: '100%'
            },
            items: [{
                name: 'cfb_mode',
                xtype: 'combo',
                fieldLabel: this.app.i18n._('Mode'),
                typeAhead: true,
                triggerAction: 'all',
                lazyRender:true,
                triggerAction: 'all',
                allowBlank: false,
                editable: false,
                blurOnSelect: true,
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        this.getForm().findField('cfb_number').setDisabled(record.data.field1 != 'number');
                    }
                }
            }, {
                name: 'cfb_number',    
                fieldLabel: this.app.i18n._('Forward busy number'),
                xtype: 'textfield'
            }]
         }, {
            title: this.app.i18n._('Forward delayed'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
            labelAlign: 'top',
            defaults: {
                anchor: '100%'
            },
            items: [{
                name: 'cfd_mode',
                xtype: 'combo',
                fieldLabel: this.app.i18n._('Mode'),
                typeAhead: true,
                triggerAction: 'all',
                lazyRender:true,
                triggerAction: 'all',
                allowBlank: false,
                editable: false,
                blurOnSelect: true,
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        this.getForm().findField('cfd_number').setDisabled(record.data.field1 != 'number');
                        this.getForm().findField('cfd_time').setDisabled(record.data.field1 == 'off');
                    }
                }
            }, {
                name: 'cfd_number',    
                fieldLabel: this.app.i18n._('Forward delayed number'),
                xtype: 'textfield'
            }, {
                name: 'cfd_time',      
                fieldLabel: this.app.i18n._('Forward delay time (seconds)'),
                xtype: 'numberfield',
                allowNegative: false
            }]
        }];
    },
    
    /**
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        // TODO set form ?
        
        this.getForm().findField('cfi_number').setDisabled(record.data.cfi_mode != 'number');
        this.getForm().findField('cfb_number').setDisabled(record.data.cfb_mode != 'number');
        this.getForm().findField('cfd_number').setDisabled(record.data.cfd_mode != 'number');
        this.getForm().findField('cfd_time').setDisabled(record.data.cfd_mode == 'off');
    },

    /**
     * 
     * @param {Object} record
     */
    onRecordUpdate: function(record) {
        // TODO get form ?
    }
    
});

