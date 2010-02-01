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
    /**
     * @cfg {String} weekHeaderString
     */
    weekHeaderString: 'WK',
    
    init: function(picker) {
        picker.onRender = picker.onRender.createSequence(this.onRender, picker);
        picker.update = picker.update.createSequence(this.update, picker);
        picker.handleDateClick = picker.handleDateClick.createSequence(this.handleDateClick, picker);
        picker.showMonthPicker = picker.showMonthPicker.createInterceptor(this.inspectMonthPickerClick, picker);
        picker.weekHeaderString = this.weekHeaderString;
        
        picker.getRowEl = this.getRowEl.createDelegate(picker);
        picker.selectWeek = this.selectWeek.createDelegate(picker);
        picker.clearSelection = this.clearSelection.createDelegate(picker);
        picker.onWkCellOver = this.onWkCellOver.createDelegate(picker);
        picker.onWkCellOut = this.onWkCellOut.createDelegate(picker);
    },
    
    onRender: function() {
        var innerCal = Ext.DomQuery.selectNode('table[class=x-date-inner]', this.getEl().dom);
        var trs = Ext.DomQuery.select('tr', innerCal);
        for (var el, i=0; i<trs.length; i++) {
            el = Ext.DomHelper.insertFirst(trs[i], i==0 ? '<th class="x-date-picker-wk-hd">' + this.weekHeaderString + '</th>' : '<td class="x-date-picker-wk"><a class="x-date-date" tabindex="1" hidefocus="on" href="#"><em><span>'+ i +'</span></em></td>');
            Ext.get(el).addListener({
                scope: this,
                mouseover: this.onWkCellOver,
                mouseout: this.onWkCellOut
            });
        }
        
        // update again;
        this.update(this.value);
        
        // shit, datePicker is not on BaxComponent ;-(
        //this.picker.getEl().container.on('resize', function() {
        //    console.log(this.picker.getEl());
        //}, this);
    },
    
    onWkCellOver: function(e, t) {
        var tr = e.getTarget('tr', 10 , true);
        tr.addClass('x-date-picker-wk-wkrowover');
    },
    
    onWkCellOut: function(e, t) {
        var tr = e.getTarget('tr', 10, true);
        tr.removeClass('x-date-picker-wk-wkrowover')
    },
    
    update: function(date, forceRefresh, weekNumber){
        var firstOfMonth = date.getFirstDateOfMonth();
        var startingPos = firstOfMonth.getDay()-this.startDay;
        if(startingPos <= this.startDay) {
            startingPos += 7;
        }
        
        // NOTE "+1" to ensure ISO week!
        var startDate = firstOfMonth.add(Date.DAY, -1*startingPos + 1);
        var wkCells = Ext.DomQuery.select('td[class=x-date-picker-wk]', this.getEl().dom);
        for (var i=0, id; i<wkCells.length; i++) {
            id = Ext.id() + ':' + startDate.add(Date.DAY, i*7).format('Y-m-d');
            wkCells[i].firstChild.firstChild.innerHTML = '<span id="' + id + '">' + startDate.add(Date.DAY, i*7).getWeekOfYear() + '</span>';
        }
        
        if (weekNumber) {
            this.clearSelection();
            
            if (! Ext.isArray(weekNumber)) {
                weekNumber = [weekNumber];
            }
            
            for (var i=0, row; i<weekNumber.length; i++) {
                row = this.getRowEl(weekNumber[i]);
                this.selectWeek(row);
            }
        }
        
    },
    
    handleDateClick: function(e) {
        target = e.getTarget('td[class=x-date-picker-wk]');
        if (target) {
            var row = target.parentNode;
            var weekNumber = target.firstChild.firstChild.firstChild.innerHTML;
            
            if (Ext.DomQuery.select('td[class=x-date-prevday]', row).length > 3 ) {
                this.showPrevMonth()
            } else if (Ext.DomQuery.select('td[class=x-date-nextday]', row).length > 4) {
                this.showNextMonth()
            } 
            
            // get row again
            row = this.getRowEl(weekNumber);
            
            // set new date value
            var value = Date.parseDate(row.firstChild.firstChild.firstChild.firstChild.id.split(':')[1], 'Y-m-d');
            this.setValue(value);
            
            // set selection
            this.clearSelection();
            this.selectWeek(row);
            
            this.fireEvent("select", this, this.value, weekNumber);
        }
    },
    
    getRowEl: function(weekNumber) {
        var wktds = Ext.DomQuery.select('td[class=x-date-picker-wk]', this.getEl().dom);
        for (var i=0; i<wktds.length; i++) {
            if (wktds[i].firstChild.firstChild.firstChild.innerHTML == weekNumber) {
                var rowEl = wktds[i].parentNode;
                break;
            }
        }
        
        return rowEl;
    },
    
    clearSelection: function() {
        var innerCal = Ext.DomQuery.selectNode('table[class=x-date-inner]', this.getEl().dom);
        var dates = Ext.DomQuery.select('td', innerCal);
        for (var i=0; i<dates.length; i++) {
            Ext.fly(dates[i]).removeClass('x-date-selected');
        }
    },
    
    /**
     * inspects month picker onClick event method
     * return false to cancle original onClick handler
     */
    inspectMonthPickerClick: Ext.emptyFn,
    
    selectWeek: function(rowEl) {
        if (rowEl) {
            // set new selection
            for (var j=1; j<rowEl.childNodes.length; j++) {
                Ext.fly(rowEl.childNodes[j]).addClass('x-date-selected');
            }
        }
    }
};