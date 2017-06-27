Tine.Calendar.Printer.MonthViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    paperHeight: 155,

    generateBody: function(view) {
        var daysHtml = this.splitDays(view.store, view.dateMesh[0], view.dateMesh.length),
            body = [],
            me = this;
        return new Promise(function (fulfill, reject) {
            // try to force landscape -> opera only atm...
            body.push('<style type="text/css">',
                '@page {',
                'size:landscape',
                '}',
                '@media print {thead {display: table-header-group;}}',
                '</style>');

            // day headers
            var dayNames = [];
            for (var i = 0; i < 7; i++) {
                var d = view.startDay + i;
                if (d > 6) {
                    d = d - 7;
                }
                dayNames.push("<th class='cal-print-monthview-daycell'><span>", view.dayNames[d], "</span></th>");
            }

            body.push(
                '<table class="cal-print-monthview">',
                '<thead>',
                '<tr><th colspan="7" class="cal-print-title">', me.getTitle(view), '</th></tr>',
                '<tr>', dayNames.join("\n"), '</tr>',
                '</thead>',
                '<tbody>',
                me.generateCalRows(daysHtml, 7, true),
                '</tbody>');

            fulfill(body.join("\n"));
        });
    },
    
    getTitle: function(view) {
        return view.dateMesh[10].format('F Y');
    },
    
    dayHeadersTpl: new Ext.XTemplate(
        '<tr>',
            '<tpl for=".">',
                '<th>\{{dataIndex}\}</th>',
            '</tpl>',
        '</tr>'
    )
});
