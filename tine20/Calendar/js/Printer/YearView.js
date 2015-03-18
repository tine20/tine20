Tine.Calendar.Printer.YearViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    paperHeight: 155,

    generateBody: function(view) {
        var body = [];
        
        // try to force landscape -> opera only atm...
        body.push('<style type="text/css">', 
            '@page {',
                'size:landscape',
            '}',
            '@media print {thead {display: table-header-group;}}',
        '</style>');
        
	var monthNames = [];
        monthNames.push("<th class='cal-print-yearview-daycell'><span></span></th>");
        for(var i = 0; i < 12; i++){
           monthNames.push("<th class='cal-print-yearview-daycell'><span>", view.monthNames[i], "</span></th>");
        }
 
        var daysHtml = [];
	for(i=0; i< view.dayCells.length; i++)
        {
            if(i %12 == 0)
                daysHtml.push(i/12+1);

            celltext = view.dayCells[i].innerText;
            if(celltext.length > 3 )
                daysHtml.push(celltext.substring(celltext.indexOf("\n")));
            else
                daysHtml.push("");
        }

        body.push(
        '<table class="cal-print-yearview">',
            '<thead>',
                '<tr><th colspan="13" class="cal-print-title">', this.getTitle(view), '</th></tr>',
                '<tr>', monthNames.join("\n"), '</tr>',

            '</thead>',
            '<tbody>',
                this.generateCalRows(daysHtml, 13, true, true),
            '</tbody>');
            
        return body.join("\n");

    },
    
    getTitle: function(view) {
        return view.dateMesh[10].format('Y');
    },
    
    dayHeadersTpl: new Ext.XTemplate(
        '<tr>',
            '<tpl for=".">',
                '<th>\{{dataIndex}\}</th>',
            '</tpl>',
        '</tr>'
    )
});
