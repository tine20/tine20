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

Tine.widgets.persistentfilter.EditPersistentFilterPanel = Ext.extend(
        Ext.FormPanel, {

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
                this.addEvents('cancel', 'save', 'close');
                // init actions
                this.initActions();
                // init buttons and tbar
                this.initButtons();
                // get items for this dialog
                this.items = this.getFormItems();

                Tine.widgets.persistentfilter.EditPersistentFilterPanel.superclass.initComponent.call(this);
            },

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

            initButtons : function() {
                this.fbar = ['->', this.action_cancel, this.action_save];
            },

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
            
            onSave : function() {
                
                if(!this.window.record) {
                    this.window.record = this.getNewEmptyRecord();
                }

                // Name of the favorite
                if (this.inputTitle.isValid()) {
                    if (this.inputTitle.getValue().length < 40) {
                        this.window.record.set('name', this.inputTitle.getValue());
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
                    this.window.record.set('description', this.inputDescription.getValue());
                }

                this.window.record.set('account_id', Tine.Tinebase.registry.get('currentAccount').accountId);

                // Favorite Checkbox
                if (Tine.Tinebase.common.hasRight('manage_shared_favorites', this.window.app.name)) {
                    if (this.inputCheck.getValue()) {
                        this.window.record.set('account_id', null);
                    }
                }
                this.window.fireEvent('update');
                this.purgeListeners();
                this.window.purgeListeners();
                this.window.close();
            },

            onCancel : function() {
                this.fireEvent('cancel');
                this.purgeListeners();
                this.window.close();
            },

            getFormItems : function() {

                this.inputTitle = new Ext.form.TextField({
                    value: (this.window.record) ? this.window.record.get('name') : '',
                    allowBlank : false,
                    fieldLabel : _('Title'),
                    width : '97%',
                    minLength : 1,
                    maxLength : 40
                });

                this.inputDescription = new Ext.form.TextField({
                    value: (this.window.record) ? this.window.record.get('description') : '',
                    allowBlank : true,
                    fieldLabel : _('Description'),
                    width : '97%',
                    minLength : 0,
                    maxLength : 255
                });

                this.inputCheck = new Ext.form.Checkbox({
                    checked: (this.window.record) ? this.window.record.isShared() : false,
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

                if (Tine.Tinebase.common.hasRight('manage_shared_favorites', this.window.app.name)) {
                    items.push(this.inputCheck);
                }

                return {
                    border : false,
                    frame : true,
                    layout : 'form',
                    items : items
                };
            }

        });