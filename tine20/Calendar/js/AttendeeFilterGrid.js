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
    addNewAttendeeText: 'Add attendee', // _('Add attendee')
    
    initComponent: function() {
        this.record = new Tine.Calendar.Model.Event({
            editGrant: true
        });
        
        this.selModel = new Ext.grid.RowSelectionModel();
        this.selModel.on('beforerowselect', this.onBeforeRowSelect, this);
        
        Tine.Calendar.AttendeeFilterGrid.superclass.initComponent.call(this);
        
        // apply  initial state
        var explicitAttendee = Ext.state.Manager.get(this.stateId);
        this.applyState(explicitAttendee);
        
        this.store.on('add', this.onStoreAdd, this);
        this.store.on('remove', this.onStoreRemove, this);
        this.store.on('update', this.onStoreUpdate, this /*, {buffer: 1000}*/);
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
            tooltip: this.app.i18n._('Check to filter for this attendee')
        }));
        
        this.plugins = Ext.isArray(this.plugins) ? this.plugins : [];
        this.plugins.push(this.columns[0]);
    },
    
    onBeforeRowSelect: function(sm, idx, keep, attendee) {
        if (! attendee.get('user_id')) {
            return false;
        }
    },
    
    onStoreAdd: function() {
        // don't save initial 'add attendee' record
        if (this.store.getCount() > 1) {
            Ext.state.Manager.set(this.stateId, this.getState());
            this.onStoreChange();
        }
    },
    
    onStoreRemove: function() {
        Ext.state.Manager.set(this.stateId, this.getState());
        this.onStoreChange();
    },
    
    onStoreUpdate: function(store, attendee) {
        if (attendee.get('user_id')) {
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
    
    applyState: function(explicitAttendee) {
        var rs = [];
        Ext.each(explicitAttendee, function(attendeeData) {
            var attendee = new Tine.Calendar.Model.Attender(attendeeData, 'new-' + Ext.id());
            attendee.explicitlyAdded = true;
            rs.push(attendee);
        }, this);
        
        this.store.removeAll();
        this.store.add(rs);
    },
    
    getState: function() {
        var explicitAttendee = [];
        
        this.store.each(function(attendee) {
            if (attendee.get('user_id') && attendee.explicitlyAdded) {
                explicitAttendee.push(attendee.data)
            }
        }, this);
        
        return explicitAttendee;
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
        
        // save west panel scrolling position so we can restore it after selecting nodes
        if (this.app.getMainScreen().getWestPanel().body) {
            this.leftPanelScrollTop = this.app.getMainScreen().getWestPanel().body.getScroll().top;
        }
        
        this.setValue(value);
    },
    
    /**
     * set attendee value
     * 
     * @param {Array} value
     */
    setValue: function(value) {
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(value),
            selections = this.getSelectionModel().getSelections(),
            explicitAttendee = Ext.state.Manager.get(this.stateId),
            activeEditor = this.activeEditor;
        
        this.store.suspendEvents();
        this.applyState(explicitAttendee);
        
        this.store.each(function(attendee) {
            attendee.set('checked', false);
        }, this);
        
        attendeeStore.each(function(attendee) {
            var currentAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(this.store, attendee);
            if (! currentAttendee) {
                this.store.add(attendee);
                attendee.set('checked', true);
            } else {
                currentAttendee.set('checked', true);
            }
        }, this);
        
        this.store.add([new Tine.Calendar.Model.Attender(Tine.Calendar.Model.Attender.getDefaultData(), 'new-' + Ext.id() )]);
        this.store.applySort();
        
        this.store.resumeEvents();
        
        this.getView().refresh();
        
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

