Ext.ns('Tine.Calendar.Printer');


/**
 * @class   Tine.Calendar.Printer.BaseRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * 
 * Printig renderer for Ext.ux.printing
 */
Tine.Calendar.Printer.BaseRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {
    stylesheetPath: 'Calendar/css/print.css',
//    generateBody: function(view) {
//        var days = [];
//        
//        // iterate days
//        for (var dayStart, dayEnd, dayEvents, i=0; i<view.numOfDays; i++) {
//            dayStart = view.startDate.add(Date.DAY, i);
//            dayEnd   = dayStart.add(Date.DAY, 1).add(Date.SECOND, -1);
//            
//            // get events in this day
//            dayEvents = view.ds.queryBy(function(event){
//                return event.data.dtstart.getTime() < dayEnd.getTime() && event.data.dtend.getTime() > dayStart.getTime();
//            });
//            
//            days.push(this.generateDay(dayStart, dayEnd, dayEvents));
//        }
//        
//        var topic = this.generateHeader(view);
//        var body  = 
//        return view.numOfDays === 1 ? days[0] : String.format('<table>{0}</table>', this.generateCalRows(days, view.numOfDays < 9 ? 2 : 7));
//    },
    
    generateCalRows: function(days, numCols, alignHorizontal) {
        var row, col, cellsHtml, idx,
            numRows = Math.ceil(days.length/numCols),
            rowsHtml = '';
        
        for (row=0; row<numRows; row++) {
            cellsHtml = '';
            //offset = row*numCols;
            
            for (col=0; col<numCols; col++) {
                idx = alignHorizontal ? row*numCols + col: col*numRows + row;
                cellsHtml += String.format('<td class="cal-print-daycell" style="vertical-align: top;">{0}</td>', days[idx] || '');
            }
            
            rowsHtml += String.format('<tr class="cal-print-dayrow" style="height: {1}mm">{0}</tr>', cellsHtml, this.paperHeight/numRows);
        }
        
        return rowsHtml;
    },
    
    generateDay: function(dayStart, dayEnd, dayEvents) {
        var dayBody = '';
        
        dayEvents.each(function(event){
            var start = event.data.dtstart.getTime() <= dayStart.getTime() ? dayStart : event.data.dtstart,
                end   = event.data.dtend.getTime() > dayEnd.getTime() ? dayEnd : event.data.dtend;
            
            dayBody += this.eventTpl.apply({
                color: event.colorSet.color,
                startTime: event.data.is_all_day_event ? '' : start.format('H:i'),
                untilseperator: event.data.is_all_day_event ? '' : '-',
                endTime: event.data.is_all_day_event ? '' : end.format('H:i'),
                summary: Ext.util.Format.htmlEncode(event.data.summary),
                duration: event.data.is_all_day_event ? Tine.Tinebase.appMgr.get('Calendar').i18n._('whole day') : 
                    Tine.Tinebase.common.minutesRenderer(Math.round((end.getTime() - start.getTime())/(1000*60)), '{0}:{1}', 'i')
            });
        }, this);
        
        var dayHeader = this.dayTpl.apply({
            dayOfMonth: dayStart.format('j'),
            weekDay: dayStart.format('l')
        });
        return String.format('<table class="cal-print-daysview-day"><tr>{0}</tr>{1}</table>', dayHeader, dayBody);
    },
    
    splitDays: function(ds, startDate, numOfDays, returnData) {
        var days = [];
        
        // iterate days
        for (var dayStart, dayEnd, dayEvents, i=0; i<numOfDays; i++) {
            dayStart = startDate.add(Date.DAY, i);
            dayEnd   = dayStart.add(Date.DAY, 1).add(Date.SECOND, -1);
            
            // get events in this day
            dayEvents = ds.queryBy(function(event){
                return event.data.dtstart.getTime() < dayEnd.getTime() && event.data.dtend.getTime() > dayStart.getTime();
            });
            
            days.push(returnData ? {
                dayStart: dayStart,
                dayEnd: dayEnd,
                dayEvents: dayEvents
            } : this.generateDay(dayStart, dayEnd, dayEvents));
        }
        
        return days;
    },
    
    dayTpl: new Ext.XTemplate(
        '<tr>',
            '<th  colspan="5">',
                '<span class="cal-print-daysview-day-dayOfMonth">{dayOfMonth}</span>',
                '<span class="cal-print-daysview-day-weekDay">{weekDay}</span>',
            '</th>', 
        '</tr>'
    ),
    
    /**
     * @property eventTpl
     * @type Ext.XTemplate
     * The XTemplate used to create the headings row. By default this just uses <th> elements, override to provide your own
     */
    eventTpl: new Ext.XTemplate(
        '<tr>',
            '<td class="cal-print-daysview-day-color"><span style="color: {color};">&#9673;</span></td>',
            '<td class="cal-print-daysview-day-starttime">{startTime}</td>',
            '<td class="cal-print-daysview-day-untilseperator">{untilseperator}</td>',
            '<td class="cal-print-daysview-day-endtime">{endTime}</td>',
            '<td class="cal-print-daysview-day-summary">{summary} (<span class="cal-print-daysview-day-duration">{duration}</span>)</td>',
        '</tr>'
    )
    
});
