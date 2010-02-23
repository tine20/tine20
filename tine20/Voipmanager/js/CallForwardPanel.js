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
 * @extends     Ext.Panel
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
Tine.Voipmanager.CallForwardPanel = Ext.extend(Ext.Panel, {

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
     * @type Tine.widgets.dialog.EditDialog
     */
    editDialog: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.addEvents(
            /**
             * @event change
             * Fired when one of the input fields changed
             */
            'change');
        this.items = this.getFormItems();
        
        Tine.Voipmanager.CallForwardPanel.superclass.initComponent.call(this);
        
        this.on('afterrender', this.onAfterRender, this);
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return [{
            title: this.app.i18n._('Forward immediately'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
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
                value: 'off',
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        // disable number if != number
                        this.editDialog.getForm().findField('cfi_number').setDisabled(record.data.field1 != 'number');
                        this.onFieldChange();
                    }
                }
            }, {
                name: 'cfi_number',    
                fieldLabel: this.app.i18n._('Number'),
                xtype: 'textfield',
                enableKeyEvents: true,
                listeners: {
                    keyup: this.onFieldChange,
                    scope: this
                }
            }]
        }, {
            title: this.app.i18n._('Forward busy'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
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
                value: 'off',
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        this.editDialog.getForm().findField('cfb_number').setDisabled(record.data.field1 != 'number');
                        this.onFieldChange();
                    }
                }
            }, {
                name: 'cfb_number',    
                fieldLabel: this.app.i18n._('Number'),
                xtype: 'textfield',
                enableKeyEvents: true,
                listeners: {
                    keyup: this.onFieldChange,
                    scope: this
                }
            }]
         }, {
            title: this.app.i18n._('Forward delayed'),
            autoHeight: true,
            xtype: 'fieldset',
            layout: 'form',
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
                value: 'off',
                store: [
                    ['off', 'off'],
                    ['number', 'number'],
                    ['voicemail', 'voicemail']
                ],
                listeners: {
                    scope: this,
                    select: function(combo, record) {
                        this.editDialog.getForm().findField('cfd_number').setDisabled(record.data.field1 != 'number');
                        this.editDialog.getForm().findField('cfd_time').setDisabled(record.data.field1 == 'off');
                        this.onFieldChange();
                    }
                }
            }, {
                name: 'cfd_number',    
                fieldLabel: this.app.i18n._('Number'),
                xtype: 'textfield',
                enableKeyEvents: true,
                listeners: {
                    keyup: this.onFieldChange,
                    scope: this
                }
            }, {
                name: 'cfd_time',      
                fieldLabel: this.app.i18n._('Delay time'),
                xtype: 'numberfield',
                allowNegative: false,
                value: 30,
                enableKeyEvents: true,
                listeners: {
                    keyup: this.onFieldChange,
                    scope: this
                }
            }]
        }];
    },
    
    /**
     * disable some fields after render
     */
    onAfterRender: function() {
        if (this.record !== null) {
            this.disableFields();
        }
    },
    
    /**
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        if (this.rendered) {
            this.disableFields();
        }
    },
    
    /**
     * fire change event if field changes
     */
    onFieldChange: function() {
        this.fireEvent('change');
    },
    
    disableFields: function() {
        this.editDialog.getForm().findField('cfi_number').setDisabled(this.record.data.cfi_mode != 'number');
        this.editDialog.getForm().findField('cfb_number').setDisabled(this.record.data.cfb_mode != 'number');
        this.editDialog.getForm().findField('cfd_number').setDisabled(this.record.data.cfd_mode != 'number');
        this.editDialog.getForm().findField('cfd_time').setDisabled(this.record.data.cfd_mode == 'off');
    }
});
