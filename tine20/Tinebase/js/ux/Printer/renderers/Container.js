
Ext.ux.Printer.ContainerRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

    /**
     * Generates the body HTML for the container
     * @param {Ext.Container} container The container to print
     */
    generateBody: function(container) {
        var _ = window.lodash;

        return _.reduce(container.items.items, function(promise, cmp) {
            return promise.then(function(html) {
                return new Promise(function(resolve, reject) {
                    var renderer = Ext.ux.Printer.findRenderer(cmp),
                        result = renderer ? renderer.generateBody(cmp) : '';

                    if (_.isString(result)) {
                        resolve(html + result);
                    } else {
                        result.then(function(string) {
                            resolve(html + string);
                        })
                    }
                });
            });
        }, Promise.resolve(''));
    }
});

Ext.ux.Printer.registerRenderer('container', Ext.ux.Printer.ContainerRenderer);
