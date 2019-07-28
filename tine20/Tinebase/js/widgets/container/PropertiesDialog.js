/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * Container Properties dialog
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.PropertiesDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.container.PropertiesDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @cfg {Tine.Tinebase.container.models.container}
     * Container to manage grants for
     */
    grantContainer: null,
    
    /**
     * @cfg {string}
     * Name of container folders, e.g. Addressbook
     */
    containerName: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'ContainerPropertiesWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,

    /**
     * @private
     */
    initComponent: function() {
        var _ = window.lodash,
            me = this,
            getTranslation = function(string) {
                var translation = me.grantsModelApp.i18n._hidden(string);

                return translation != string ? translation : window.i18n._hidden(string);
            };

        if (! this.grantsModel) {
            this.grantsModel = Tine.Tinebase.container.getGrantsModel(this.grantContainer);
        }

        if (! this.grantsModelApp) {
            this.grantsModelApp = Tine.Tinebase.appMgr.get(this.grantsModel.getMeta('appName'));
        }

        // my grants
        this.myGrants = [];
        for (var grant in this.grantContainer.account_grants) {
            if (this.grantContainer.account_grants.hasOwnProperty(grant)
                && this.grantContainer.account_grants[grant]
                && Tine.widgets.container.GrantsGrid.prototype[grant + 'Title']
                && this.grantsModel.hasField(grant)
            ) {
                this.myGrants.push({
                    title: getTranslation(Tine.widgets.container.GrantsGrid.prototype[grant + 'Title']),
                    description: getTranslation(Tine.widgets.container.GrantsGrid.prototype[grant + 'Description'])
                })
            }
        }
        
        this.myGrantsTemplate = new Ext.XTemplate(
            '<tpl for=".">',
                '<span class="tine-wordbox" ext:qtip="','{[this.encode(values.description)]}','">','{[this.encode(values.title)]}','</span>',
            '</tpl>',
            {
                encode: function(description) { return Tine.Tinebase.common.doubleEncode(description); }
            }
        ).compile();
        
        Tine.widgets.container.PropertiesDialog.superclass.initComponent.call(this);
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        return ['',
            this.app,
            this.canonicalName,
            'ContainerProperties',
            this.grantsModel.getMeta('')
        ].join(Tine.Tinebase.CanonicalPath.separator);
    },

    /**
     * init record to edit
     * 
     * - overwritten: we don't have a record here 
     */
    initRecord: function() {
    },
    
    /**
     * returns dialog
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: i18n._('Properties'),
                border: false,
                frame: true,
                layout: 'form',
                labelAlign: 'top',
                labelSeparator: '',
                layoutConfig: {
                    trackLabels: true
                },
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tine.widgets.container.PropertiesDialog.FormItems.Properties'
                }],
                items: [{
                    xtype: 'textfield',
                    anchor: '100%',
                    readOnly: true,
                    fieldLabel: i18n._('Name'),
                    value: this.grantContainer.name
                },{
                    xtype: 'textfield',
                    anchor: '100%',
                    readOnly: true,
                    fieldLabel: i18n._('Hierarchy/Name'),
                    value: this.grantContainer.hierarchy
                }, {
                    xtype: 'colorfield',
                    width: 40,
                    readOnly: true,
                    fieldLabel: i18n._('Color'),
                    value: this.grantContainer.color
                }, {
                    xtype: 'label',
                    anchor: '100%',
                    readOnly: true,
                    fieldLabel: i18n._('My Grants'),
                    html: this.myGrantsTemplate.applyTemplate(this.myGrants)
                }]
            }]
        };
    },
    
    /**
     * @private
     */
    onApplyChanges: function() {
        this.purgeListeners();
        this.window.close();
    }
});

/**
 * grants dialog popup / window
 */
Tine.widgets.container.PropertiesDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 450,
        name: Tine.widgets.container.PropertiesDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.container.PropertiesDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
