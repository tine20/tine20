/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Felamimail');
/**
 * @param config
 * @constructor
 */
Tine.Felamimail.sieve.VacationPanel = function(config) {
    Ext.apply(this, config);
    Tine.Felamimail.sieve.VacationPanel.superclass.constructor.call(this);
};

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.VacationPanel
 * @extends     Ext.Panel
 */
Ext.extend(Tine.Felamimail.sieve.VacationPanel, Ext.Panel, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    /**
     * @private
     */
    appName: 'Felamimail',
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,
    asAdminModule:false,
    
    layout: 'vbox',
    layoutConfig: {
        align:'stretch'
    },
    
    border: false,
    app: null,

    
    initComponent: function () {
        this.initReasonEditor();
        var generalItems = this.getGeneralItems();

        this.items = [{
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
            },
            {
                title: this.app.i18n._('Advanced'),
                collapsible: true,
                collapseMode: 'mini',
                collapsed: true,
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
                    minValue: 1,
                    checkState: () => {
                        this.editDialog.getForm().findField('days')
                            .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                    },
                }]]
        }];
        
        this.supr().initComponent.call(this);
    },

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     *
     * @private
     */
    updateToolbars: function () {
    },
    
    /**
     * init reason editor
     */
    initReasonEditor: function () {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        var reg = this.app.getRegistry(),
            readonly = reg.get('config').vacationMessageCustomAllowed && reg.get('config').vacationMessageCustomAllowed.value === 0;

        this.reasonEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Incoming mails will be answered with this text:'),
            name: 'reason',
            allowBlank: true,
            disabled: true,
            height: 220,
            readOnly: readonly,
            getDocMarkup: function () {
                var markup = '<html><body></body></html>';
                return markup;
            },
            checkState: () => {
                this.editDialog.getForm().findField('reason')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
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
    getGeneralItems: function () {
        let me = this;
        let items = [[{
            fieldLabel: this.app.i18n._('Status'),
            name: 'enabled',
            typeAhead: false,
            triggerAction: 'all',
            lazyRender: true,
            editable: false,
            mode: 'local',
            forceSelection: true,
            value: 0,
            xtype: 'combo',
            store: [
                [0, this.app.i18n._('I am available (vacation message disabled)')],
                [1, this.app.i18n._('I am not available (vacation message enabled)')]
            ],
            listeners: {
                scope: me,
                select: function (combo, record) {
                    me.editDialog.onRecordUpdate();
                }
            }
        }]];

        // TODO always add date items? check capabilities here / pass it in registry?
        items = items.concat(this.getDateItems());

        const templates = this.app.getRegistry().get('vacationTemplates');

        if (templates.totalcount > 0) {
            items = items.concat(this.getTemplateItems(templates));
        }

        items.push([this.reasonEditor]);

        return items;
    },
    
   
    /**
     * @return Array
     */
    getDateItems: function () {
        const commonConfig = this.getCommonFieldConfig();
        return [[Ext.apply({
            fieldLabel: this.app.i18n._('Start Date'),
            emptyText: this.app.i18n._('Set vacation start date ...'),
            name: 'start_date',
            xtype: 'datefield',
            checkState: () => {
                this.editDialog.getForm().findField('start_date')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            }
        }, commonConfig), Ext.apply({
            fieldLabel: this.app.i18n._('End Date'),
            emptyText: this.app.i18n._('Set vacation end date ...'),
            name: 'end_date',
            xtype: 'datefield',
            checkState: () => {
                this.editDialog.getForm().findField('end_date')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            }
        }, commonConfig)]
        ];
    },

    getCommonFieldConfig: function () {
        return {
            listeners: {
                scope: this,
                select: this.onSelectTemplateField
            },
            columnWidth: 0.5
        };
    },

    /**
     * get items for vacation templates
     *
     * @return Array
     *
     * TODO use grid panel for x representatives?
     * @param templates
     */
    getTemplateItems: function (templates) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationPanel::getTemplateItems()');
        Tine.log.debug(templates);

        const commonConfig = this.getCommonFieldConfig();

        return [[
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #1'),
                emptyText: this.app.i18n._('Choose first Representative ...'),
                blurOnSelect: true,
                name: 'contact_id1',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true,
                checkState: () => {
                    this.editDialog.getForm().findField('contact_id1')
                        .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                }
            }, commonConfig)),
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #2'),
                emptyText: this.app.i18n._('Choose second Representative ...'),
                blurOnSelect: true,
                name: 'contact_id2',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true,
                checkState: () => {
                    this.editDialog.getForm().findField('contact_id2')
                        .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                }
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
            }),
            checkState: () => {
                this.editDialog.getForm().findField('template_id')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            }
        }]
        ];
    },

    /**
     * template field has been selected, check if new vacation message needs to be fetched
     * - do this only if template has already been selected
     */
    onSelectTemplateField: async function () {
        if (this.editDialog.getForm().findField('template_id').getValue() !== '') {
            this.editDialog.onRecordUpdate();
        }
    },
    
    /**
     * template combo select event handler
     *
     * @param {} combo
     * @param {} record
     * @param {} index
     */
    onTemplateComboSelect: async function (combo, record, index) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationPanel::onTemplateComboSelect()');
        Tine.log.debug(record);
        
        if (record.data && record.get('type') === 'file') {
            this.editDialog.onRecordUpdate();
        } else {
            // TODO do something?
        }
    }
});

Ext.reg('Tine.Felamimail.sieve.VacationPanel', Tine.Felamimail.sieve.VacationPanel);
