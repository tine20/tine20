/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.DocumentPreview = Ext.extend(Ext.Panel, {
    /**
     * Node record to preview
     */
    record: null,

    /**
     * filemanager
     */
    app: null,

    /**
     * App which triggered this action
     */
    initialApp: null,

    /**
     * Layout
     */
    layout: 'fit',

    nativelySupported: [
        'image/jpeg', 'image/png', 'image/gif', 'image/apng', 'image/avif', 'image/svg+xml', 'image/webp',
    ],

    initComponent: function () {
        this.addEvents(
            /**
             * Fires if no preview is available. Later it should be used to be fired if the browser is not able to load images.
             */
            'noPreviewAvailable'
        );

        this.on('noPreviewAvailable', this.onNoPreviewAvailable, this);
        this.on('keydown', this.onKeydown, this);

        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.afterIsRendered().then(() => {
            this.el.on('contextmenu', (e) => {
                e.stopEvent();
                return false;
            })
        });

        Tine.Filemanager.DocumentPreview.superclass.initComponent.apply(this, arguments);

        if (!this.record) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        this.loadPreview();
    },

    loadPreview: function () {
        var _ = window.lodash,
            me = this,
            recordClass = this.record.constructor,
            records = [];

        // attachments preview
        this.isAttachment = this.record.get('path').match(/^\/records/);
        if (! recordClass.hasField('preview_count') && recordClass.hasField('attachments')) {
            _.each(this.record.get('attachments'), function(attachmentData) {
                records.push(new Tine.Tinebase.Model.Tree_Node(attachmentData));
            });
        } else {
            records.push(this.record);
        }

        records = _.filter(records, (record) => {
            return this.useOriginal(record) || !!+record.get('preview_count');
        });

        if (! records.length) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        me.add(this.previewContainer = new Ext.Panel({
            layout: 'anchor',
            bodyStyle: 'overflow-y: scroll;'
        }));
        this.afterIsRendered().then(async () => {

            const isRendered = records.map((record) => {
                return me.addPreviewPanelForRecord(me, record);
            });

            await Promise.all(isRendered);
        });
    },

    useOriginal: function(record) {
        const useOriginalSizeLimit = 0.5*1024*1024; // @TODO have pref or conf
        const nativelySupported = this.nativelySupported.indexOf(record.get('contenttype')) >=0;
        return nativelySupported && (record.get('size') <= useOriginalSizeLimit || !+record.get('preview_count'));
    },

    addPreviewPanelForRecord: function (me, record) {
        const path = record.get('path');
        const revision = record.get('revision');
        const urls = [];

        if (this.useOriginal(record)) {
            urls.push(Tine.Filemanager.Model.Node.getDownloadUrl(record));
        } else {
            _.range(record.get('preview_count')).forEach((previewNumber) => {
                urls.push(Ext.urlEncode({
                    method: 'Tinebase.downloadPreview',
                    frontend: 'http',
                    _path: path,
                    _appId: me.initialApp ? me.initialApp.id : me.app.id,
                    _type: 'previews',
                    _num: previewNumber,
                    _revision: revision
                }, Tine.Tinebase.tineInit.requestUrl + '?'));
            });
        }

        const isRendered = urls.map((url) => {
            return new Promise((resolve) => {
                me.previewContainer.add({
                    html: '<img style="width: 100%;" src="' + url + '" /><div class="filemanager-quicklook-protect" />',
                    xtype: 'panel',
                    frame: true,
                    border: true,
                    afterRender: resolve
                });
            });

        });

        me.doLayout();
        return Promise.all(isRendered);
    },

    onKeydown: function(e) {
        const key = e.getKey();
        if (key.constrain(33, 36) === key) {
            const scrollEl = this.previewContainer.el.child('.x-panel-body');
            if (key < 35) {
                scrollEl.scroll(key === e.PAGE_UP ? 'up' : 'down', Math.round(scrollEl.getHeight()*0.9), true);
            } else {
                scrollEl.scrollTo('top', key === e.HOME ? 0 : scrollEl.dom.scrollHeight);
            }
        }
    },

    /**
     * Fires if no previews are available
     */
    onNoPreviewAvailable: function () {
        var me = this;
        me.afterIsRendered().then(function() {
            let text = '';
            let contenttype =  me.record.get('contenttype');
            let iconCls = me.record.get('type') === 'folder' ? 'mime-icon-folder' :
                contenttype ? Tine.Tinebase.common.getMimeIconCls(contenttype) : 'mime-icon-file';

            if (!Tine.Tinebase.configManager.get('filesystem').createPreviews) {
                text = '<b>' + me.app.i18n._('Sorry, Tine 2.0 would have liked to show you the contents of the file.') + '</b><br/><br/>' +
                    me.app.i18n._('This is possible for .doc, .jpg, .pdf and even more file formats.') + '<br/>' +
                    '<a href="https://www.tine20.com/kontakt/" target="_blank">' +
                    me.app.i18n._('Interested? Then let us know!') + '</a><br/>' +
                    me.app.i18n._('We would be happy to make you a non-binding offer.');
            } else if (String(contenttype).match(/^vnd\.adobe\.partial-upload.*/)) {
                const [, final] = contenttype.match(/final_type=(.+)$/);
                iconCls = final ? Tine.Tinebase.common.getMimeIconCls(final) : 'mime-icon-file';
                text = '<b>' + me.app.i18n._('This file has no contents. The Upload has failed or has not yet finished.') + '</b>';
            } else if (!contenttype) { // how to get all previewable types?
                text = '<b>' + me.app.i18n._('No preview available.') + '</b>';
            } else {
                text = '<b>' + me.app.i18n._('No preview available yet - Please try again in a few minutes.') + '</b>';
            }

            me.add({
                border: false,
                layout: 'vbox',
                layoutConfig: {
                    align: 'stretch'
                },
                items: [{
                    html: text,
                    frame: true,
                    border: true
                }, {
                    border: false,
                    flex: 1,
                    xtype: 'container',
                    cls: iconCls,
                    style: 'background-repeat: no-repeat; background-position: center; background-size: contain;'
                }]
            });

            me.doLayout();
        });
    }
});
