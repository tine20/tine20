Tine.Calendar.Printer.MonthViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    paperHeight: 170,
    
    generateBody: function(view) {
        var daysHtml = this.splitDays(view.ds, view.startDate, view.dateMesh.length),
            body = [];
        
        body.push('<table><tr><th class="cal-print-title">', this.getTitle(view), '</th></tr></table>');
        
        body.push(String.format('<table class="cal-print-monthview">{0}</table>', this.generateCalRows(daysHtml, 7, true)));
   
        return body.join("\n");
    },
    
    getTitle: function(view) {
        return view.dateMesh[10].format('F Y');
    }
});