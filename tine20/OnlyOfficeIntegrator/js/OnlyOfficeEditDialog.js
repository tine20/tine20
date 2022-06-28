/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import waitFor from "util/waitFor.es6";
const { retryAllRejectedPromises } = require('promises-to-retry');

Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog = Ext.extend(Ext.Component, {

    /**
     * @cfg {String} JSON encoded node record
     */
    recordData: null,

    /**
     * @property {DocsAPI.DocEditor}
     */
    docEditor: null,

    /**
     * @private
     */
    windowNamePrefix: 'OnlyOfficeEditWindow_',
    border: false,
    style: 'height: 100%',

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('OnlyOfficeIntegrator');
        this.autoEl = {
            tag: 'div',
            cn:{tag: 'div', id: Ext.id(), cls: 'tine-viewport-waitcycle'}
        };

        this.onlyOfficeUrl = Tine.Tinebase.configManager.get('onlyOfficePublicUrl', 'OnlyOfficeIntegrator');

        this.initRecord(this.recordData);

        this.apiIsInjected = this.injectAPI();

        this.window.on('beforeclose', this.onBeforeCloseWindow, this);

        const tokenKeepAliveInterval = Tine.Tinebase.configManager.get('tokenLiveTime', 'OnlyOfficeIntegrator') * 800;
        window.setInterval(_.bind(this.tokenKeepAlive, this), tokenKeepAliveInterval);

        this.initialConfig.__OOIGetRecord = () => { return this.record };
        Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.superclass.initComponent.call(this);
    },

    initRecord: function (recordData) {
        this.record = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);

        this.isTempfile = !!_.get(this.record, 'json.session_id');
        this.isAttachment = !!String(this.record.get('path')).match(/^\/records\//);
        this.enableHistory = !this.isTempfile && Tine.Tinebase.configManager.get('filesystem').modLogActive && _.get(this.record, 'data.revisionProps.keep', true);
    },
    
    injectAPI: async function() {
        const apiUrl = this.onlyOfficeUrl + '/web-apps/apps/api/documents/api.js';

        const script = document.createElement("script");
        script.setAttribute("src", apiUrl);
        script.setAttribute("type", "text/javascript");
        document.getElementsByTagName('head')[0].appendChild(script);

        return waitFor(() => {
            return !! _.get(window, 'DocsAPI.DocEditor')
        }, 5000);
    },

    afterRender: async function() {
        const [config] = await Promise.all([this.getSignedConfig(), this.apiIsInjected]);

        this.window.onlyOfficeDocumentKey = _.get(config, 'document.key');
        this.window.onlyOfficeDocumentDirty = false;

        this.window.setTitle([Tine.title, 'ONLYOFFICE', this.record.get('name')].join(' - '));
        
        const localeParts = _.split(Tine.Tinebase.registry.get('locale').locale, '_');
        const lang = localeParts[0];
        const region = lang + '-' + localeParts[1] ? localeParts[1] : _.toUpper(lang);
        _.assign(config.editorConfig, {
            "lang": lang,
            "region": region
        });

        _.merge(config, {
            // only available in developer edition
            "customization": {
                "logo": {
                    "image": Tine.logo,
                    "imageEmbedded": Tine.logo,
                    "url": Tine.weburl
                },
            },

            "events": {
                "onDocumentStateChange": _.bind(this.onDocumentStateChange, this),
                "onRequestInsertImage": _.bind(this.onRequestInsertImage, this),
                "onOutdatedVersion": _.bind(this.onOutdatedVersion, this),
                // offer exportAs for attachments/uploads?
                "onRequestSaveAs": _.bind(this.onRequestSaveAs, this)
            }
        });

        if (this.enableHistory) {
            _.merge(config, {
                "events": {
                    "onRequestHistory": _.bind(this.onRequestHistory, this),
                    "onRequestHistoryClose": _.bind(this.onRequestHistoryClose, this),
                    "onRequestHistoryData": _.bind(this.onRequestHistoryData, this),
                    "onRequestRestore": _.bind(this.onRequestRestore, this)
                }
            });
        }

        this.docEditor = new DocsAPI.DocEditor(this.autoEl.cn.id, config);

        Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.superclass.afterRender.call(this);
    },

    onDocumentStateChange: function(e) {
        if (e.data) {
            this.window.onlyOfficeDocumentDirty = true;
        }

        // OO activity count's as user presence
        Tine.Tinebase.PresenceObserver.lastPresence = new Date().getTime();
    },

    onRequestInsertImage: function() {
        if (! this.filePickerDialog) {
            this.filePickerDialog = new Tine.Filemanager.FilePickerDialog({
                windowTitle: this.app.i18n._('Select Image'),
                singleSelect: true,
                constraint: /(jpg|gif|png|tiff)$/,
            });

            this.filePickerDialog.on('apply', async (node) => {
                const config = await Tine.OnlyOfficeIntegrator.getEmbedUrlForNodeId(node.id);

                this.docEditor.insertImage(_.assign(config, {
                    "fileType": node.path.split('.').pop(),
                }));
            });

            this.filePickerDialog.openWindow({
                closeAction: 'hide'
            });
        }

        this.filePickerDialog.window.show();
    },

    onOutdatedVersion: async function() {
        if (this.isAttachment) {
            let [, , model, recordId] = this.record.get('path').split('/');
            // arg: how to get recordProxy here in a generic way?
            window.location.reload();
        } else {
            const recordData = await Tine.Filemanager.getNode(this.record.id);
            this.record = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);
        }
        this.docEditor.destroyEditor();
        this.afterRender();
    },

    onRequestSaveAs: function(event) {
        const title = event.data.title;
        const url = event.data.url;
        const path = this.record.get('path');

        const win = new Tine.Tinebase.widgets.file.SelectionDialog.openWindow({
            windowId: 'OnlyOfficeEditDialog.SaveAs' + this.id,
            mode: 'target',
            locationTypesEnabled: 'fm_node',
            windowTitle: this.app.i18n._('Save Copy As'),
            fileName: title,
            initialPath: path.match(/^\/records\//) ? Tine.Tinebase.container.getMyFileNodePath(): path.match(/.*\//)[0],
            constraint: new RegExp('\\.' + url.split('.').pop() + '$'),
            listeners: { apply: async (node) => {
                    const path = _.get(node, 'fm_path');
                    const loadMask = new Ext.LoadMask(Ext.getBody(), {
                        msg: this.app.i18n._('Exporting Document ...'),
                        removeMask: true
                    });

                    loadMask.show();
                    const recordData = await Tine.OnlyOfficeIntegrator.exportAs(url, path, true);

                    if (_.get(recordData, 'name')) {
                        window.postal.publish({
                            channel: "recordchange",
                            topic: 'Filemanager.Node.create',
                            data: recordData
                        });
                    }
                    loadMask.hide();
                    
                    // switch to new document
                    this.record = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);
                    this.isAttachment = !!String(this.record.get('path')).match(/^\/records\//);
                    this.isTempfile = false;
                    this.docEditor.destroyEditor();
                    this.afterRender();
            }}
        });
    },

    onRequestHistory: async function() {
        const history = await Tine.OnlyOfficeIntegrator.getHistory(this.window.onlyOfficeDocumentKey);

        const keys = _.split(this.window.onlyOfficeDocumentKey, ',');
        this.window.onlyOfficeDocumentKey = _.join(_.uniq(_.compact(_.concat(keys, _.map(history, 'key')))), ',');

        this.docEditor.refreshHistory(history);
    },

    onRequestHistoryClose: async function() {
        this.onOutdatedVersion();
    },

    onRequestHistoryData: async function(event) {
        const version = event.data;
        const keys = _.split(this.window.onlyOfficeDocumentKey, ',');

        try {
            const historyData = await Tine.OnlyOfficeIntegrator.getHistoryData(_.get(keys, '[0]'), version);

            // make sure history tokens are keeped alive
            keys.push(_.get(historyData, 'key'));
            keys.push(_.get(historyData, 'previous.key'));
            this.window.onlyOfficeDocumentKey = _.join(_.uniq(_.compact(keys)), ',');

            this.docEditor.setHistoryData(historyData);
        } catch (err) {
            this.docEditor.setHistoryData({
                "error": this.app.i18n._(`Version details for version ${version} not available.`),
                "version": version
            });
        }
    },

    // @TODO: let user choose where to restore
    //        don't forget attachments
    onRequestRestore: async function(event) {
        const url = event.data.url;
        const version = event.data.version;
        const fileLocationSrc = {
            type: this.isAttachment ? 'attachement' : 'fm_node',
            revision: version
        };

        if (this.isAttachment) {
            const [, , model, recordId] = this.record.get('path').split('/');
            _.assign(fileLocationSrc, {
                model: model,
                record_id: recordId,
                file_name: this.record.get('name')
            });
        } else {
            fileLocationSrc.fm_path = this.record.get('path');
        }

        const title = this.app.formatMessage('Overwrite Existing File?');
        const msg = this.app.formatMessage('You are about to replace "{filename}" by the old version {version}. All newer changes will be lost. Do you want to continue?', {
            version: version,
            filename: this.record.get('name')
        });

        const confirm = await new Promise ((resolve) => {
            Ext.MessageBox.confirm(title, msg, (btn) => {
                resolve(btn === 'yes');
            });
        });

        if (confirm) {
            const loadMask = new Ext.LoadMask(this.getEl(), {
                msg: this.app.formatMessage('Restoring ...'),
                removeMask: true
            });
            loadMask.show();


            await Tine.Tinebase.restoreRevision(fileLocationSrc);
            loadMask.hide();

            this.onRequestHistoryClose();
        }
    },

    onBeforeCloseWindow: function() {
        /**
         * NOTE: Tine.OnlyOfficeIntegrator.tokenSignOut is done in Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.onEditorWindowClose
         *       as we need to use the main/parent window as this window can't process the request any more
         */
    },

    getSignedConfig: async function() {
        if (this.createNewPromise) {
            this.initRecord(await this.createNewPromise);
        }

        const recordId = this.record.getId();
        let config = null;
        
        // we see this in the logs, reloaded opener?
        if (! recordId) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR,
                title: this.app.i18n._('No Document Specified'),
                msg: this.app.i18n._('Something went wrong, please try again later.'),
                fn: () => {this.window.close()}
            });
            
            return config;
        }
        
        try {
            if (this.isAttachment) {
                let [, , model, recordId] = this.record.get('path').split('/');
                config = await Tine.OnlyOfficeIntegrator.getEditorConfigForAttachment(model, recordId, this.record.id);
            } else if (this.isTempfile) {
                config = await Tine.OnlyOfficeIntegrator.getEditorConfigForTempFileId(recordId, this.record.get('name'));
            } else {
                config = await Tine.OnlyOfficeIntegrator.getEditorConfigForNodeId(recordId);
            }
        } catch(err) {
            // if (_.get(err, 'data.code') === 647) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('Outdated Revision'),
                msg: this.app.i18n._('This revision of the document is outdated, please open the latest revision'),
                fn: () => {this.window.close()}
            });
        }

        return config;
    },

    tokenKeepAlive: function() {
        const keys = _.compact(_.split(this.window.onlyOfficeDocumentKey, ','));
        if (! keys.length) return;
        
        retryAllRejectedPromises([_.partial(Tine.OnlyOfficeIntegrator.tokenKeepAlive, keys)], {
            maxAttempts: 5, delay: 1200
        });
    },
    
    // fake container
    setSize: Ext.emptyFn,
    doLayout: Ext.emptyFn
});

Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow = function(config) {
    const win = Tine.WindowFactory.getWindow({
        width: 1024,
        height: 768,
        name: Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.prototype.windowNamePrefix + config.id,
        contentPanelConstructor: 'Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog',
        contentPanelConstructorConfig: config
    });

    win.on('beforeclose', (w) => {
        const record = config.__OOIGetRecord();
        Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.onEditorWindowClose(win, {... record.data});
    });

    return win;
};

Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.onEditorWindowClose = async function(win, recordData) {
    const app = Tine.Tinebase.appMgr.get('OnlyOfficeIntegrator');
    const keys = _.compact(_.split(win.onlyOfficeDocumentKey, ','));
    const dirty = win.onlyOfficeDocumentDirty;
    const record = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);
    const isTempFile  = !!_.get(record, 'json.session_id') || !!_.get(record, 'data.path', '').match(/^\/tempFile\/.*/);
    const isAttachment = !isTempFile && String(record.get('path')).match(/^\/records\//);
    let loadMask = false;

    if (keys.length) {
        _.each(keys, (key) => {
            Tine.OnlyOfficeIntegrator.tokenSignOut(key);
        });

        if (dirty) {
            if  (isTempFile) {
                loadMask = new Ext.LoadMask(Ext.getBody(), {
                    msg: app.i18n._('Saving Document ...'),
                    removeMask: true
                });
                loadMask.show();
            }

            const saveResult = await Tine.OnlyOfficeIntegrator.waitForDocumentSave(keys[0], 20);

            const model = isTempFile ? 'Tinebase.TempFile' :
                isAttachment ? 'Tinebase.Tree_Node' :
                    'Filemanager.Node';

            window.postal.publish({
                channel: "recordchange",
                topic: model + '.update',
                data: saveResult
            });
        }

        if (loadMask) {
            loadMask.hide();
        }
    }
}
