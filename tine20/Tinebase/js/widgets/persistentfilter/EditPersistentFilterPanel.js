/*
 * Tine 2.0
 * 
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3 @author
 * @author Alexander Stintzing <a.stintzing@metaways.de> 
 * @copyright Copyright (c) 2009-2012
 * Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.persistentfilter');

/**
 * @namespace Tine.widgets.persistentfilter
 * @class Tine.widgets.persistentfilter.EditPersistentFilterPanel
 * @extends Ext.FormPanel
 * 
 * <p>
 * PersistentFilter Edit Dialog
 * </p>
 * 
 * @author Alexander Stintzing <a.stintzing@metaways.de>
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param {Object} config
 */
Tine.widgets.persistentfilter.EditPersistentFilterPanel = Ext.extend(Ext.FormPanel, {
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',

    bodyStyle : 'padding:5px',
    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    /**
     * the application the panel belongs to. (auto set by initComponent)
     * 
     * @type {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * initializes the component
     */
    initComponent : function() {
        this.addEvents('cancel', 'save', 'close');
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();

        this.app = Tine.Tinebase.appMgr.get(this.window.app.name);
        
        // get items for this dialog
        this.items = this.getFormItems();

        Tine.widgets.persistentfilter.EditPersistentFilterPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initializes the actions
     */
    initActions : function() {
        this.action_save = new Ext.Action({
            text : _('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onSave,
            iconCls : 'action_saveAndClose'
        });
        this.action_cancel = new Ext.Action({
            text : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
    },

    /**
     * initializes the buttons
     */
    initButtons : function() {
        this.fbar = ['->', this.action_cancel, this.action_save];
    },

    /**
     * @private
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [{
            key : [13],
            ctrl : false,
            fn : this.onSave,
            scope : this
        }]);
    },
    
    /**
     * is called on save
     */
    onSave : function() {
        if (!this.window.record) {
            this.window.record = this.getNewEmptyRecord();
        }
        var record = this.window.record,
            shipped = this.window.record.isShipped(),
            // if any field has changed of a shipped record, use translation
            shippedChanged = ((shipped && this.inputTitle.getValue() != this.app.i18n._(this.window.record.get('name'))) ||
                              (shipped && this.inputDescription.getValue() != this.app.i18n._(this.window.record.get('description'))) ||
                              (this.hasRight() && ! this.inputCheck.getValue()));
        
        // Name of the favorite
        if (this.inputTitle.isValid()) {
            if (this.inputTitle.getValue().length < 40) {
                if (shipped) {
                    if (shippedChanged) {
                        record.set('name', this.inputTitle.getValue());
                    }
                } else{
                    record.set('name', this.inputTitle.getValue());
                }
                
            } else {
                Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a shorter name! Names of favorite can only be up to 40 characters long.'));
                this.onCancel();
            }
        } else {
            Ext.Msg.alert(_('Favorite not Saved'),_('You have to supply a name for the favorite!'));
            this.onCancel();
        }

        // Description of the favorite
        if (this.inputDescription.isValid()) {
            if (shipped) {
                if (shippedChanged) {
                    record.set('description', this.inputDescription.getValue());
                }
            } else {
                record.set('description', this.inputDescription.getValue());
            }
            
        }
        
        record.set('account_id', Tine.Tinebase.registry.get('currentAccount').accountId);

        // Favorite Checkbox
        if (this.hasRight()) {
            if (this.inputCheck.getValue()) {
                record.set('account_id', null);
            }
        }
        this.window.fireEvent('update');
        this.purgeListeners();
        this.window.purgeListeners();
        this.window.close();
    },

    /**
     * is called on cancel
     */
    onCancel : function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    /**
     * returns the needed form items
     * @return {Object}
     */
    getFormItems : function() {
        
        var record = this.window.record,
            shipped = record && record.isShipped(),
            title = (shipped) ? this.app.i18n._(record.get('name')) : record ? record.get('name') : '',
            description = (shipped) ? this.app.i18n._(record.get('description')) : record ? record.get('description') : '';

        this.inputTitle = new Ext.form.TextField({
            value: title,
            allowBlank : false,
            fieldLabel : _('Title'),
            width : '97%',
            minLength : 1,
            maxLength : 40
        });

        this.inputDescription = new Ext.form.TextField({
            value: description,
            allowBlank : true,
            fieldLabel : _('Description'),
            width : '97%',
            minLength : 0,
            maxLength : 255
        });

        this.inputCheck = new Ext.form.Checkbox({
            checked: (record) ? record.isShared() : false,
            hideLabel: true,
            boxLabel: _('Shared Favorite (visible by all users)')
        });

        var items = [
            {
                region : 'center',
                layout : {
                    align : 'stretch',
                    type : 'vbox'
                }
            }, 
            this.inputTitle, 
            this.inputDescription
        ];

        if (this.hasRight()) {
            items.push(this.inputCheck);
        }

        return {
            border : false,
            frame : true,
            layout : 'form',
            items : items
        };
    },
    
    /**
     * checks the managed_shared_<this.modelName>_right
     * @return {}
     */
    hasRight: function() {
        return Tine.Tinebase.common.hasRight('manage_shared_' + this.window.modelName.toLowerCase() + '_favorites', this.window.app.name);
    }
});
