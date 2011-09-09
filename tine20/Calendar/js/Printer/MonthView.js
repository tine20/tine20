Tine.Calendar.Printer.MonthViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    paperHeight: 155,
    
    generateBody: function(view) {
        var daysHtml = this.splitDays(view.ds, view.dateMesh[0], view.dateMesh.length),
            body = [];
        
        // try to force landscape -> opera only atm...
        body.push('<style type="text/css">', 
            '@page {',
                'size:landscape',
            '}',
        '</style>');
        
        // title
        body.push('<table><tr><th class="cal-print-title">', this.getTitle(view), '</th></tr></table>');
        
        // day headers
        var dayNames = [];
        for(var i = 0; i < 7; i++){
            var d = view.startDay+i;
            if(d > 6){
                d = d-7;
            }
            dayNames.push("<td class='cal-print-monthview-daycell'><span>", view.dayNames[d], "</span></td>");
        }
        
        // body
        body.push(String.format('<br/><table class="cal-print-monthview"><tr>{0}</thead>{1}</tr>', dayNames.join("\n"), this.generateCalRows(daysHtml, 7, true)));
   
        return body.join("\n");
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
