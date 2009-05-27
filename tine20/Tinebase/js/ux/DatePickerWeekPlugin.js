/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Ext.ux');

Ext.ux.DatePickerWeekPlugin = function(config) {
    Ext.apply(this, config || {});
    
};

Ext.ux.DatePickerWeekPlugin.prototype = {
    
    init: function(picker) {
        picker.onRender = picker.onRender.createSequence(this.onRender, picker);
        picker.update = picker.update.createSequence(this.update, picker);
        picker.handleDateClick = picker.handleDateClick.createSequence(this.handleDateClick, picker);
    },
    
    onRender: function() {
        var innerCal = Ext.DomQuery.selectNode('table[class=x-date-inner]', this.getEl().dom);
        var trs = Ext.DomQuery.select('tr', innerCal);
        for (var i=0; i<trs.length; i++) {
            Ext.DomHelper.insertFirst(trs[i], i==0 ? '<th >WK</th>' : '<td class="x-date-picker-wk"><a class="x-date-date" tabindex="1" hidefocus="on" href="#"><em><span>'+ i +'</span></em></td>');
        }
        
        // update again;
        this.update(this.value);
        
        // shit, datePicker is not on BaxComponent ;-(
        //this.picker.getEl().container.on('resize', function() {
        //    console.log(this.picker.getEl());
        //}, this);
    },
    
    update: function(date){
        var firstOfMonth = date.getFirstDateOfMonth();
        var startingPos = firstOfMonth.getDay()-this.startDay;
        if(startingPos <= this.startDay) {
            startingPos += 7;
        }
        
        // NOTE "+1" to ensure ISO week!
        var startDate = firstOfMonth.add(Date.DAY, -1*startingPos + 1);
        var wkCells = Ext.DomQuery.select('td[class=x-date-picker-wk]', this.getEl().dom);
        for (var i=0; i<wkCells.length; i++) {
            wkCells[i].firstChild.firstChild.firstChild.innerHTML = startDate.add(Date.DAY, i*7).getWeekOfYear();
        }
    },
    
    handleDateClick: function(e) {
        target = e.getTarget('td[class=x-date-picker-wk]');
        if (target) {
            var row = target.parentNode;
            var weekNumber = target.firstChild.firstChild.firstChild.innerHTML;
            
            if (Ext.DomQuery.select('td[class=x-date-prevday]', row).length > 3 ) {
                this.showPrevMonth()
            } else if (Ext.DomQuery.select('td[class=x-date-nextday]', row).length > 3) {
                this.showNextMonth()
            } 
            
            // clear all selections
            var dates = Ext.DomQuery.select('td', this.getEl().dom);
            for (var i=0; i<dates.length; i++) {
                Ext.fly(dates[i]).removeClass('x-date-selected');
            }
            
            // get row again and select all dates in it
            var wktds = Ext.DomQuery.select('td[class=x-date-picker-wk]', this.getEl().dom);
            for (var i=0; i<wktds.length; i++) {
                if (wktds[i].firstChild.firstChild.firstChild.innerHTML == weekNumber) {
                    break;
                }
            }
            var row = wktds[i].parentNode;
            for (var j=1; j<row.childNodes.length; j++) {
                Ext.fly(row.childNodes[j]).addClass('x-date-selected');
            }
            
            this.fireEvent("select", this, this.value, true);
        }
    }
};