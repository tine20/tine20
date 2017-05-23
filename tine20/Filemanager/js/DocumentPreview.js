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

    initComponent: function () {
        this.addEvents(
            /**
             * Fires if no preview is available. Later it should be used to be fired if the browser is not able to load images.
             */
            'noPreviewAvailable'
        );

        Tine.Filemanager.DocumentPreview.superclass.initComponent.apply(this, arguments);

        this.on('noPreviewAvailable', this.onNoPreviewAvailable.createDelegate(this));

        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        if (!this.record) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        this.loadPreview();
    },

    loadPreview: function () {
        var me = this;

        if ('0' === this.record.get('preview_count')) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        lodash.range(this.record.get('preview_count')).forEach(function (previewNumber) {
            var path = this.record.get('path'),
                revision = this.record.get('revision');

            var url = Ext.urlEncode({
                method: 'Filemanager.downloadPreview',
                frontend: 'http',
                _path: path,
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
        }, this);
    },

    /**
     * Fires if no previews are available
     */
    onNoPreviewAvailable: function () {
        this.html = '<b>' + this.app.i18n._('No preview available.') + '</b>';
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
