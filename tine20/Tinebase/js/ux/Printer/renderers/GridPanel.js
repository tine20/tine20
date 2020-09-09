/**
 * @class Ext.ux.Printer.GridPanelRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * @author Ed Spencer
 * Helper class to easily print the contents of a grid. Will open a new window with a table where the first row
 * contains the headings from your column model, and with a row for each item in your grid's store. When formatted
 * with appropriate CSS it should look very similar to a default grid. If renderers are specified in your column
 * model, they will be used in creating the table. Override headerTpl and bodyTpl to change how the markup is generated
 */
Ext.ux.Printer.GridPanelRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {
    headlineTpl: new Ext.XTemplate(
        '<div class="gridpanelrenderer_header_title">',
            '<h1>{header_title}</h1>',
        '</div>',
        '<div class="gridpanelrenderer_header_logo">',
            '{header_logo}',
        '</div>',
        '<div class="clear"></div>'
    ),

    /**
     * Generates the body HTML for the grid
     * @param {Ext.grid.GridPanel} grid The grid to print
     */
    generateBody: function (grid, data) {
        var me = this;

        // when called inside other components the outer generateHTML (outer page renderer) didn't prepare data
        // this is the case e.g. inside editDialogs - we don't load remote data then
        data = data || me.extractData(grid, grid.store.data.items);

        return new Promise(function (fulfill, reject) {
            var columns = me.getColumns(grid);

            //use the headerTpl and bodyTpl XTemplates to create the main XTemplate below
            var headings = me.headerTpl.apply(columns);
            var body = me.bodyTpl.apply(columns);

            var installLogoImg = Tine.Tinebase.common.getUrl('full') && Tine.Tinebase.registry.get('installLogo') ? '<img src="' + Tine.Tinebase.registry.get('installLogo') + '" />' : null;

            var header = me.headlineTpl.apply({
                header_title: Tine.Tinebase.registry.get('brandingTitle'),
                header_logo: installLogoImg
            });

            if (grid.title) {
                header += '<div class="rp-print-single-block-heading">' + grid.title + '</div>';
            }

            fulfill(new Ext.XTemplate(String.format('{0}<table>{1}<tpl for=".">{2}</tpl></table>', header, headings, body)).apply(data));
        });
    },

    /**
     * Prepares data from the grid for use in the XTemplate
     * @param {Ext.grid.GridPanel} grid The grid panel
     * @return {Array} Data suitable for use in the XTemplate
     */
    prepareData: function (grid) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            //refetch data without paging
            grid.store.on('beforeload', function (store, options) {
                options.params = options.params || {};
                delete options.params.start;
                delete options.params.limit;

                // @TODO rethink - with local sort we don't need a remote query at all?
                if (! options.params.sort && !grid.store.remoteSort) {
                    var sortState = grid.store.getSortState();
                    options.params.sort = sortState.field;
                    options.params.dir = sortState.direction;
                }
            }, me, {'single': true});
            grid.store.on('beforeloadrecords', function (o, options, success, store) {
                var data = me.extractData(grid, o.records);
                fulfill(data);

                // don't touch original store!
                return false;
            }, me, {'single': true});

            grid.store.load();
        });
    },

    extractData: function (grid, records) {
        var columns = this.getColumns(grid),
            data = [];

        Ext.each(records, function (item) {
            var convertedData = {};


            Ext.each(columns, function (column) {
                var key = column.dataIndex,
                    value = item.get(key);

                convertedData[key] = column.renderer ? column.renderer(value, null, item) : value;
                convertedData[key] = convertedData[key] || '';
            });


            data.push(convertedData);
        });

        return data;
    },
    /**
     * Returns the array of columns from a grid
     * @param {Ext.grid.GridPanel} grid The grid to get columns from
     * @return {Array} The array of grid columns
     */
    getColumns: function (grid) {
        var columns = [];

        Ext.each(grid.getColumnModel().config, function (col) {
            if (col.hidden != true) columns.push(col);
        }, this);

        return columns;
    },

    /**
     * @property headerTpl
     * @type Ext.XTemplate
     * The XTemplate used to create the headings row. By default this just uses <th> elements, override to provide your own
     */
    headerTpl: new Ext.XTemplate(
        '<tr><thead>',
        '<tpl for=".">',
        '<th>{header}</th>',
        '</tpl>',
        '</thead></tr>'
    ),

    /**
     * @property bodyTpl
     * @type Ext.XTemplate
     * The XTemplate used to create each row. This is used inside the 'print' function to build another XTemplate, to which the data
     * are then applied (see the escaped dataIndex attribute here - this ends up as "{dataIndex}")
     */
    bodyTpl: new Ext.XTemplate(
        '<tr>',
        '<tpl for=".">',
        '<td>\{{dataIndex}\}</td>',
        '</tpl>',
        '</tr>'
    )
});

Ext.ux.Printer.registerRenderer('grid', Ext.ux.Printer.GridPanelRenderer);
