
Ext.ux.Printer.PanelRenderer = Ext.extend(Ext.ux.Printer.ContainerRenderer, {

    /**
     * Generates the body HTML for the panel
     * @param {Ext.Panel} panel The panel to print
     */
    generateBody: function(panel) {
        return Ext.ux.Printer.PanelRenderer.superclass.generateBody.call(this, panel).then(function(html) {

            if (panel.header) {
                html = '<div class = "panel-header">' + panel.header + '</div>' + html;
            }

            return html;
        });
    }
});

Ext.ux.Printer.registerRenderer('panel', Ext.ux.Printer.PanelRenderer);
