/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

Tine.HumanResources.FreeTimePlanningPanel = Ext.extend(Tine.widgets.grid.GridPanel, {


    /**
     * @cfg {String} dateFormatString
     */
    dateFormatString: null,

    /**
     * @property {Ext.ux.form.PeriodPicker} periodPicker
     */
    periodPicker: null,


    /**
     * @property {Array} resolvedFreeTimePeriods
     */
    resolvedFreeTimePeriods: null,

    // private
    // border: false,
    // layout: 'border',
    recordClass: 'Tine.HumanResources.Model.Employee',
    autoRefreshInterval: null,
    listenMessageBus: false,
    displaySelectionHelper: false,

    initComponent: function() {
        let me = this;

        me.dateFormatString = me.dateFormatString || Locale.getTranslationData('Date', 'short');

        me.periodPicker = new Ext.ux.form.PeriodPicker({
            // availableRanges: 'week,month,quarter,year',
            availableRanges: 'month',
            listeners: {
                'change': _.bind(me.onPeriodChange, me)
            }
        });

        me.recordProxy = Tine.HumanResources.employeeBackend;
        me.defaultSortInfo = {
            field: 'account_id'
        };

        me.i18nRecordName = 'Free Time';
        me.initFreeTimeTypes(); // async!
        
        this.defaultFilters = [{
            field: 'employment_end', operator: 'after', value: new Date().clearTime().getLastDateOfMonth().add(Date.DAY, 1)
        }];
        Tine.HumanResources.FreeTimePlanningPanel.superclass.initComponent.call(me);

        me.grid.on('cellmousedown', _.bind(me.onCellMouseDown, me));
        me.grid.on('cellclick', _.bind(me.onCellClick, me));
        me.grid.on('celldblclick', _.bind(me.action_editInNewWindow.execute, me.action_editInNewWindow));
    },

    initGrid: function() {
        let me = this;
        let columns = me.getColumns();

        // me.gridConfig.autoExpandColumn = 'account_id';
        me.gridConfig.forceFit = false;
        me.gridConfig.cm = new Ext.grid.ColumnModel({
            defaults: {
                width: 30,
                fixed: true,
                resizable: false,
                sortable: false,
                menuDisabled: true
            },
            columns: columns
        });

        me.selectionModel = new Ext.ux.grid.MultiCellSelectionModel({
            // getCount: function() {return 0},
            // getSelections: function() { return []},
            selectRow: function() {},
            listeners: {
                'beforecellselect': _.bind(me.onBeforeCellSelect, me),
                'selectionchange': _.bind(me.onSelectionChange, me)
            }
        });

        Tine.HumanResources.FreeTimePlanningPanel.superclass.initGrid.call(me);
    },

    getColumns: function() {
        let me = this;

        let colManager = _.bind(Tine.widgets.grid.ColumnManager.get, Tine.widgets.grid.ColumnManager,
            'HumanResources',
            'Employee',
            _,
            'mainScreen',
            {fixed: false, resizable: true, sortable: true, menuDisabled: false}
        );

        let columns = [
            colManager('number'),
            _.assign(colManager('account_id'), {width: 100}),
            _.assign(colManager('division_id'), {width: 100})
        ];

        let period = me.periodPicker.getValue();
        let day = period.from;
        do {
            columns.push({
                id: day.format('Y-m-d'),
                day: day,
                header: day.format('D').substring(0,2) + '<br>' + day.format('d'),
                renderer: _.bind(me.renderFreeDay, me, _, _, _, _, _, _, day),
                dataIndex: 'virtual'
            });
            day = day.add(Date.DAY, 1);

        } while (day < period.until);

        return columns;
    },

    initFreeTimeTypes: async function() {
        this.freeTimeTypes = [];
        this.freeTimeTypes = _.get(await Tine.HumanResources.searchFreeTimeTypes({}), 'results', []);
    },
    
    renderFreeDay: function(value, metaData, record, rowIndex, colIndex, store, day) {
        let me = this;
        let bgColor = '#FFFFFF';
        let char = '';

        if (me.isExcludeDay(record, day)) {
            bgColor = 'lightgrey';
            char = 'X';
        }
        else {
            let freeTimes = me.getFreeTimes(record, day);
            if (freeTimes.length) {
                // support multiple freetimes per day?
                let freeTimeType = freeTimes[0].type;
                char = _.get(freeTimeType, 'abbreviation', freeTimeType[0]);
                // @TODO color
            }
        }

        return '<div class="hr-freetimeplanning-daycell" style="background-color: ' + bgColor + ';">' + char + '</div>'

    },

    afterRender: function() {
        let me = this;

        me.pagingToolbar.insert(me.pagingToolbar.items.length -4, {xtype: 'tbseparator'});
        me.pagingToolbar.insert(me.pagingToolbar.items.length -4, me.periodPicker);

        Tine.HumanResources.FreeTimePlanningPanel.superclass.afterRender.call(me);
    },

    onPeriodChange: function(pp, period) {
        let me = this;
        let year = me.periodPicker.getValue().from.format('Y');
        if (_.indexOf(me.resolvedFreeTimePeriods, year) < 0) {
            me.resolveRecords(year)
        }
        me.gridConfig.cm.setConfig(me.getColumns());
    },
    
    // prevent default
    onRowClick: Ext.emptyFn,

    /**
     * NOTE: onCellClick is executed after selection model has already selected the cell
     *       so we need to get the initial state from the mousedown event
     */
    onCellMouseDown: function(grid, rowIndex, columnIndex, e) {
        let me = this;
        e.isSelected = me.selectionModel.isSelected([rowIndex, columnIndex]);
    },

    onCellClick: function(grid, rowIndex, columnIndex, e) {
        let me = this;
        let day = me.getColumns()[columnIndex].day;

        if (day) {
            let employee = me.getStore().getAt(rowIndex);
            let freeTimes = me.getFreeTimes(employee, day);

            if (freeTimes.length) {
                let freeDates = _.map(_.concat.apply([], _.map(freeTimes, 'freedays')), 'date');

                _.each(me.getColumns(), (col, idx) => {
                    if (col.day && _.indexOf(freeDates, col.day.format('Y-m-d 00:00:00')) >= 0) {
                        if (e.isSelected && e.ctrlKey) {
                            me.selectionModel.deselectCell([rowIndex, idx])
                        } else {
                            me.selectionModel.selectCell([rowIndex, idx], true);
                        }
                    }
                });
            }
        }
    },
    
    // prevent default
    onRowDblClick: Ext.emptyFn,
    
    onBeforeCellSelect: function(sm, cellInfo, keepExisting) {
        let me = this;
        let col = me.getColumns()[cellInfo[1]];
        let employee = me.getStore().getAt(cellInfo[0]);

        let isDay = _.get(col, 'dataIndex') === 'virtual';
        let isSameRow = _.get(me.selectionModel.getSelections(), 0, cellInfo)[0] === cellInfo[0];
        let isExludeDay = me.isExcludeDay(employee, col.day);

        return isDay && !isExludeDay && (keepExisting ? isSameRow : true);
    },

    onSelectionChange: function(sm, selections) {
        let me = this;
        let selectedFreeTimes = me.getSelectedFreeTimes();

        me.action_deleteRecord.setDisabled(selectedFreeTimes.length < 1);
        me.action_editInNewWindow.setDisabled(selectedFreeTimes.length !== 1);
    },

    getSelectedEmployee: function() {
        let me = this;
        return me.getStore().getAt(_.get(_.map(me.selectionModel.getSelections(), 0), 0));

    },

    getSelectedDays: function() {
        let me = this;
        return _.map(_.map(_.map(me.selectionModel.getSelections(), 1), _.bind(_.get, me, me.grid.colModel.columns)), 'day');
    },

    getSelectedFreeTimes: function() {
        let me = this;
        let employee = me.getSelectedEmployee();
        let selectedDays = me.getSelectedDays();

        return me.getFreeTimes(employee, selectedDays);
    },

    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        let me = this;

        me.resolvedFreeTimePeriods = [];

        me.showLoadMask();

        // filter = [{field: 'shadow_path', operator: 'contains', value: '{SITE}'}]

        Tine.HumanResources.FreeTimePlanningPanel.superclass.onStoreBeforeload.apply(me, arguments);
    },

    /**
     * called after a new set of Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        let me = this;

        Tine.HumanResources.FreeTimePlanningPanel.superclass.onStoreLoad.apply(me, arguments);

        me.resolveRecords(me.periodPicker.getValue().from.format('Y'));
    },

    resolveRecords: function(year, employeeIds) {
        let me = this;
        let promises = [];

        return me.showLoadMask()
            .then(() => {
                me.pagingToolbar.refresh.disable();
            })
            .then(() => {
                employeeIds = _.isArray(employeeIds) ? employeeIds : _.map(me.store.data.items, 'data.id');
                const reflect = p => p.then(
                    v => ({v, status: "resolved" }),
                    e => ({e, status: "rejected" })
                );

                // NOTE: we reflect the calls as some of them might fail (e.g. employee has no contract)
                _.each(employeeIds, (employeeId) => {
                    promises.push(reflect(Tine.HumanResources.getFeastAndFreeDays(employeeId, year, null, null)));
                });

                return  Promise.all(promises);
            })
            .then((feastAndFreeDays) => {
                feastAndFreeDays = _.map(_.filter(feastAndFreeDays, {status :'resolved'}), 'v.results');

                _.each(feastAndFreeDays,  (feastAndFreeDaysFor) => {
                    // sort freedays into freetime
                    Tine.HumanResources.Model.FreeTime.prepareFeastAndFreeDays(feastAndFreeDaysFor);

                    // reference feastAndFreeDays in corresponding employee record
                    let employee = _.find(me.store.data.items, {id: _.get(feastAndFreeDaysFor, 'employee.id')});
                    _.set(employee, 'feastAndFreeDays.' + year, feastAndFreeDaysFor);
                });
                me.resolvedFreeTimePeriods.push(year);
            })
            .then(function() {
                me.grid.getView().refresh();
            })
            .finally(() => {
                me.hideLoadMask();
                me.pagingToolbar.refresh.enable();
            });
    },

    showLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Loading free time planning data...")});
        }
        this.loadMask.show.defer(100, this.loadMask);
        return Promise.resolve();
    },

    hideLoadMask: function() {
        this.loadMask.hide.defer(100, this.loadMask);
        return Promise.resolve();
    },

    initActions: function() {
        let me = this;

        me.action_addInNewWindow = new Ext.Action({
            text: me.app.i18n._('Add Free Time'),
            handler: _.bind(me.onEditInNewWindow, me, 'add'),
            iconCls: 'action_add',
        });

        me.action_editInNewWindow = new Ext.Action({
            text: me.app.i18n._('Edit Free Time'),
            disabled: true,
            handler: _.bind(me.onEditInNewWindow, me, 'edit'),
            iconCls: 'action_edit',
        });

        me.action_deleteRecord = new Ext.Action({
            text: me.app.i18n._('Delete Free Time'),
            handler: _.bind(me.onDeleteRecords, me),
            disabled: true,
            iconCls: 'action_delete',
        });
    },

    onEditInNewWindow: function(actionType) {
        let me = this;
        let selectedEmployee = me.getSelectedEmployee();
        let selectedFreeTime = _.get(me.getSelectedFreeTimes(), 0);

        let record = null;
        let fixedFields = null;

        if (actionType === 'edit' && selectedFreeTime) {
            record = selectedFreeTime;
            fixedFields = {
                'employee_id': selectedEmployee.data,
                'type': selectedFreeTime.type
            };
        } else {
            const freedays = selectedFreeTime ? [] : _.map(me.getSelectedDays(), (day) => {
                return {date: day.format('Y-m-d 00:00:00')}
            });
            
            record = Tine.HumanResources.Model.FreeTime.getDefaultData({
                'employee_id': _.get(selectedEmployee, 'data'),
                'type': this.freeTimeType,
                'freedays': freedays,
                'days_count': freedays.length,
                'firstday_date': freedays[0],
                'lastday_date': freedays[freedays.length-1]
            });
        }

        if (record) {
            Tine.HumanResources.FreeTimeEditDialog.openWindow({
                record: record,
                fixedFields: JSON.stringify(fixedFields),
                listeners: {
                    scope: this,
                    update: this.onUpdateRecord
                }
            });
        }


    },

    onDeleteRecords: function() {
        const sm = this.grid.getSelectionModel();
        const records = _.map(this.getSelectedFreeTimes(), _.curry(Tine.Tinebase.data.Record.setFromJson)(_, Tine.HumanResources.Model.FreeTime));

        if (this.disableDeleteConfirmation || (Tine[this.app.appName].registry.get('preferences')
            && Tine[this.app.appName].registry.get('preferences').get('confirmDelete') !== null
            && Tine[this.app.appName].registry.get('preferences').get('confirmDelete') == 0)
        ) {
            // don't show confirmation question for record deletion
            this.deleteRecords(sm, records);
        } else {
            var recordNames = records[0].getTitle();
            if (records.length > 1) {
                recordNames += ', ...';
            }

            var i18nQuestion = this.i18nDeleteQuestion ?
                this.app.i18n.n_hidden(this.i18nDeleteQuestion[0], this.i18nDeleteQuestion[1], records.length) :
                String.format(i18n.ngettext('Do you really want to delete the selected record?',
                    'Do you really want to delete the selected records?', records.length), recordNames);
            Ext.MessageBox.confirm(i18n._('Confirm'), i18nQuestion, function(btn) {
                if (btn == 'yes') {
                    this.deleteRecords(sm, records);
                }
            }, this);
        }
        
    },

    deleteRecords: async function(sm, rs) {
        this.pagingToolbar.refresh.disable();
        await Tine.HumanResources.deleteFreeTimes(_.map(rs, 'data.id'));
        
        const employeeIds = _.uniq(_.reduce(rs, (es, r) => {
            return es.concat(this.store.getById(r.data.employee_id));
        }, []));
        
        await this.resolveRecords(this.periodPicker.getValue().from.format('Y'), employeeIds)

        this.grid.view.refresh();
    },
    
    /**
     * on update after edit
     *
     * @param {String|Tine.Tinebase.data.Record} record
     * @param {String} mode
     */
    onUpdateRecord: function(record, mode) {
        const freeTime = Tine.Tinebase.data.Record.setFromJson(record, Tine.HumanResources.Model.FreeTime);
        const employee = Tine.Tinebase.data.Record.setFromJson(freeTime.get('employee_id'), Tine.HumanResources.Model.Employee);
        
        this.resolveRecords(this.periodPicker.getValue().from.format('Y'), [employee.getId()])
        
        .then(() => {
            this.grid.view.refresh();
        });
    },
    
    /**
     *
     * @param {custom} employee
     * @param {Date} day
     */
    isExcludeDay(employee, day) {
        const feastAndFreeDaysCache  = _.get(employee, 'feastAndFreeDays', {});

        return Tine.HumanResources.Model.FreeTime.isExcludeDay(feastAndFreeDaysCache, day);
    },

    /**
     *
     * @param {custom} employee
     * @param {Date|Date[]}day
     */
    getFreeTimes(employee, day) {
        const feastAndFreeDaysCache  = _.get(employee, 'feastAndFreeDays', {});
        
        return Tine.HumanResources.Model.FreeTime.getFreeTimes(feastAndFreeDaysCache, day);
    },

    onKeyDown: function(e) {
        const freeTimeType = _.find(this.freeTimeTypes, {'abbreviation': String.fromCharCode(e.getKey())});
        
        if (freeTimeType) {
            this.freeTimeType = freeTimeType;
            window.setTimeout(() => {
                this.freeTimeType = null
            }, 1000);
            this.action_editInNewWindow.execute();
        } else {
            Tine.HumanResources.FreeTimePlanningPanel.superclass.onKeyDown.call(this, e);
        }
    }
});

Ext.reg('humanresources.freetimeplanning', Tine.HumanResources.FreeTimePlanningPanel);

Tine.HumanResources.FreeTimePlanningWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    recordClass: 'Tine.HumanResources.Model.Employee',
    hasContainerTreePanel: false,
    hasFavoritesPanel: true
});
