/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>DatePicker with multiple days</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.DatePicker
 */
Tine.HumanResources.DatePicker = Ext.extend(Ext.DatePicker, {
    
    recordClass: null,
    app: null,
    
    record: null,
    
    /**
     * the employee to use for this freetime
     * 
     * @type {Tine.HumanResources.Model.Employee}
     */
    employee: null,
    freetimeType: null,
    editDialog: null,
    dateProperty: 'date',
    recordsProperty: 'freedays',
    foreignIdProperty: 'freetime_id',
    useWeekPickerPlugin: false,

    /**
     * initializes the component
     */
    initComponent: function() {
        if (this.useWeekPickerPlugin) {
            this.plugins = this.plugins ? this.plugins : [];
            this.plugins.push(new Ext.ux.DatePickerWeekPlugin({
                weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK')
            }));
        }

        this.initStore();
        Tine.HumanResources.DatePicker.superclass.initComponent.call(this);
    },
    
    /**
     * initializes the store
     */
    initStore: function() {
        var picker = this;
        this.store = new Tine.Tinebase.data.RecordStore({
            remoteSort: false,
            recordClass: this.recordClass,
            autoSave: false,
            getByDate: function(date) {
                if (!Ext.isDate(date)) {
                    date = new Date(date);
                }
                var index = this.findBy(function(record) {
                    if(record.get(picker.dateProperty).toString() == date.toString()) {
                        return true;
                    }
                });
                return this.getAt(index);
            },
            getFirstDay: function() {
                this.sort({field: 'date', direction: 'ASC'});
                return this.getAt(0);
            },
            
            getLastDay: function() {
                this.sort({field: 'date', direction: 'ASC'});
                return this.getAt(this.getCount() - 1);
            }
        }, this);
    },
    
    /**
     * is called on editdialog record load
     * @param {Tine.HumanResources.Model.FreeTime} record
     */
    onRecordLoad: function(record, employeeId) {
        this.loadFeastDays(record, employeeId);
    },

    /**
     * loads the feast days of the configured feast calendar from the server
     * @param {} data
     */
    loadFeastDays: function(freetime, employeeId) {
        
        if (freetime) {
            var year = null;
            var freeTimeId   = freetime.get('id') ? freetime.get('id') : null;
        } else {
            var year = this.editDialog.accountBox.selectedRecord ? this.editDialog.accountBox.selectedRecord.data.year : null;
            var freeTimeId = null;
        }
        this.loadMask = new Ext.LoadMask(this.getEl(), {
            msg: this.app.i18n._('Loading calendar data...')
        });
        
        this.loadMask.show();
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            params : { method : 'HumanResources.getFeastAndFreeDays', _employeeId : employeeId, _year: year, _freeTimeId: freeTimeId},
            success : function(_result, _request) {
                this.onFeastDaysLoad(Ext.decode(_result.responseText), freetime);
            },
            failure : function(exception) {
                Tine.HumanResources.handleRequestException(exception, this.onFeastDaysLoadFailureCallback, this);
            },
            scope: this
        });
    },
    
    /**
     * loads the feast days from loadFeastDays
     * @param {Object} result
     */
    onFeastDaysLoad: function(result, freetime) {
        var lfd = this.editDialog.localFreedays || [] ;
        var lsd = this.editDialog.localSicknessdays || [];
        var exdates = result.results.excludeDates || [];
        this.disabledDates = [];
        
        Ext.each([lfd, lsd, exdates], function(d){
            Ext.each(d, function(date) {
                var split = date.date.split(' '), dateSplit = split[0].split('-');
                var date = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
                this.disabledDates.push(date);
            }, this);
        }, this);
        
        this.setDisabledDates(this.disabledDates);
        
        if (this.freetimeType == 'VACATION') {
            this.editDialog.getForm().findField('remaining_vacation_days').setValue(result.results.remainingVacation - lfd.length);
        }
        
        var split = result.results.firstDay.date.split(' '), dateSplit = split[0].split('-');
        var firstDay = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
        this.setMinDate(firstDay);
        
        var split = result.results.lastDay.date.split(' '), dateSplit = split[0].split('-');
        var lastDay = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
        this.setMaxDate(lastDay);
        
        var iterate = result.results.ownFreeDays ? result.results.ownFreeDays : (freetime ? freetime.get('freedays') : null);
        
        if (Ext.isArray(iterate)) {
            Ext.each(iterate, function(fd) {
                var split = fd.date.split(' '), dateSplit = split[0].split('-');
                fd.date = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
                fd.date.clearTime();
                this.store.add(new this.recordClass(fd));
            }, this);
        }
        
        this.updateCellClasses();
        
        this.loadMask.hide();
        this.enable();
        
        var focusDate = this.editDialog.record.get('firstday_date');
        if (! focusDate) {
            var year = this.editDialog.accountBox.selectedRecord ? parseInt(this.editDialog.accountBox.selectedRecord.data.year) : 0;
            if (year > 0) {
                var focusDate = new Date(this.activeDate.getTime());
                focusDate.setFullYear(year);
            } else {
                focusDate = new Date();
            }
        }
        
        // focus
        this.update(focusDate);
    },
    
    /**
     * if loading feast and freedays failes
     */
    onFeastDaysLoadFailureCallback: function() {
        this.loadMask.hide();
        this.editDialog.disable();
    },

    /**
     * overwrites update function of superclass
     * @param {} date
     * @param {} forceRefresh
     */
    update : function(date, forceRefresh) {
        Tine.HumanResources.DatePicker.superclass.update.call(this, date, forceRefresh);
        this.updateCellClasses();
        },
    
    /**
     * removes or adds a date on date click
     * @param {Object} e
     * @param {Object} t
     */
    handleDateClick: function(e, t) {
        
        if (!(!this.disabled && t.dateValue && !Ext.fly(t.parentNode).hasClass('x-date-disabled'))) {
            return;
        }
        
        var date = new Date(t.dateValue),
            existing;
            
        date.clearTime();
        
        if (this.freetimeType == 'VACATION') {
            var remaining = this.editDialog.getForm().findField('remaining_vacation_days').getValue();
            
            if (remaining == 0) {
                Ext.MessageBox.show({
                    title: this.app.i18n._('No more vacation days'), 
                    msg: this.app.i18n._('The Employee has no more possible vacation days left for this year. Add some extra free days to the account, if the employee should have more vacation this year.'),
                    icon: Ext.MessageBox.WARNING,
                    buttons: Ext.Msg.OK
                });
                return;
            }
        } else {
            var remaining = 0;
        }
        
        if (existing = this.store.getByDate(date)) {
            this.store.remove(existing);
            remaining++;
        } else {
            this.store.addSorted(new this.recordClass({date: date, duration: 1}));
            remaining--;
        }
        
        if (this.store.getCount() > 0) {
            if (! this.editDialog.accountBox.getValue()) {
                this.editDialog.accountBox.load();
            }
            this.editDialog.accountBox.disable();
        } else {
            this.editDialog.accountBox.enable();
        }
        
        if (this.freetimeType == 'VACATION') {
            this.editDialog.getForm().findField('remaining_vacation_days').setValue(remaining);
        }
        Tine.HumanResources.DatePicker.superclass.handleDateClick.call(this, e, t);
    },
    
    /**
     * updates the cell classes
     */
    updateCellClasses: function() {
        this.cells.each(function(c){
           if (this.store.getByDate(c.dom.firstChild.dateValue)) {
               c.addClass('x-date-selected');
           } else {
               c.removeClass('x-date-selected');
           }
        }, this);
    },
    
    /**
     * returns data for the editDialog
     * @return {Array}
     */
    getData: function() {
        var ret = [];
        this.store.sort({field: 'date', direction: 'ASC'});
        this.store.query().each(function(record) {
            ret.push(record.data);
        }, this);
        
        return ret;
    }
});
