/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

MediaPanel = Ext.extend(Ext.Panel, {
    border: false,

    initComponent: function() {
        const record = this.nodeRecord;

        const contentType = record.get('contenttype');
        const tag = contentType.match(/^audio/) ? 'audio' : 'video';
        const iconCls = tag === 'audio' ? Tine.Tinebase.common.getMimeIconCls(contentType) : '';
        const url = Tine.Filemanager.Model.Node.getDownloadUrl(record);

        this.html = `
        <div class="filemanager-quicklook-mediapanel ${iconCls}">
            <${tag} controls autoplay controlsList="nodownload">
              <source src="${url}" type="${contentType}" />
            </${tag}>
        </div>`;

        this.afterIsRendered().then(() => {
            this.el.on('contextmenu', (e) => {
                e.stopEvent();
                return false;
            });
            this.on('hide', (e) => {
                this.el.child(tag).dom.pause();
            });
            this.on('show', (e) => {
                this.el.child(tag).dom.currentTime=0;
                this.el.child(tag).dom.play();
            });
        });
        MediaPanel.superclass.initComponent.call(this);
    }
});

Ext.reg('Filemanager.QuickLookMediaPanel', MediaPanel);

//@TODO it should be possible to register audio/* video/*
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/basic', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/L24', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/mid', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/mpeg', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/mp4', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/x-aiff', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/x-mpegurl', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/vnd.rn-realaudio', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/ogg', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/vorbis', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/vnd.wav', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/webm', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/wav', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/wave', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/x-wav', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('audio/x-pn-wav', 'Filemanager.QuickLookMediaPanel');

Tine.Filemanager.QuickLookRegistry.registerContentType('video/mpeg', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/mp4', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/webm', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/ogg', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('application/x-mpegURL', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/3gpp', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/quicktime', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/x-msvideo', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/x-ms-wmv', 'Filemanager.QuickLookMediaPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('video/x-flv', 'Filemanager.QuickLookMediaPanel');
