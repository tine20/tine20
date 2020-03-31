/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * attendee filter gird widget
 * 
 * @TODO: remove attendee deleted on server?
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeFilterGrid
 * @extends     Tine.Calendar.AttendeeGridPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.AttendeeFilterGrid = Ext.extend(Tine.Calendar.AttendeeGridPanel, {
    showNamesOnly: true,
    showMemberOfType: true,
    stateId: 'calendar-attendee-filter-grid',
    cls: 'x-cal-attendee-filter-grid',
    addNewAttendeeText: 'Add attendee', // i18n._('Add attendee')
    requireFreeBusyGrantOnly: true,

    enableDragDrop: true,
    ddGroup: 'Tine.Calendar.AttendeeFilterGrid.Sort',
    
    initComponent: function() {
        this.record = new Tine.Calendar.Model.Event({
            editGrant: true
        });

        this.viewConfig = {
            scrollOffset: 0
        };
        
        this.selModel = new Ext.grid.RowSelectionModel({singleSelect: true});
        this.selModel.on('beforerowselect', this.onBeforeRowSelect, this);
        
        Tine.Calendar.AttendeeFilterGrid.superclass.initComponent.call(this);
        
        this.store.on('add', this.onStoreAdd, this);
        this.store.on('remove', this.onStoreRemove, this);
        this.store.on('update', this.onStoreUpdate, this);
        
        this.ddText = this.app.i18n._('Sort Attendee');
    },
    
    initColumns: function() {
        Tine.Calendar.AttendeeFilterGrid.superclass.initColumns.call(this);
        
        this.columns.unshift(new Ext.ux.grid.CheckColumn({
            id: 'checked',
            dataIndex: 'checked',
            width: 30,
            sortable: true,
            resizable: false,
            header: '&#160;',
            tooltip: this.app.i18n._('Check to filter for this attendee'),
            listeners: {
                scope: this,
                beforecheckchange: this.onBeforeCheckChange
            }
        }));
        
        this.plugins = Ext.isArray(this.plugins) ? this.plugins : [];
        this.plugins.push(this.columns[0]);
    },
    
    afterRender: function() {
        Tine.Calendar.AttendeeFilterGrid.superclass.afterRender.apply(this, arguments);
        this.dropZone = new Ext.ux.grid.GridDropZone(this, {
            ddGroup: this.ddGroup,
            isValidDropPoint: function(target, pt, dd, e, data) {
                var addIdx = this.grid.store.getCount() -1;
                return this.view.findRowIndex(target) != addIdx && data.rowIndex != addIdx;
            },
            onNodeDrop : function(target, dd, e, data){
                var pt = this.getDropPoint(e, target, dd),
                    targetRowIndex = this.view.findRowIndex(target),
                    targetPos = pt == 'above' ? targetRowIndex : targetRowIndex + 1;
                
                if (this.isValidDropPoint(target, pt, dd, e, data)) {
                    var store = this.grid.getStore();
                    
                    store.suspendEvents();
                    
                    Ext.each(data.selections, function(r) {
                        var currentIdx = store.indexOf(r);
                        if (currentIdx >= 0) {
                            targetPos = targetPos > currentIdx ? targetPos -1 : targetPos;
                            store.remove(r);
                        }
                        store.insert(targetPos, data.selections);
                    }, this);
                    
                    store.each(function(r,idx) {r.set('sort', idx)});
                    store.sort('sort', 'ASC');
                    store.resumeEvents();
                    
                    this.grid.getView().refresh();
                    this.grid.getView().updateSortIcon('sort', 'ASC');
                    this.grid.fireEvent('sortchange', this.grid, this.grid.getState());
                }
            }
        });
    },

    onBeforeCheckChange: function(col, newValue, oldValue, record) {
        var _ = window.lodash,
            chkCount = _.countBy(_.map(this.store.data.items, 'data.checked')).true,
            filterPanel = this.app.getMainScreen().getCenterPanel().filterToolbar.activeFilterPanel,
            calFilter = filterPanel.filterStore.getAt(filterPanel.filterStore.findExact('field', 'container_id'));

        if (! newValue && chkCount == 1 && !calFilter) {

            return false;
        }
    },

    onBeforeRowSelect: function(sm, idx, keep, attendee) {
        if (! attendee.get('user_id')) {
            return false;
        }
    },
    
    onStoreAdd: function() {
        // don't save initial 'add attendee' record
        if (this.store.getCount() > 1) {
            this.saveState();
        }
    },
    
    onStoreRemove: function() {
        this.saveState();
        this.onStoreChange();
    },
    
    onStoreUpdate: function(store, attendee) {
        // skip updates from new attendee row
        if (store.indexOf(attendee) +1 != store.getCount() || attendee.explicitlyAdded) {
            this.onStoreChange();
        }
    },
    
    /**
     * sync to filter panel
     */
    onStoreChange: function() {
        try {
            var filterPanel = this.app.getMainScreen().getCenterPanel().filterToolbar.activeFilterPanel,
                attendeeFilter = filterPanel.filterStore.getAt(filterPanel.filterStore.findExact('field', 'attender'));
                
            if (attendeeFilter) {
//                attendeeFilter.formFields.operator.setValue('in');
                attendeeFilter.formFields.value.setValue(this.getValue());
            } else {
                attendeeFilter = {field: 'attender', operator: 'oneof', value: this.getValue()}
                filterPanel.addFilter(new filterPanel.record(attendeeFilter));
            }
            
            filterPanel.onFilterChange();
        } catch (e) {}
    },
    
    applyState: function(state) {
        this.stateful = false;
        var explicitAttendee = (!state || Ext.isArray(state)) ? state : state.explicitAttendee;
        
        var rs = [];
        Ext.each(explicitAttendee, function(attendeeData) {
            var attendee = new Tine.Calendar.Model.Attender(attendeeData, 'new-' + Ext.id());
            attendee.explicitlyAdded = true;
            rs.push(attendee);
        }, this);
        
        this.store.removeAll();
        this.store.add(rs);
        
        if(state && state.sort){
            if (state.sort.field == 'sort') {
                this.store.each(function(attendee) {
                    var idx = state.sort.order.indexOf(attendee.get('user_type') + '-' + attendee.getUserId());
                    attendee.data.sort = idx >= 0 ? idx : 1000;
                }, this);
            }
            this.store.sort(state.sort.field, state.sort.direction);
            this.getView().sortState = state.sort;
        }
        this.stateful = true;
    },
    
    getState: function() {
        var explicitAttendee = [],
            sort = this.store.getSortState(),
            order = [];
        
        this.store.each(function(attendee) {
            if (attendee.get('user_id')){
                order.push(attendee.get('user_type') + '-' + attendee.getUserId());
                if (attendee.explicitlyAdded) {
                    explicitAttendee.push(attendee.data);
                }
            }
        }, this);
        
        if (sort.field == 'sort') {
            sort.order = order;
        }
        return {
            explicitAttendee: explicitAttendee,
            sort: sort
        }
    },
    
    /**
     * get checked attendee
     */
    getValue: function() {
        var attendeeData = [];
        this.store.each(function(attendee) {
            if (attendee.get('checked') && attendee.get('user_id')) {
                attendeeData.push(attendee.data);
            }
        }, this);
        
        return attendeeData;
    },
    
    /**
     * set filter value (done by MainScreenCenterPanel::onStoreLoad)
     * 
     * @param {Array} filter
     */
    setFilterValue: function(filters) {
        var value = this.extractFilterValue(filters) || [];

        // save west panel scrolling position so we can restore it after selecting nodes
        if (this.app.getMainScreen().getWestPanel().body) {
            this.leftPanelScrollTop = this.app.getMainScreen().getWestPanel().body.getScroll().top;
        }
        
        this.setValue(value);
    },

    /**
     * extract attendee filter from filters
     *
     * @param {Array} filter
     */
    extractFilterValue: function(filters) {
        var value = [];

        // use first OR panel in case of filterPanel
        Ext.each(filters, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                filters = filterData.filters[0].filters;
                return false;
            }
        }, this);

        // use first attedee filter
        Ext.each(filters, function(filter) {
            if (filter.field == 'attender') {
                value = filter.value;
                return false;
            }
        }, this);

        return value;
    },

    /**
     * set attendee value
     * 
     * @param {Array} value
     */
    setValue: function(value) {
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(value),
            selections = this.getSelectionModel().getSelections(),
            activeEditor = this.activeEditor,
            state = Ext.state.Manager.get(this.stateId);
        
        this.store.suspendEvents();
        this.applyState(state);
        
        this.store.each(function(attendee) {
            attendee.set('checked', false);
        }, this);
        
        attendeeStore.each(function(attendee) {
            var currentAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.store, attendee);
            if (! currentAttendee) {
                if (state && state.sort && state.sort.order) {
                    var idx = state.sort.order.indexOf(attendee.get('user_type') + '-' + attendee.getUserId());
                    attendee.data.sort = idx >= 0 ? idx : 1000;
                }
                
                this.store.add(attendee);
                attendee.set('checked', true);
            } else {
                currentAttendee.set('checked', true);
            }
        }, this);
        
        this.store.add([new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 'new-' + Ext.id() )]);
        this.store.applySort();
        
        this.store.resumeEvents();
        
        try {
            this.getView().refresh();
        } catch (e) {
            // grid could not be refreshed at the moment, maybe a row has been deleted
        }
        
        if (activeEditor) {
            this.startEditing(activeEditor.row, activeEditor.col);
        }
        
        Ext.each(selections, function(attendee) {
            var toSelect = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.store, attendee);
            if (toSelect) {
                this.getSelectionModel().selectRecords([toSelect], true);
            }
        }, this);
    }
});

