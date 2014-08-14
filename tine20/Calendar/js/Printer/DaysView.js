Tine.Calendar.Printer.DaysViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    paperHeight: 200, 
    
    printMode: 'sheet',
    
    generateBody: function(view) {
        var mode = Ext.util.Format.capitalize(this.printMode),
            method = 'generate' + mode + 'HTML';
        
        return this[method](view);
    },
    
    /**
     * Returns the HTML that will be placed into the <head> element of th print window.
     * @param {Ext.Component} component The component to render
     * @return {String} The HTML fragment to place inside the print window's <head> element
     */
    getAdditionalHeaders: function(component) {
        var calendarCSS = Tine.clientVersion.buildType.match(/DEBUG|RELEASE/) ? 
            '@import url(Calendar/css/Calendar-FAT.css);' : (
            
            '@import url(Calendar/css/daysviewpanel.css);' +
            '@import url(Calendar/css/Calendar.css);'
        );
            
        var head = 
            '<style type="text/css" title="text/css" media="screen,print">' +
                '@import url(library/ExtJS/resources/css/ext-all.css);' +
                calendarCSS +
            '</style>';
        
        return head;
    },
    
    generateSheetHTML: function(view) {
        var node = view.el.dom.cloneNode(true),
            header = node.getElementsByClassName('cal-daysviewpanel-wholedayheader-scroller')[0],
            scroller = node.getElementsByClassName('cal-daysviewpanel-scroller')[0];
        
        // resize header/scroller to fullsize
        header.style.height = [header.firstChild.style.height, header.style.height].sort().pop();
        scroller.style.height = view.dayEndPx - view.getTimeOffset(view.dayStart) + 20 + 'px';
        scroller.style.width = null;
        
        return this.generateTitle(view) + node.innerHTML;
    },
    
    generateGridHTML: function(view) {
        var daysHtml = this.splitDays(view.store, view.startDate, view.numOfDays),
            body = [];
        
        body.push(this.generateTitle(view));
        
        if (view.numOfDays === 1) {
            body.push(String.format('<div class="cal-print-day-singleday">{0}</div>', daysHtml[0]));
        } else if (view.numOfDays < 9) {
            if (view.numOfDays == 7 && view.startDate.format('w') == 1) {
                // iso week
                body.push(this.generateIsoWeek(daysHtml));
            } else {
                body.push(String.format('<table>{0}</table>', this.generateCalRows(daysHtml, 2)));
            }
        } else {
            body.push(String.format('<table>{0}</table>', this.generateCalRows(daysHtml, 2, true)));
        }
        
        return body.join("\n");
    },
    
    getTitle: function(view) {
        if (view.numOfDays == 1) {
            return String.format(view.dayFormatString + ' {3}', view.startDate.format('l'), view.startDate.format('j'), view.startDate.format('F'), view.startDate.format('Y'));
        } else {
            var endDate = view.startDate.add(Date.DAY, view.numOfDays -1),
                startDayOfMonth = view.startDate.format('j. '),
                startMonth = view.startDate.format('F '),
                startYear = view.startDate.format('Y '),
                endDayOfMonth = endDate.format('j. '),
                endMonth = endDate.format('F '),
                endYear = endDate.format('Y '),
                week = view.numOfDays == 7 ? String.format(view.app.i18n._('Week {0} :'), view.startDate.add(Date.DAY, 1).getWeekOfYear()) + ' ' : '';
                
                if (startYear === endYear) startYear = '';
                if (startMonth === endMonth) startMonth = '';
                
                return week + startDayOfMonth + startMonth + startYear + ' - ' + endDayOfMonth + endMonth + endYear;
        }
    },
  
    generateIsoWeek: function(daysHtml) {
        var height = this.paperHeight/4;
        return ['<table>',
            '<tr style="height: ' + height + 'mm;">',
                '<td class="cal-print-daycell" width="50%">', daysHtml[0], '</td>',
                '<td class="cal-print-daycell" width="50%">', daysHtml[3], '</td>',
            '</tr>', 
            '<tr style="height: ' + height + 'mm;">',
                '<td class="cal-print-daycell" width="50%">', daysHtml[1], '</td>',
                '<td class="cal-print-daycell" width="50%">', daysHtml[4], '</td>',
            '</tr>', 
            '<tr style="height: ' + height + 'mm;">',
                '<td class="cal-print-daycell" width="50%">', daysHtml[2], '</td>',
                '<td class="cal-print-daycell" width="50%">',
                    '<table style="padding: 0;">',
                        '<tr style="height: ' + height/2 + 'mm;">',
                            '<td class="cal-print-daycell" width="100%" style="padding: 0;">', daysHtml[5], '</td>',
                        '</tr>',
                        '<tr style="height: ' + height/2 + 'mm;">',
                            '<td class="cal-print-daycell" width="100%" style="padding: 0;">', daysHtml[6], '</td>',
                        '</tr>', 
                    '</table>',
            '</tr>', 
        '</table>'].join("\n");
    }
});
