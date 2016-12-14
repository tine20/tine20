/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.display');

/**
 * @class       Tine.widgets.display.DefaultDisplayPanel
 * @namespace   Tine.widgets.display
 * @extends     Ext.ux.display.DisplayPanel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 * Panel for displaying default info.
 */
Tine.widgets.display.DefaultDisplayPanel = Ext.extend(Ext.ux.display.DisplayPanel, {

    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class
     */
    recordClass: null,

    /**
     * @property {String}
     */
    appName: null,
    /**
     * @property {String}
     */
    modelName: null,
    /**
     * @property {Tine.Tinebase.Application}
     */
    app: null,

    /* private */
    layout: 'fit',
    border: false,

    /**
     * initializes the component, builds this.fields, calls parent
     */
    initComponent: function() {
        this.appName = this.recordClass.getMeta('appName');
        this.modelName = this.recordClass.getMeta('modelName');

        this.app = Tine.Tinebase.appMgr.get(this.appName);

        this.items = [{
            layout: 'hbox',
            border: false,
            defaults:{margins:'0 5 0 0'},
            layoutConfig: {
                padding:'5',
                align:'stretch'
            },
            items: [{
                flex: 1,
                border: false,
                layout: 'ux.display',
                layoutConfig: {
                    background: 'solid',
                    declaration: this.recordClass.getRecordsName()
                }
            }]
        }];

        Tine.widgets.display.DefaultDisplayPanel.superclass.initComponent.call(this);
    }
});

Ext.reg('ux.displaypanel', Ext.ux.display.DisplayPanel);
