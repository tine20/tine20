/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail.sieve');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.VacationEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing sieve filters (vacation and rules).</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new VacationEditDialog
 */
 Tine.Felamimail.sieve.VacationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'VacationEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Vacation,
    recordProxy: Tine.Felamimail.vacationBackend,
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        // mime type is always multipart/alternative
        this.record.set('mime', 'multipart/alternative');
        if (this.account && this.account.get('signature')) {
            this.record.set('signature', this.account.get('signature'));
        }

        this.getForm().loadRecord(this.record);
        
        var title = String.format(this.app.i18n._('Vacation Message for {0}'), this.account.get('name'));
        this.window.setTitle(title);
        
        this.reasonEditor.setDisabled(! this.record.get('enabled'));
        
        Tine.log.debug('Tine.Felamimail.sieve.VacationEditDialog::onRecordLoad() -> record:');
        Tine.log.debug(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     */
    getFormItems: function() {
        
        this.initReasonEditor();
        
        var generalItems = this.getGeneralItems();
        
        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('General'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: generalItems
            }, {
                title: this.app.i18n._('Advanced'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [[{
                    fieldLabel: this.app.i18n._('Only send all X days to the same sender'),
                    name: 'days',
                    value: 7,
                    xtype: 'numberfield',
                    allowNegative: false,
                    minValue: 1
                }]]
            }]
        };
    },
    
    /**
     * init reason editor
     */
    initReasonEditor: function() {
        var reg = this.app.getRegistry(),
            readonly = reg.get('config').vacationMessageCustomAllowed && reg.get('config').vacationMessageCustomAllowed.value === 0;
        
        this.reasonEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Incoming mails will be answered with this text:'),
            name: 'reason',
            allowBlank: true,
            disabled: true,
            height: 220,
            readOnly: readonly,
            getDocMarkup: function() {
                var markup = '<html><body></body></html>';
                return markup;
            },
            plugins: [
                new Ext.ux.form.HtmlEditor.RemoveFormat()
            ]
        });
    },
    
    /**
     * get items for general tab
     * 
     * @return Array
     */
    getGeneralItems: function() {
        var items = [[{
            fieldLabel: this.app.i18n._('Status'),
            name: 'enabled',
            typeAhead     : false,
            triggerAction : 'all',
            lazyRender    : true,
            editable      : false,
            mode          : 'local',
            forceSelection: true,
            value: 0,
            xtype: 'combo',
            store: [
                [0, this.app.i18n._('I am available (vacation message disabled)')], 
                [1, this.app.i18n._('I am not available (vacation message enabled)')]
            ],
            listeners: {
                scope: this,
                select: function (combo, record) {
                    this.reasonEditor.setDisabled(! record.data.field1);
                }
            }
        }]];
        
        // add vacation template items if needed
        var templates = this.app.getRegistry().get('vacationTemplates');
        if (templates.totalcount > 0) {
            items = items.concat(this.getTemplateItems(templates));
        }
        
        items.push([this.reasonEditor]);
        
        return items;
    },
    
    /**
     * get items for vacation templates
     * 
     * @param Object templates
     * @return Array
     * 
     * TODO use grid panel for x representatives?
     */
    getTemplateItems: function(templates) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationEditDialog::getTemplateItems()');
        Tine.log.debug(templates);
        
        var commonConfig = {
                listeners: {
                    scope: this,
                    select: this.onSelectTemplateField
                },
                columnWidth: 0.5,
            },
            items = [[Ext.apply({
            fieldLabel: this.app.i18n._('Start Date'),
            emptyText: this.app.i18n._('Set vacation start date ...'),
            name: 'start_date',
            xtype: 'datefield'
        }, commonConfig), Ext.apply({
            fieldLabel: this.app.i18n._('End Date'),
            emptyText: this.app.i18n._('Set vacation end date ...'),
            name: 'end_date',
            xtype: 'datefield'
        }, commonConfig)], [
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #1'),
                emptyText: this.app.i18n._('Choose first Representative ...'),
                blurOnSelect: true,
                name: 'contact_id1',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true
            }, commonConfig)),
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #2'),
                emptyText: this.app.i18n._('Choose second Representative ...'),
                blurOnSelect: true,
                name: 'contact_id2',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true
            }, commonConfig))
        ], [{
            fieldLabel: this.app.i18n._('Message Template'),
            xtype: 'combo',
            mode: 'local',
            listeners: {
                scope: this,
                select: this.onTemplateComboSelect
            },
            displayField: 'name',
            name: 'template_id',
            valueField: 'id',
            triggerAction: 'all',
            emptyText: this.app.i18n._('Choose Template ...'),
            editable: false,
            store: new Ext.data.JsonStore({
                id: 'timezone',
                root: 'results',
                totalProperty: 'totalcount',
                fields: ['id', 'name', 'type'], // TODO use Tine.Filemanager.Model.Node or generic File model?
                data: templates
            })
        }]
        ];
        
        return items;
    },
    
    /**
     * template field has been selected, check if new vacation message needs to be fetched
     * - do this only if template has already been selected
     */
    onSelectTemplateField: function() {
        if (this.record.get('template_id') !== '') {
            this.getVacationMessage();
        }
    },
    
    /**
     * template combo select event handler
     * 
     * @param {} combo
     * @param {} record
     * @param {} index
     */
    onTemplateComboSelect: function(combo, record, index) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationEditDialog::onTemplateComboSelect()');
        Tine.log.debug(record);
        
        if (record.data && record.get('type') === 'file') {
            this.getVacationMessage();
        } else {
            // TODO do something?
        }
    },
    
    /**
     * get vacation with template replacements message from server
     */
    getVacationMessage: function() {
        this.loadMask.show();
        this.onRecordUpdate();
        Tine.Felamimail.getVacationMessage(this.record.data, this.onGetVacationMessage.createDelegate(this));
    },
    
    /**
     * onGetVacationMessage
     * 
     * @param {} response
     */
    onGetVacationMessage: function(response) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationEditDialog::onGetMessage()');
        Tine.log.debug(response);
        this.loadMask.hide();
        
        if (response.message) {
            this.reasonEditor.setValue(response.message);
        }
    },
    
    /**
     * generic request exception handler
     * 
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        Tine.Felamimail.handleRequestException(exception);
        this.loadMask.hide();
    },
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function() {
        Tine.Felamimail.sieve.VacationEditDialog.superclass.onRecordUpdate.call(this);
        
        var contactIds = [];
        Ext.each(['contact_id1', 'contact_id2'], function(field) {
            if (this.getForm().findField(field) && this.getForm().findField(field).getValue() !== '') {
                contactIds.push(this.getForm().findField(field).getValue());
            }
        }, this);
        this.record.set('contact_ids', contactIds);
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.VacationEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 640,
        height: 550,
        name: Tine.Felamimail.sieve.VacationEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.VacationEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
