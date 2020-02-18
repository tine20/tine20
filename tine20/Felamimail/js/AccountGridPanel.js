Ext.ns('Tine.Felamimail');

Tine.Felamimail.AccountGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
});

Tine.widgets.grid.RendererManager.register('Felamimail', 'Account', 'type', function(type) {
    let types = Tine.Felamimail.Model.getAvailableAccountTypes(true);
    return _.get(_.find(types, {id: type}), 'value', type);
}, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);