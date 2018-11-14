/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.DocumentPreview = Ext.extend(Ext.FormPanel, {
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
     * Required for overflow auto
     */
    autoScroll: true,

    /**
     * Overflow auto to enable scrollbar automatically
     */
    overflow: 'auto',

    /**
     * Layout
     */
    layout: 'hfit',

    /**
     * Enable scrollbar
      */
    containsScrollbar: true,

    /**
     * gray fbar
     */
    cls: 'tw-editdialog',

    initComponent: function () {
        this.addEvents(
            /**
             * Fires if no preview is available. Later it should be used to be fired if the browser is not able to load images.
             */
            'noPreviewAvailable'
        );

        this.on('noPreviewAvailable', this.onNoPreviewAvailable, this);

        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.action_close = new Ext.Action({
            text: this.app.i18n._('Close'),
            minWidth: 70,
            scope: this,
            handler: this.onClose,
            iconCls: 'action_cancel'
        });

        this.fbar = ['->', this.action_close];

        Ext.getBody().on('keydown', function (e) {
            switch (e.getKey()) {
                case e.SPACE:
                case e.ESC:
                    this.onClose();
                    break;
                case e.DOWN:
                case e.UP:
                case e.LEFT:
                case e.RIGHT:
                    this.onNavigate(e);
                    break;
                default:
                    break;
            }
        }, this);

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
        if (! recordClass.hasField('preview_count') && recordClass.hasField('attachments')) {
            _.each(this.record.get('attachments'), function(attachmentData) {
                records.push(new Tine.Tinebase.Model.Tree_Node(attachmentData));
            });
        } else if (this.record.get('preview_count')) {
            records.push(this.record);
        } else if (this.hasEmailPreview(this.record)) {
            // @todo fake preview count needed?
            this.record.set('preview_count', 1);

            records.push(this.record);
        }

        records = _.filter(records, function(record) {
            return !!record.get('preview_count');
        });

        if (! records.length) {
            this.fireEvent('noPreviewAvailable');
            return;
        }
        
        this.removeAll(true);
        
        this.afterIsRendered().then(function () {
            _.each(records, function(record) {
                if (me.hasEmailPreview(record)) {
                    me.addEmailDetailsPanel(me, record);
                } else {
                    me.addPreviewPanelForRecord(me, record);
                }
            });
            
            me.doLayout();
        });
    },

    addPreviewPanelForRecord: function (me, record) {
        var _ = window.lodash;
        _.range(record.get('preview_count')).forEach(function (previewNumber) {
            var path = record.get('path'),
                revision = record.get('revision');

            var url = Ext.urlEncode({
                method: 'Tinebase.downloadPreview',
                frontend: 'http',
                _path: path,
                _appId: me.initialApp ? me.initialApp.id : me.app.id,
                _type: 'previews',
                _num: previewNumber,
                _revision: revision
            }, Tine.Tinebase.tineInit.requestUrl + '?');

            me.add({
                html: '<img style="width: 100%;" src="' + url + '" />',
                xtype: 'panel',
                frame: true,
                border: true
            });
        });
    },

    addEmailDetailsPanel: function (me, node) {
        require('Felamimail/js/MailDetailsPanel');

        let detailsPanel = new Tine.Felamimail.MailDetailsPanel({
            height: 830, // @todo auto
            autoscroll: true, // @todo scollbar!
            appName: 'Filemanager'
        });
        me.add(detailsPanel);

        Tine.Felamimail.messageBackend.getMessageFromNode(node, {
            success: function(response) {
                // TODO make it work
                var message = Tine.Felamimail.messageBackend.recordReader({responseText: Ext.util.JSON.encode(response.data)});
                this.loadRecord(message.data);
            },
            failure: function (exception) {
                Tine.log.debug(exception);
                // @todo add loadMask?
                // this.getLoadMask().hide();
                // if (exception.code == 404) {
                    this.defaultTpl.overwrite(body, {msg: this.app.i18n._('Message not available.')});
                // } else {
                //     // @todo handle exception?
                // }
            },
            scope: detailsPanel
        });
    },

    hasEmailPreview: function (fileNode) {
        if (! Tine.Tinebase.common.hasRight('run', 'Felamimail')) {
            // needs Felamimail
            return false;
        }

        // define email content-types
        const emailContentTypes = [
            'message/rfc822'
        ];
        if (emailContentTypes.indexOf(fileNode.get('contenttype')) !== -1) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * Fires if no previews are available
     *
     * @todo show more information about preview service + configuration
     */
    onNoPreviewAvailable: function () {
        var me = this;
        me.afterIsRendered().then(function() {
            me.removeAll(true);
            me.add({
                html: '<b>' + me.app.i18n._('No preview available.') + '</b>',
                xtype: 'panel',
                frame: true,
                border: true
            });
            me.doLayout();
        });
    },

    onNavigate: function(e) {
        if (this.sm) {
            switch (e.getKey()) {
                case e.DOWN:
                    this.sm.selectNext();
                    break;
                case e.UP:
                    this.sm.selectPrevious();
                    break;
                default:
                    break;
            }

            if (this.sm.getSelected() !== this.record) {
                this.record = this.sm.getSelected();
                this.removeAll(true);
                this.loadPreview();
            }

        }
    },
    /**
     * @private
     */
    onClose : function(){
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    }
});

Tine.Filemanager.DocumentPreview.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    return Tine.WindowFactory.getWindow({
        width: (screen.height * 0.8) / Math.sqrt(2), // DIN A4 and so on
        height: screen.height * 0.8,
        name: Tine.Filemanager.DocumentPreview.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.DocumentPreview',
        contentPanelConstructorConfig: config,
        modal: false,
        windowType: 'Browser'
    });
};
