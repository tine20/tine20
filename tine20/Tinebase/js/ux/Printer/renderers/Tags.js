
Ext.ux.Printer.TagsRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

    /**
     * Generates the body HTML for the tagsPanel
     * @param {Tine.widgets.tags.TagPanel} tagsPanel
     */
    generateBody: function(tagsPanel) {
        var _ = window.lodash;

        return '<div class="cal-print-single-block-heading">' + i18n._('Tags') + '</div>' +
               '<div class="rp-print-single-block rp-print-single-tags">' +
                    tagsPanel.dataView.el.dom.innerHTML +
               '</div><br class="x-clear"><br>';
    }
});

Ext.ux.Printer.registerRenderer('Tine.widgets.tags.TagPanel', Ext.ux.Printer.TagsRenderer);
