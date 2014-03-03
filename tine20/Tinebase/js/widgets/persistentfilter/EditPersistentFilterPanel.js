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
     * use preference settings for order of save and close buttons
     */
    initButtons : function() {

        this.fbar = ['->'];
        
        if (Tine.Tinebase.registry && Tine.Tinebase.registry.get('preferences') && Tine.Tinebase.registry.get('preferences').get('dialogButtonsOrderStyle') === 'Windows') {
            this.fbar.push(this.action_save, this.action_cancel);
        }
        else {
            this.fbar.push(this.action_cancel, this.action_save);
        }
    },

    /**
     * @private
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        // load grants
        if (this.window.record) {
            var grants = this.window.record.get('grants') || [];
            this.getGrantsStore().loadData({results: grants});
        }

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
        if (! this.window.record) {
            this.window.record = this.getNewEmptyRecord();
        }
        var record = this.window.record,
            shipped = this.window.record.isShipped(),
            // if any field has changed of a shipped record, use translation
            shippedChanged = ((shipped && this.inputTitle.getValue() != this.app.i18n._(this.window.record.get('name'))) ||
                              (shipped && this.inputDescription.getValue() != this.app.i18n._(this.window.record.get('description'))) ||
                              ((this.hasRight() || this.hasEditGrant()) && ! this.inputCheck.getValue()));
        
        if (this.inputTitle.isValid()) {
            if (! shipped || shippedChanged) {
                record.set('name', this.inputTitle.getValue());
            }
        } else {
            Ext.Msg.alert(_('Favorite not saved'), this.inputTitle.getActiveError());
            return;
        }

        // Description of the favorite
        if (this.inputDescription.isValid()) {
            if (! shipped || shippedChanged) {
                record.set('description', this.inputDescription.getValue());
            }
        }
        
        record.set('account_id', Tine.Tinebase.registry.get('currentAccount').accountId);
        
        if (this.hasRight() || this.hasEditGrant()) {
            // Favorite Checkbox
            if (this.inputCheck.getValue()) {
                record.set('account_id', null);
            }
            
            // set grants
            this.window.record.set('grants', '');
            var grants = [];
            this.grantsStore.each(function(record){
                grants.push(record.data);
            });
            this.window.record.set('grants', grants);
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
            boxLabel: _('Shared Favorite (visible by all users)'),
            disabled: ! this.hasRight(),
            listeners: {
                'check': function(checkbox, value) {
                    Tine.log.debug('Tine.widgets.persistentfilter.EditPersistentFilterPanel::inputCheck.check -> ' + value);
                    if (value) {
                        this.getGrantsGrid().enable();
                    } else {
                        this.getGrantsGrid().disable();
                    }
                },
                scope: this
            }
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
        
        if (this.hasRight() || this.hasEditGrant()) {
            items.push(this.inputCheck);
            items.push(this.getGrantsGrid());
        }
        
        return {
            border : false,
            frame : true,
            layout : 'form',
            items : items
        };
    },
    
    /**
     * get grants grid
     * 
     * @return Tine.widgets.account.PickerGridPanel
     */
    getGrantsGrid: function() {
        if (! this.grantsGrid) {
            var columns = [
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Read'),
                    dataIndex: 'readGrant',
                    tooltip: _('The grant to see and use this filter'),
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Edit'),
                    tooltip: _('The grant to edit this filter'),
                    dataIndex: 'editGrant',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Delete'),
                    tooltip: _('The grant to delete this filter'),
                    dataIndex: 'deleteGrant',
                    width: 55
                })
            ];
            
            this.grantsGrid = new Tine.widgets.account.PickerGridPanel({
                selectType: 'both',
                title:  this.app.i18n._('Permissions'),
                store: this.getGrantsStore(),
                hasAccountPrefix: true,
                configColumns: columns,
                //selectAnyone: true,
                selectTypeDefault: 'group',
                height : 250,
                // TODO improve layout: use border layout, move this panel to center to allow dynamic resizing
                //width: 'auto',
                //height : '100%',
                //height : 'auto',
                disabled: ! this.window.record || ! this.window.record.isShared(),
                recordClass: Tine.Tinebase.Model.Grant
            });
        }
        return this.grantsGrid;
    },
    
    /**
     * get grants store
     * 
     * @return Ext.data.JsonStore
     */
    getGrantsStore: function() {
        if (! this.grantsStore) {
            this.grantsStore = new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                // use account_id here because that simplifies the adding of new records with the search comboboxes
                id: 'account_id',
                fields: Tine.Tinebase.Model.Grant
            });
        }
        return this.grantsStore;
    },
    
    /**
     * checks the managed_shared_<this.modelName>_right
     * @return {}
     */
    hasRight: function() {
        var right = Tine.Tinebase.common.hasRight('manage_shared_' + this.window.modelName.toLowerCase() + '_favorites', this.window.app.name);
        return right;
    },
    
    /**
     * check if user has grant on record 
     * 
     * @param {Record} record
     * @param {String} requiredGrant
     * @return {Boolean}
     * 
     * TODO move this to generic Record
     */
    hasEditGrant: function() {
        var record = this.window.record,
            accountGrants = record ? (record.account_grants || record.data.account_grants) : false,
            result = (accountGrants && accountGrants['editGrant'] == true);
            
        Tine.log.debug('hasEditGrant() -> ' + result);
        return result;
    }
});
