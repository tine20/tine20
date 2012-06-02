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
    dateProperty: 'date',
    recordsProperty: 'freedays',
    foreignIdProperty: 'freetime_id',
    useWeekPickerPlugin: false,
    initDate: null,

    /**
     * initializes the component
     */
    initComponent: function() {
        this.disabledDays = [0,6];
        
        if(this.useWeekPickerPlugin) {
            this.plugins = this.plugins ? this.plugins : [];
            this.plugins.push(new Ext.ux.DatePickerWeekPlugin({
                weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK')
            }));
        }

        this.initStore();
        this.on('show', this.onAfterRender, this);
        Tine.HumanResources.DatePicker.superclass.initComponent.call(this);
    },

    /**
     * loads the feast days of the configured feast calendar from the server
     * @param {} data
     */
    loadFeastDays: function(employeeId, contractId) {
        if(employeeId || contractId) {
            var fdd = this.record.get('firstday_date') ? this.record.get('firstday_date') : new Date();
            var excludeFreeTimeId = this.record.get('id') ? this.record.get('id') : null;
            this.loadMask = new Ext.LoadMask(this.getEl(), {
                msg: this.app.i18n._('Loading calendar data...')
            });
            this.loadMask.show();
            var req = Ext.Ajax.request({
                url : 'index.php',
                params : { method : 'HumanResources.getFeastAndFreeDays', _employeeId : employeeId, _firstDayDate: fdd, _excludeFreeTimeId: excludeFreeTimeId, _contractId: contractId},
                success : function(_result, _request) {
                    this.onFeastDaysLoad(Ext.decode(_result.responseText));
                },
                failure : this.onFailure,
                scope: this
            });
        }
    },
    
    onFailure: function(exception) {
        if (! exception.code && exception.responseText) {
            // we need to decode the exception first
            var response = Ext.util.JSON.decode(exception.responseText);
            exception = response.data;
        }
        
        if(exception.code == 910) {
            if(exception.nearestRecord) {
                
                this.editDialog.contractPicker.setValue(exception.nearestRecord);
                this.editDialog.contractPicker.selectedRecord = new Tine.HumanResources.Model.Contract(exception.nearestRecord); 
                this.editDialog.contractPicker.fireEvent('select');
            } else {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
        }
    },
    /**
     * loads the feast days from loadFeastDays
     * @param {Object} result
     */
    onFeastDaysLoad: function(result) {
        if(result.totalcount > 0) {
            this.disabledDates = [];
            Ext.each(result.results, function(date){
                var date = new Date(date.date);
                this.disabledDates.push(date);
            }, this);
            
            this.editDialog.contractPicker.setValue(result.contract);
            this.editDialog.contractPicker.selectedRecord = new Tine.HumanResources.Model.Contract(result.contract);
            this.editDialog.contractPicker.enable();
            
            this.setMinDate(new Date(result.contract.start_date));
            if(result.contract.end_date) this.setMaxDate(new Date(result.contract.end_date));
            this.setDisabledDates(this.disabledDates);
        }
        this.loadMask.hide();
        this.enable();
    },
    
    initStore: function() {
        var picker = this;
        this.store = new Tine.Tinebase.data.RecordStore({
            remoteSort: false,
            recordClass: this.recordClass,
            autoSave: false,
            getByDate: function(date) {
                if(!Ext.isDate(date)) {
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

    update : function(date, forceRefresh) {
        Tine.HumanResources.DatePicker.superclass.update.call(this, date, forceRefresh);
        this.updateCellClasses();
        },
    
    handleDateClick: function(e, t) {
        var date = new Date(t.dateValue),
            existing;
            
        date.clearTime();
        if (existing = this.store.getByDate(date)) {
            this.store.remove(existing);
        } else {
            this.store.add(new this.recordClass({date: date, duration: 1}));
        }
        Tine.HumanResources.DatePicker.superclass.handleDateClick.call(this, e, t);
    },
    
    updateCellClasses: function() {
        this.cells.each(function(c){
           if(this.store.getByDate(c.dom.firstChild.dateValue)) {
               c.addClass('x-date-selected');
           } else {
               c.removeClass('x-date-selected');
           }
        }, this);
    },
    
    getData: function() {
        var ret = [];
        this.store.sort({field: 'date', direction: 'ASC'});
        var i=0;
        this.store.query().each(function(record) {
            i++;
            if(i == 1) {
                record.set('duration', this.editDialog.firstDayLengthPicker.getValue());
            } else if (this.store.getCount() == i) {
                record.set('duration', this.editDialog.lastDayLengthPicker.getValue());
            } else {
                record.set('duration', 1);
            }
            record.set('freetime_id', this.record.get(this.recordClass.getMeta('idProperty')));
            ret.push(record.data);
        }, this);
        return ret;
    },
    
    onRecordLoad: function(record) {
        if(record.get('employee_id')) {
            this.loadFeastDays(record.get('employee_id').id);
        }
        Ext.each(record.get(this.recordsProperty), function(fd) {
            fd.date = new Date(fd.date);
            fd.date.clearTime();
            this.store.add(new this.recordClass(fd));
        }, this);

        // focus
        this.initDate = this.initDate ? this.initDate : new Date();
        this.setValue(this.initDate);
        
        // clear invalid
        this.store.each(function(record) {
            if(!record.get('freetime_id')) {
                this.store.remove(record);
            }
        }, this);
        
        this.updateCellClasses();
    }
});
