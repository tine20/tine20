/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.StreamEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Contract Compose Dialog</p>
 * <p></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.StreamEditDialog
 */
Tine.HumanResources.StreamEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    appName: 'HumanResources',
    windowHeight: 550,

    /**
     * inits the component
     */
    initComponent: function() {
        Tine.HumanResources.StreamEditDialog.superclass.initComponent.call(this);
    },

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initalisation is done.
     *
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        let streamNorthPanel = {
            xtype: 'fieldset',
            region: 'north',
            autoHeight: true,
            title: this.app.i18n._('Stream'),
            items: [{
                xtype: 'panel',
                layout: 'hbox',
                align: 'stretch',
                /*plugins: [{
                    ptype: 'ux.itemregistry',
                    key: 'Tine.HumanResources.editDialog.northPanel'
                }],*/
                items: [{
                    flex: 1,
                    xtype: 'columnform',
                    autoHeight: true,
                    style: 'padding-right: 5px;',
                    items: [
                        [new Tine.Tinebase.widgets.keyfield.ComboBox({
                            fieldLabel: this.app.i18n._('Type'),
                            name: 'type',
                            app: 'HumanResources',
                            keyFieldName: 'streamType',
                            value: ''
                        }),
                            {name: 'title', maxLength: 64, fieldLabel: this.app.i18n._('Title'), allowBlank: false}
                        ]
                    ]
                }]
            }]
        };

        let streamEastPanel = {
            region: 'east',
            layout: 'ux.multiaccordion',
            animate: true,
            width: 210,
            split: true,
            collapsible: true,
            collapseMode: 'mini',
            header: false,
            margins: '0 5 0 5',
            border: true,
            items: [
                new Ext.Panel({
                    title: this.app.i18n._('Description'),
                    iconCls: 'descriptionIcon',
                    layout: 'form',
                    labelAlign: 'top',
                    border: false,
                    items: [{
                        style: 'margin-top: -4px; border 0px;',
                        labelSeparator: '',
                        xtype: 'textarea',
                        name: 'description',
                        hideLabel: true,
                        grow: false,
                        preventScrollbars: false,
                        anchor: '100% 100%',
                        emptyText: this.app.i18n._('Enter description')
                    }]
                })/*, new Ext.Panel({
                    title: this.app.i18n._('Board Info'),
                    iconCls: 'descriptionIcon',
                    layout: 'form',
                    labelAlign: 'top',
                    border: false,
                    items: [{
                        style: 'margin-top: -4px; border 0px;',
                        labelSeparator: '',
                        xtype: 'textarea',
                        name: 'boardinfo',
                        hideLabel: true,
                        grow: false,
                        preventScrollbars: false,
                        anchor: '100% 100%',
                        emptyText: this.app.i18n._('Enter board info')
                    }]
                })*/
            ]
        };

        let streamTab = {
            title: this.app.i18n._('Stream'),
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                layout: 'border',
                items: [
                    streamNorthPanel //,
                    //streamCenterPanel,
                    //streamSouthPanel
                ]
            }/*, streamEastPanel*/]
        };

        let tabs = [
            streamTab
        ];

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine.HumanResources.editDialog.mainTabPanel'
            }, {
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items: tabs
        };
/*
        return {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            activeTab: 0,
            items: [{
                xtype: 'panel',
                layout: 'hbox',
                align: 'stretch',
                title: this.app.i18n._('Stream'),
                autoScroll: true,
                border: false,
                frame: true,
                //layout: 'border',
                items: [{
                    region: 'north',
                    layout: 'hfit',
                    //height: 220,
                    flex: 1,
                    xtype: 'columnform',
                    autoHeight: true,
                    style: 'padding-right: 5px;',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Stream'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            defaults: {columnWidth: 1/2},
                            items: [[
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    fieldLabel: this.app.i18n._('Type'),
                                    name: 'type',
                                    app: 'HumanResources',
                                    keyFieldName: 'streamType',
                                    value: ''
                                }),
                                {name: 'title', maxLength: 64, fieldLabel: this.app.i18n._('Title'), allowBlank: false}
                            ], [
                                {name: 'description', fieldLabel: this.app.i18n._('Description'), columnWidth: 1}
                            ], [
                                {name: 'boardinfo', fieldLabel: this.app.i18n._('Board Info'), columnWidth: 1}
                            ]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Responsibles'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            items: [
                                [
                                    {name: 'boardinfo', fieldLabel: this.app.i18n._('Board Info'), columnWidth: 1}
                                ]
                            ]
                        }]
                    }]
                }]
            }]
        };*/
    }
});
