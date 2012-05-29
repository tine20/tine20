
Ext.ns('Tine.HumanResources');

Tine.HumanResources.DatePicker = Ext.extend(Ext.DatePicker, {
    initComponent: function() {
        this.plugins = [new Ext.ux.DatePickerWeekPlugin({
                weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK')
            })];
        
        this.initStore();
            
        Tine.HumanResources.DatePicker.superclass.initComponent.call(this);
    },
    
    initStore: function() {
        this.store = new Tine.Tinebase.
    }

//    update : function(date, forceRefresh){
//        if(this.rendered){
//            var vd = this.activeDate, vis = this.isVisible();
//            this.activeDate = date;
//            if(!forceRefresh && vd && this.el){
//                var t = date.getTime();
//                if(vd.getMonth() == date.getMonth() && vd.getFullYear() == date.getFullYear()){
//                    this.cells.removeClass('x-date-selected');
//                    this.cells.each(function(c){
//                       if(c.dom.firstChild.dateValue == t){
//                           c.addClass('x-date-selected');
//                           if(vis && !this.cancelFocus){
//                               Ext.fly(c.dom.firstChild).focus(50);
//                           }
//                           return false;
//                       }
//                    }, this);
//                    return;
//                }
//            }
//            var days = date.getDaysInMonth(),
//                firstOfMonth = date.getFirstDateOfMonth(),
//                startingPos = firstOfMonth.getDay()-this.startDay;
//    
//            if(startingPos < 0){
//                startingPos += 7;
//            }
//            days += startingPos;
//    
//            var pm = date.add('mo', -1),
//                prevStart = pm.getDaysInMonth()-startingPos,
//                cells = this.cells.elements,
//                textEls = this.textNodes,
//                // convert everything to numbers so it's fast
//                day = 86400000,
//                d = (new Date(pm.getFullYear(), pm.getMonth(), prevStart)).clearTime(),
//                today = new Date().clearTime().getTime(),
//                sel = date.clearTime(true).getTime(),
//                min = this.minDate ? this.minDate.clearTime(true) : Number.NEGATIVE_INFINITY,
//                max = this.maxDate ? this.maxDate.clearTime(true) : Number.POSITIVE_INFINITY,
//                ddMatch = this.disabledDatesRE,
//                ddText = this.disabledDatesText,
//                ddays = this.disabledDays ? this.disabledDays.join('') : false,
//                ddaysText = this.disabledDaysText,
//                format = this.format;
//    
//            if(this.showToday){
//                var td = new Date().clearTime(),
//                    disable = (td < min || td > max ||
//                    (ddMatch && format && ddMatch.test(td.dateFormat(format))) ||
//                    (ddays && ddays.indexOf(td.getDay()) != -1));
//    
//                if(!this.disabled){
//                    this.todayBtn.setDisabled(disable);
//                    this.todayKeyListener[disable ? 'disable' : 'enable']();
//                }
//            }
//    
//            var setCellClass = function(cal, cell){
//                cell.title = '';
//                var t = d.getTime();
//                cell.firstChild.dateValue = t;
//                if(t == today){
//                    cell.className += ' x-date-today';
//                    cell.title = cal.todayText;
//                }
//                if(t == sel){
//                    cell.className += ' x-date-selected';
//                    if(vis){
//                        Ext.fly(cell.firstChild).focus(50);
//                    }
//                }
//                // disabling
//                if(t < min) {
//                    cell.className = ' x-date-disabled';
//                    cell.title = cal.minText;
//                    return;
//                }
//                if(t > max) {
//                    cell.className = ' x-date-disabled';
//                    cell.title = cal.maxText;
//                    return;
//                }
//                if(ddays){
//                    if(ddays.indexOf(d.getDay()) != -1){
//                        cell.title = ddaysText;
//                        cell.className = ' x-date-disabled';
//                    }
//                }
//                if(ddMatch && format){
//                    var fvalue = d.dateFormat(format);
//                    if(ddMatch.test(fvalue)){
//                        cell.title = ddText.replace('%0', fvalue);
//                        cell.className = ' x-date-disabled';
//                    }
//                }
//            };
//    
//            var i = 0;
//            for(; i < startingPos; i++) {
//                textEls[i].innerHTML = (++prevStart);
//                d.setDate(d.getDate()+1);
//                cells[i].className = 'x-date-prevday';
//                setCellClass(this, cells[i]);
//            }
//            for(; i < days; i++){
//                var intDay = i - startingPos + 1;
//                textEls[i].innerHTML = (intDay);
//                d.setDate(d.getDate()+1);
//                cells[i].className = 'x-date-active';
//                setCellClass(this, cells[i]);
//            }
//            var extraDays = 0;
//            for(; i < 42; i++) {
//                 textEls[i].innerHTML = (++extraDays);
//                 d.setDate(d.getDate()+1);
//                 cells[i].className = 'x-date-nextday';
//                 setCellClass(this, cells[i]);
//            }
//    
//            this.mbtn.setText(this.monthNames[date.getMonth()] + ' ' + date.getFullYear());
//    
//            if(!this.internalRender){
//                var main = this.el.dom.firstChild,
//                    w = main.offsetWidth;
//                this.el.setWidth(w + this.el.getBorderWidth('lr'));
//                Ext.fly(main).setWidth(w);
//                this.internalRender = true;
//                // opera does not respect the auto grow header center column
//                // then, after it gets a width opera refuses to recalculate
//                // without a second pass
//                if(Ext.isOpera && !this.secondPass){
//                    main.rows[0].cells[1].style.width = (w - (main.rows[0].cells[0].offsetWidth+main.rows[0].cells[2].offsetWidth)) + 'px';
//                    this.secondPass = true;
//                    this.update.defer(10, this, [date]);
//                }
//            }
//        }
//    }
    
    update: function(date, forceRefresh) {
        
        Tine.HumanResources.DatePicker.superclass.update.call(this, date, forceRefresh);
    }
});
