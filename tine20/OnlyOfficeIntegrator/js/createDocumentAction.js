require('../styles/onlyoffice.less')

Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Filemanager'),
    Tine.Tinebase.appMgr.isInitialised('OnlyOfficeIntegrator')
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('OnlyOfficeIntegrator');
    
    const createDocument = async function (type) {
        const baseAction = this.ownerCt.ownerCt.baseAction;
        const action = baseAction.initialConfig;
        const filteredContainers = action.filteredContainers;
        const path = await action.getPath(baseAction, type);
        const name = _.isFunction(action.getName) ? await action.getName(baseAction, type) : null;
        const createNewPromise = Tine.OnlyOfficeIntegrator.createNew(type, path, name);
        const win = Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({
            recordData: JSON.stringify({}),
            id: new Date().getTime(),
            createNewPromise: createNewPromise
        });

        const recordData = await createNewPromise;
        
        recordData.path = Tine.Filemanager.Model.Node.sanitize(path + '/' + recordData.name);
        
        if (_.isFunction(action.onNewNode)) {
            action.onNewNode(recordData, baseAction);
        }
    };

    const createDocumentConfig = {
        app: app,
        allowMultiple: true,
        disabled: true,
        iconCls: 'action_onlyoffice_add',
        scope: this,
        text: app.i18n._('Add Document'),
        menu: [{
            text: app.i18n._('Add Text'),
            iconCls: 'mime-type-application-slash-vnd-dot-msword',
            handler: _.partial(createDocument, 'text')
        }, {
            text: app.i18n._('Add Spreadsheet'),
            iconCls: 'mime-type-application-slash-vnd-dot-ms-excel',
            handler: _.partial(createDocument, 'spreadsheet')
        }, {
            text: app.i18n._('Add Presentation'),
            iconCls: 'mime-type-application-slash-vnd-dot-ms-powerpoint',
            handler: _.partial(createDocument, 'presentation')
        }],
        actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
            const recordData = _.get(filteredContainers, '[0]');
            const isAttachment = _.get(recordData, 'container_id.account_grants', false);

            // hack for attachments where grants are not evaluated
            let enabled = _.get(action, 'isUploadGrid', false);
            if (isAttachment) {
                enabled =_.get(recordData, 'container_id.account_grants.editGrant', false);
            } else if (recordData) {
                const node = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);
                enabled = Tine.Filemanager.nodeActionsMgr.checkConstraints('create', node, [{type: 'file'}])
                    && node?.data?.path !== '/shared/';
            }

            action.baseAction.filteredContainers = filteredContainers;

            action.setDisabled(!enabled);
            action.baseAction.setDisabled(!enabled); // WTF?
        }
    };

    // filemanager -> path
    const fileManagerCreateDocumentAction = new Ext.Action(Ext.applyIf({
        getPath: async function (baseAction, type) {
            return _.get(baseAction, 'filteredContainers[0].path');
        },
        getName: async function (baseAction, type) {
            return new Promise((resolve) => {
                const nameMap = {
                    'text': ['New Document', '.docx'],
                    'spreadsheet': ['New Spreadsheet', '.xlsx'],
                    'presentation': ['New Presentation', '.pptx']
                };
                const grid = baseAction.initialConfig.selectionModel.grid.ownerCt.ownerCt;
                const localDocument = new Tine.Filemanager.Model.Node(Tine.Filemanager.Model.Node.getDefaultData({
                    name: app.i18n._hidden(nameMap[type][0]) + nameMap[type][1],
                    type: 'file',
                    account_grants: {
                        addGrant: true,
                        editGrant: true,
                        deleteGrant: true
                    },
                }));
                grid.newInlineRecord(localDocument, 'name', async (localDocument) => {
                    const name = String(localDocument.get('name')).replace(new RegExp(nameMap[type][1] + '$'), '');
                    const folderPath = _.get(baseAction, 'filteredContainers[0].path');
                    localDocument.data.path = `${folderPath}${name}${nameMap[type][1]}`;
                    
                    resolve(name);
                    
                    // we need to return a promise for gridPanel here as it needs to remove the local record!
                    return new Promise((resolve) => {
                        baseAction.initialConfig.onNewNode = (recordData) => {
                            const remoteDocument = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node)
                            resolve(remoteDocument);
                        };
                    });
                });
            });
        }
    }, createDocumentConfig));
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ContextMenu', fileManagerCreateDocumentAction, 2);
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ActionToolbar-leftbtngrp', Ext.apply(new Ext.Button(fileManagerCreateDocumentAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }), 2);


    // upload grids -> tempFile
    const uploadGridCreateDocumentAction = new Ext.Action(Ext.applyIf({
        isUploadGrid: true,
        getPath: async function (baseAction) {
            return 'tempFile';
        },
        onNewNode: function (recordData) {
            window.postal.publish({
                channel: "recordchange",
                topic: 'Tinebase.TempFile.create',
                data: recordData
            });
        }
    }, createDocumentConfig));
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-Toolbar', uploadGridCreateDocumentAction, 5);
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-ContextMenu', uploadGridCreateDocumentAction, 5);

    // quickadds -> in personal folder
    const quickAddCreateDocumentAction = new Ext.Action(Ext.applyIf({
        disabled: false,
        getPath: async function (baseAction) {
            return 'personal';
        }
    }, createDocumentConfig));
    Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', quickAddCreateDocumentAction);

});
