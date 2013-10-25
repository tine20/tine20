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
    
    /**
     * the employee to use for this freetime
     * 
     * @type {Tine.HumanResources.Model.Employee}
     */
    employee: null,
    
    /**
     * holds the freetime type (SICKNESS or VACATION)
     * 
     * @type {String}
     */
    freetimeType: null,
    
    /**
     * the editdialog this is nested in
     * 
     * @type {Tine.HumanResources.FreeTimeEditDialog}
     */
    editDialog: null,
    
    /**
     * if vacation is handled, the account picker of the edit dialog is active
     * 
     * @type {Boolean}
     */
    accountPickerActive: null,
    
    dateProperty: 'date',
    recordsProperty: 'freedays',
    foreignIdProperty: 'freetime_id',
    useWeekPickerPlugin: false,
    
    /**
     * holds the previous year selected (to switch back on no account found exception
     * 
     * @type {Number}
     */
    previousYear: null,
    
    /**
     * holds the current year selected
     * 
     * @type {Number}
     */
    currentYear: null,
    
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
     * loads the feast days of the configured feast calendar from the server
     * 
     * @param {Boolean} fromYearChange
     * @param {Boolean} onInit
     */
    loadFeastDays: function(fromYearChange, onInit) {
        
        this.disableYearChange = fromYearChange;
        
        var employeeId = this.editDialog.fixedFields.get('employee_id').id;
        var year       = this.currentYear;
        var freeTimeId = this.editDialog.record.get('id') ? this.editDialog.record.get('id') : null;
        
        this.loadMask = new Ext.LoadMask(this.getEl(), {
            msg: this.app.i18n._('Loading calendar data...')
        });
        
        this.loadMask.show();
        
        var that = this;
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            params : { 
                method:      'HumanResources.getFeastAndFreeDays', 
                _employeeId: employeeId, 
                _year:       year, 
                _freeTimeId: freeTimeId
            },
            success : function(_result, _request) {
                that.onFeastDaysLoad(Ext.decode(_result.responseText), onInit);
            },
            failure : function(exception) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception, that.onFeastDaysLoadFailureCallback, that);
            },
            scope: that
        });
    },
    
    /**
     * loads the feast days from loadFeastDays
     * @param {Object} result
     * @param {Boolean} onInit
     */
    onFeastDaysLoad: function(result, onInit) {
        // wait until the accountpicker has found the current account
        if (this.accountPickerActive) {
            if (! (this.editDialog && this.editDialog.currentAccount)) {
                this.onFeastDaysLoad.defer(100, this, [result]);
                return;
            }
        }
        Tine.log.debug('Loaded feast and freedays:');
        Tine.log.debug(result);
        
        this.disabledDates = [];
        var exdates = result.results.excludeDates || [];
        var freetime = this.editDialog.record;
        
        // find out local free days to substract. this is by account only
        if (this.accountPickerActive) {
            var accountId = this.editDialog.currentAccount.get('id');
            var localFreeDaysToSubstract = (this.editDialog.localFreedays[accountId] || []).length ;
        } else {
            var localFreeDaysToSubstract = 0;
        }
        // add local freedays to exclude days
        Ext.each([this.editDialog.localFreedays, this.editDialog.localSicknessdays], function(a) {
            Ext.iterate(a, function(p, b) {
                Ext.each(b, function(d) {
                    exdates = exdates.concat(d);
                });
            });
        });

        // format dates to fit the datepicker format
        Ext.each(exdates, function(d) {
            Ext.each(d, function(date) {
                var split = date.date.split(' '), dateSplit = split[0].split('-');
                var date = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
                this.disabledDates.push(date);
            }, this);
        }, this);

        
        this.setDisabledDates(this.disabledDates);
        this.updateCellClasses();
        
        var split = result.results.firstDay.date.split(' '), dateSplit = split[0].split('-');
        var firstDay = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
        this.setMinDate(firstDay);
        
        var split = result.results.lastDay.date.split(' '), dateSplit = split[0].split('-');
        var lastDay = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
        this.setMaxDate(lastDay);
        
        // if ownFreeDays is empty, the record hasn't been saved already, so use the properties from the local record
        var iterate = (result.results.ownFreeDays && result.results.ownFreeDays.length > 0) ? result.results.ownFreeDays : (freetime ? freetime.get('freedays') : null);
        
        if (Ext.isArray(iterate)) {
            Ext.each(iterate, function(fd) {
                var split = fd.date.split(' '), dateSplit = split[0].split('-');
                fd.date = new Date(dateSplit[0], dateSplit[1] - 1, dateSplit[2]);
                fd.date.clearTime();
                this.store.add(new this.recordClass(fd));
            }, this);
        }
        
        if (this.accountPickerActive) {
            if (freetime && onInit) {
                localFreeDaysToSubstract -= freetime.get('days_count');
            }
            this.editDialog.getForm().findField('remaining_vacation_days').setValue(result.results.remainingVacation - localFreeDaysToSubstract);
        }
        
        this.updateCellClasses();
        this.loadMask.hide();
        
        if (this.disableYearChange == true) {
            var focusDate = firstDay;
        } else {
            var focusDate = freetime.get('firstday_date');
        }
        
        // focus
        if (focusDate) {
            this.update(focusDate);
        }
        
        this.enable();
        
        this.disableYearChange = false;
    },
    
    /**
     * if loading feast and freedays failes
     */
    onFeastDaysLoadFailureCallback: function() {
        var year = this.currentYear;
        this.currentYear = this.previousYear;
        this.previousYear = year;
        this.onYearChange();
    },
    
    /**
     * is called on year change
     */
    onYearChange: function() {
        this.loadFeastDays(true);
    },
    
    /**
     * overwrites update function of superclass
     * 
     * @param {} date
     * @param {} forceRefresh
     */
    update : function(date, forceRefresh) {
        
        Tine.HumanResources.DatePicker.superclass.update.call(this, date, forceRefresh);
        
        if (! this.disableYearChange) {
            var year = date.format('Y');
            if (year !== this.currentYear) {
                if (this.getData().length > 0) {
                    Ext.MessageBox.show({
                        title: this.app.i18n._('Year can not be changed'), 
                        msg: this.app.i18n._('You have already selected some dates from another year. Please create a new record to add dates from another year!'),
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING,
                        // jump to the first day of the selected
                        fn: function() {
                            var firstDay = this.store.getFirstDay();
                            this.update(firstDay.get('date'));
                        },
                        scope: this
                    });
                } else {
                    this.previousYear = this.currentYear;
                    this.currentYear = date.format('Y');
                    this.onYearChange();
                }
            }
        }
        
        this.updateCellClasses();
    },
    
    /**
     * removes or adds a date on date click
     * 
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
        
        if (this.accountPickerActive) {
            var remaining = this.editDialog.getForm().findField('remaining_vacation_days').getValue();
            
            if (remaining == 0) {
                Ext.MessageBox.show({
                    title: this.app.i18n._('No more vacation days'), 
                    msg: this.app.i18n._('The Employee has no more possible vacation days left for this year. Create a new vacation and use another personal account the vacation should be taken from.'),
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
        
        if (this.accountPickerActive) {
            if (this.store.getCount() > 0) {
                this.editDialog.accountPicker.disable();
            } else {
                this.editDialog.accountPicker.enable();
            }
            
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
     * 
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
