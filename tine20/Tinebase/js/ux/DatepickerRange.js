/**
 * Ext.ux
 * 
 * @package     Ext.ux
 */

Ext.ns('Ext.ux');


Date.prototype.getFirstDateOfWeek = function(){
    var value=this.clearTime();
    var semana=this.getWeekOfYear();
    while(semana == value.getWeekOfYear()) {
        value=value.add(Date.DAY,-1);
    }
    value=value.add(Date.DAY,1);
    return value;
};

/**
 * @namespace   Ext.ux
 * @class       Ext.ux.DatePickerRange
 * @extends     Ext.DatePicker
 */
Ext.ux.DatePickerRange = Ext.extend(Ext.DatePicker, {
    selectionMode:'month',
    setSelectionMode:function(mode){
        this.selectionMode=mode;
        this.setValue(this.value);
    },
    getSelectionMode:function(){
        return this.selectionMode();
    },

    //private
    update : function(date){
        var vd = this.activeDate;
        this.activeDate = date;
        if(vd && this.el){
            var t = date.getTime();
            if(vd.getMonth() == date.getMonth() && vd.getFullYear() == date.getFullYear()){
                this.cells.removeClass("x-date-selected");
                this.cells.each(function(c){
                   if(this.isSelected(  c.dom.firstChild.dateValue  )){
                       c.addClass("x-date-selected");
                   }
                },this);
                return;
            }
        }
        var days = date.getDaysInMonth();
        var firstOfMonth = date.getFirstDateOfMonth();
        var startingPos = firstOfMonth.getDay()-this.startDay;

        if(startingPos <= this.startDay){
            startingPos += 7;
        }

        var pm = date.add("mo", -1);
        var prevStart = pm.getDaysInMonth()-startingPos;

        var cells = this.cells.elements;
        var textEls = this.textNodes;
        days += startingPos;

        
        var day = 86400000;
        var d = (new Date(pm.getFullYear(), pm.getMonth(), prevStart)).clearTime();
        var today = new Date().clearTime().getTime();
        var sel = date.clearTime().getTime();
        var min = this.minDate ? this.minDate.clearTime() : Number.NEGATIVE_INFINITY;
        var max = this.maxDate ? this.maxDate.clearTime() : Number.POSITIVE_INFINITY;
        var ddMatch = this.disabledDatesRE;
        var ddText = this.disabledDatesText;
        var ddays = this.disabledDays ? this.disabledDays.join("") : false;
        var ddaysText = this.disabledDaysText;
        var format = this.format;



        var setCellClass = function(cal, cell){
            cell.title = "";
            var t = d.getTime();
            cell.firstChild.dateValue = t;
            if(t == today){
                cell.className += " x-date-today";
                cell.title = cal.todayText;
            }
            if(cal.isSelected(cell.firstChild.dateValue)){
                cell.className += " x-date-selected";
            }
            
            if(t < min) {
                cell.className = " x-date-disabled";
                cell.title = cal.minText;
                return;
            }
            if(t > max) {
                cell.className = " x-date-disabled";
                cell.title = cal.maxText;
                return;
            }
            if(ddays){
                if(ddays.indexOf(d.getDay()) != -1){
                    cell.title = ddaysText;
                    cell.className = " x-date-disabled";
                }
            }
            if(ddMatch && format){
                var fvalue = d.dateFormat(format);
                if(ddMatch.test(fvalue)){
                    cell.title = ddText.replace("%0", fvalue);
                    cell.className = " x-date-disabled";
                }
            }
        };

        var i = 0;
        for(; i < startingPos; i++) {
            textEls[i].innerHTML = (++prevStart);
            d.setDate(d.getDate()+1);
            cells[i].className = "x-date-prevday";
            setCellClass(this, cells[i]);
        }

        for(; i < days; i++){
            intDay = i - startingPos + 1;
            textEls[i].innerHTML = (intDay);
            d.setDate(d.getDate()+1);
            cells[i].className = "x-date-active";
            setCellClass(this, cells[i]);
        }
        var extraDays = 0;
        for(; i < 42; i++) {
             textEls[i].innerHTML = (++extraDays);
             d.setDate(d.getDate()+1);
             cells[i].className = "x-date-nextday";
             setCellClass(this, cells[i]);
        }

        this.mbtn.setText(this.monthNames[date.getMonth()] + " " + date.getFullYear());

        if(!this.internalRender){
            var main = this.el.dom.firstChild;
            var w = main.offsetWidth;
            this.el.setWidth(w + this.el.getBorderWidth("lr"));
            Ext.fly(main).setWidth(w);
            this.internalRender = true;
            
            if(Ext.isOpera && !this.secondPass){
                main.rows[0].cells[1].style.width = (w - (main.rows[0].cells[0].offsetWidth+main.rows[0].cells[2].offsetWidth)) + "px";
                this.secondPass = true;
                this.update.defer(10, this, [date]);
            }
        }
    },
    isSelected:function(date){
        date=new Date(date);
        switch(this.selectionMode) {
            case 'day':
               return date.clearTime().getTime() == this.value.clearTime().getTime();
               break;
            case 'month':
               return date.getFirstDateOfMonth().clearTime().getTime ()==this.value.getFirstDateOfMonth().clearTime().getTime ();
               break;
            case 'week':
               return date.getFirstDateOfWeek().clearTime().getTime ()==this.value.getFirstDateOfWeek().clearTime().getTime ();
               break;
            default:
               throw 'Illegal selection mode';
               break;
        }        
    }
        
    
});

Ext.reg('datepickerrange', Ext.ux.DatePickerRange);
