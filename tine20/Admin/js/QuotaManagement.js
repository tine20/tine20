/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Admin');

Tine.Admin.QuotaManagement = Ext.extend(Ext.ux.tree.TreeGrid, {
    enableDD: false,
    border: false,

    initComponent: function() {
        var _ = window.lodash,
            comp = this;
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        this.app = Tine.Tinebase.appMgr.get('Admin');
        const appsToShow = Tine.Tinebase.configManager.get('appsToShow', 'Admin');
        const showCurrentFSUsageConfig = Tine.Tinebase.configManager.get('filesystem.showCurrentUsage', 'Tinebase');
        this.allowManageTotalQuota = Tine.Tinebase.configManager.get('quotaAllowTotalInMBManagement', 'Admin');
        this.topNode = this.allowManageTotalQuota ? '/Total Quota' : '';
        
        this.loader = {
            directFn: Tine.Admin.searchQuotaNodes,
            getParams : function(node, callback, scope) {
                let path = node.getPath('name').replace(comp.getRootNode().getPath(), '/').replace(/\/+/, '/');
                
                if (comp.allowManageTotalQuota) {
                    if (path === '/') {
                        path = '';
                    }
                }
                
                path = path.replace(comp.topNode, '/').replace(/\/+/, '/');
                const filter = [
                    {field: 'path', operator: 'equals', value: path}
                ];

                if (path === '' && comp.buttonRefreshData) {
                    comp.buttonRefreshData.disable();
                }

                return [filter];
            },
            processResponse: async function (response, node, callback, scope) {
                if (comp.buttonRefreshData) {
                    comp.buttonRefreshData.enable();
                }
                response.responseData = response.responseText?.results;

                const attr = node.attributes;
                // path is the nodePath get from BE
                // virtual path is the same as current treenode structure
                attr.path = node.getPath('i18n_name').replace(comp.getRootNode().getPath(), '/').replace(/\/+/, '/');
                attr.virtualPath = node.getPath('name').replace(comp.getRootNode().getPath(), '/').replace(/\/+/, '/');
                
                _.map(response.responseData, async (nodeData) => {
                    nodeData.virtualPath = `${attr.path === '/' ? '' : attr.virtualPath}/${nodeData.name}`;
                    nodeData.virtualPath = nodeData.virtualPath.replace(comp.topNode,'');
                    
                    const applicationId = nodeData.virtualPath.split('/').filter(Boolean)?.[0];
                    const app = Tine.Tinebase.appMgr.getById(applicationId) ?? null;
                    nodeData.appName = app ? app.appName : '';
                    nodeData.translateAppName = app ? app.getTitle() : '';
                    
                    const isAppNode = nodeData.virtualPath.split('/').length === 2;
                    nodeData.i18n_name = isAppNode ? app.getTitle() : nodeData.name;
                    nodeData.path = `${attr.path === '/' ? '' : attr.path}/${nodeData.i18n_name}`;
                });
                
                return Ext.ux.tree.TreeGridLoader.prototype.processResponse.apply(this, arguments);
            },
            createNode: function(attr) {
                if (!appsToShow || appsToShow.includes(attr.appName)) {
                    return Ext.ux.tree.TreeGridLoader.prototype.createNode.apply(this, arguments);
                }
            },
            handleFailure : function(response){
                Ext.ux.tree.TreeGridLoader.prototype.handleFailure.apply(this, arguments);
                if (comp.buttonRefreshData) {
                    comp.buttonRefreshData.enable();
                }
            },
        };

        this.columns = [{
            id: 'i18n_name',
            header: this.app.i18n._("Name"),
            width: 400,
            sortable: true,
            dataIndex: 'i18n_name'
        },{
            id: 'i18n_name',
            header: this.app.i18n._("Quota"),
            width: 400,
            sortable: true,
            dataIndex: 'quota',
            tpl: new Ext.XTemplate('{quota:this.byteRenderer}', {
                byteRenderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
            })
        }];

        if (showCurrentFSUsageConfig) {
            if (showCurrentFSUsageConfig.includes('size')) {
                this.columns.push({
                    id: 'size',
                    header: this.app.i18n._("Size"),
                    width: 60,
                    sortable: true,
                    dataIndex: 'size',
                    tpl: new Ext.XTemplate('{size:this.byteRenderer}', {
                        byteRenderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
                    })
                });
            }
            if (showCurrentFSUsageConfig.includes('revision_size')
                && Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
                this.columns.push({
                    id: 'revision_size',
                    header: this.app.i18n._("Revision Size"),
                    tooltip: this.app.i18n._("Total size of all available revisions"),
                    width: 60,
                    sortable: true,
                    dataIndex: 'revision_size',
                    tpl: new Ext.XTemplate('{revision_size:this.byteRenderer}', {
                        byteRenderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
                    })
                });
            }
        }

        this.tbar = [{
            ref: '../buttonRefreshData',
            tooltip: Ext.PagingToolbar.prototype.refreshText,
            iconCls: "x-tbar-loading",
            handler: this.onButtonRefreshData,
            scope: this
        }];
        this.on('beforecollapsenode', this.onBeforeCollapse, this);
        this.on('click', this.click, this);
        this.on('dblclick', this.quotaEditDialogHandler, this);
        
        this.supr().initComponent.apply(this, arguments);
    },
    
    /**
     * require reload when node is collapsed
     */
    onBeforeCollapse: async function (node) {
        node.removeAll();
        node.loaded = false;
    },
    
    onButtonRefreshData: function() {
        this.getRootNode().reload();
        this._action_editQuota.setDisabled(true);
    },

    click: async function (node, event) {
        this._action_editQuota.setDisabled(!this.isNodeEditable(node));
    },

    getActionToolbar() {
        this._action_editQuota = new Ext.Action({
            text: this.translation.gettext('Edit Quota'),
            disabled: true,
            handler: this.quotaEditDialogHandler,
            scope: this,
            iconCls: 'action_edit'
        });


        this._action_manageFileSystemTotalQuota = new Ext.Action({
            text:  this.translation.gettext('Manage Total Quota'),
            handler: this.totalQuotaEditDialogHandler,
            scope: this,
            iconCls: 'action_settings',
        });

        return new Ext.Toolbar({
            canonicalName: ['QuotaManagement', 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
            split: false,
            items: [{
                xtype: 'buttongroup',
                columns: 7,
                items: [
                    Ext.apply(new Ext.Button(this._action_editQuota), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        hidden: this.allowManageTotalQuota
                    }),
                    Ext.apply(new Ext.Button(this._action_manageFileSystemTotalQuota), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                    })
                ]
            }
            ]
        });
    },

    /**
     * onclick handler for edit action
     */
    async quotaEditDialogHandler(_event) {
        // search current folder record based on its parent path
        if (this.allowManageTotalQuota) {
            return;
        }
        
        const node = this.getSelectionModel().getSelectedNode();
        if (this.isNodeEditable(node)) {
            Tine.Admin.QuotaEditDialog.openWindow({
                windowTitle: node.attributes.translateAppName ?? this.topNode,
                node: node,
                customizeFields: node.attributes.customfields,
                appName: node.attributes.appName
            });
        }
   },
    
    isNodeEditable(node) {
        let isEditable = false;
    
        if (!node) {
            return isEditable;
        }
        
        let disabledNodes = [
            `${this.topNode}`
        ];
        
        if (node.attributes.appName !== '') {
            const appName = node.attributes.appName;
            const translateApp = node.attributes.translateAppName;
            
            disabledNodes = disabledNodes.concat([
                `${this.topNode}/${translateApp}`,
                `${this.topNode}/${translateApp}/folders`,
                `${this.topNode}/${translateApp}/folders/personal`
            ]);
            
            if (appName === 'Felamimail') {
                disabledNodes.push(`${this.topNode}/${translateApp}/Emails`);
        
                if (node.attributes?.customfields?.domain) {
                    disabledNodes.push(`${this.topNode}/${translateApp}/Emails/${node.attributes.customfields.domain}`);
                }
            }
        } 
       
        isEditable = !disabledNodes.includes(node.attributes.path);
        
        return isEditable;
    },

   totalQuotaEditDialogHandler(node, _event) {
       const quotaConfig = Tine.Tinebase.configManager.get('quota');
       
       const quotaEditField =  Ext.ComponentMgr.create({
           fieldLabel: this.translation.gettext('Total quota'),
           emptyText: this.translation.gettext('no quota set'),
           name: 'quota',
           value: quotaConfig?.totalInMB * 1024 * 1024,
           xtype: 'extuxbytesfield'
       });
       
       const dialog = new Tine.Tinebase.dialog.Dialog({
           listeners: {
               apply: async (quota) => {
                   await Tine.Admin.saveQuota('Tinebase', null, {totalInMB: quota}).then((result) => {
                       if (result.totalInMB) {
                           Tine.Tinebase.common.confirmApplicationRestart(true);
                       }
                   });
               }
           },
           items: [{
               layout: 'form',
               frame: true,
               width: '100%',
               items: [quotaEditField]
           }],
          
           windowTitle: this.translation.gettext('Manage Total Quota'),
           contractDialog: this,
           /**
            * Creates a new pop up dialog/window (acc. configuration)
            *
            * @returns {null}
            * TODO can we put this in the Tine.Tinebase.dialog.Dialog?
            */
            openWindow: function (config) {
                if (this.window) {
                    return this.window;
                }
                config = config || {};
                this.window = Tine.WindowFactory.getWindow(Ext.apply({
                    title: this.windowTitle,
                    closeAction: 'close',
                    modal: true,
                    width: 300,
                    height: 100,
                    layout: 'fit',
                    items: [
                        this
                    ]
                }, config));

                return this.window;
            },

            getEventData: function(eventName) {
                if (eventName === 'apply') {
                    return  quotaEditField.getValue()
                }
            },
        });
       
        dialog.openWindow();
    }
    
});

Tine.Admin.registerItem({
    // _('Quota Management')
    text: 'Quota Management',
    iconCls: 'admin-node-quota-usage',
    pos: 750,
    viewRight: 'view_quota_usage',
    panel: Tine.Admin.QuotaManagement,
    id: 'quotamanagement',
    dataPanelType: "quotamanagement",
    hidden: !Tine.Admin.showModule('quotamanagement')
});
