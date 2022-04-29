/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

require('Filemanager/js/QuickLookRegistry');
require('Filemanager/js/DocumentPreview');

Tine.Filemanager.QuickLookPanel = Ext.extend(Ext.Panel, {

    /**
     * Node record to preview
     */
    record: null,

    /**
     * filemanager
     */
    app: null,

    /**
     * App which triggered this action (passed to preview panel)
     */
    initialApp: null,

    /**
     * holds Ids of record preview panels (index is record ID)
     */
    cardPanelsByRecordId: {},

    /**
     * @type Tine.Filemanager.QuickLookRegistry
     */
    registry: null,

    /**
     * Layout
     */
    layout: 'fit', // hfit?

    /**
     * @type SelectionModel
     */
    sm: null,

    /**
     * init panel
     */
    initComponent: function () {
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.registry = Tine.Filemanager.QuickLookRegistry;

        this.action_close = new Ext.Action({
            text: this.app.i18n._('Close'),
            minWidth: 70,
            scope: this,
            handler: this.onClose,
            iconCls: 'action_cancel'
        });

        this.items = [{
            ref: 'cardPanel',
            xtype: 'panel',
            layout: 'card',
            activeItem: 0,
            frame: false,
            border: false,
            items: [{keep: true, html: '<div class="tine-viewport-waitcycle">&nbsp;</div>'}]
        }];

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

        Tine.Filemanager.QuickLookPanel.superclass.initComponent.apply(this, arguments);

        var me = this;
        this.afterIsRendered().then(function () {
            me.loadPreviewPanel();
        });
    },

    /**
     * fetch/manage preview panels for records by content-type
     */
    loadPreviewPanel: async function () {
        let previewPanel = null;
        let previewPanelXtype = null;

        await this.handleAttachments();
        
        this.window.setTitle(this.record.get('name'));

        if (this.cardPanelsByRecordId[this.record.id]) {
            previewPanel = this.cardPanel.get(this.cardPanelsByRecordId[this.record.id]);
        } else {
            const fileExtension = Tine.Filemanager.Model.Node.getExtension(this.record.get('name'));
            if (this.registry.hasContentType(this.record.get('contenttype'))) {
                previewPanelXtype = this.registry.getContentType(this.record.get('contenttype'));
                Tine.log.info('Using ' + previewPanelXtype + ' to show ' + this.record.get('contenttype') + ' preview.');
                previewPanel = Ext.create({
                    xtype: previewPanelXtype,
                    nodeRecord: this.record
                });
            } else if (this.registry.hasExtension(fileExtension)) {
                previewPanelXtype = this.registry.getExtension(fileExtension);
                Tine.log.info('Using ' + previewPanelXtype + ' to show ' + this.record.get('contenttype') + ' preview.');
                previewPanel = Ext.create({
                    xtype: previewPanelXtype,
                    nodeRecord: this.record
                });
            } else {
                // use default doc preview panel
                previewPanel = new Tine.Filemanager.DocumentPreview({
                    initialApp: this.initialApp,
                    record: this.record
                });
            }
            this.cardPanelsByRecordId[this.record.id] = previewPanel.id;
            this.cardPanel.add(previewPanel);
        }

        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.cardPanel, previewPanel, true);
    },

    async handleAttachments() {
        // cope with attachments
        if (this.record.constructor.hasField('attachments')) {
            this.attachments = _.map(this.record.get('attachments'), (attachment)=> {
                return new Tine.Tinebase.Model.Tree_Node(attachment);
            });
            this.record = this.attachments[0] || new Tine.Tinebase.Model.Tree_Node({name: ''});
        }
    },

    /**
     * navigate previews
     *
     * @param e
     */
    onNavigate: function(e) {
        const key = e.getKey();
        if ([e.LEFT, e.RIGHT].indexOf(key) >= 0) {
            return this.onNavigateAttachment(key === e.LEFT ? -1 : +1);
        }

        if (this.sm) {
            switch (key) {
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
                this.loadPreviewPanel();
            }
        }
    },

    onNavigateAttachment(dir) {
        if (this.attachments?.length > 1) {
            this.record = this.attachments[((this.attachments.indexOf(this.record) || this.attachments.length) + dir)%this.attachments.length];
            this.loadPreviewPanel();
        }
    },
    
    /**
     * @private
     */
    onClose: function(){
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    }
});

Tine.Filemanager.QuickLookPanel.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    return Tine.WindowFactory.getWindow({
        width: (screen.height * 0.8) / Math.sqrt(2), // DIN A4 and so on
        height: screen.height * 0.8,
        name: Tine.Filemanager.QuickLookPanel.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.QuickLookPanel',
        contentPanelConstructorConfig: config,
        modal: false,
        windowType: 'Browser'
    });
};
