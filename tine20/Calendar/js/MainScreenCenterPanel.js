/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.MainScreenCenterPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {String} activeView
     */
    activeView: 'week',
    
    startDate: new Date().clearTime(),
    
    calendarPanels: {},
    
    border: false,
    layout: 'border',
    
    stateful: true,
    stateId: 'cal-mainscreen',
    stateEvents: ['changeview'],
    
    getState: function() {
        return Ext.copyTo({}, this, 'activeView');
    },
    
    applyState: Ext.emptyFn,
    
    initComponent: function() {
        this.addEvents(
        /**
         * @event changeview
         * fired if an event got clicked
         * @param {Tine.Calendar.MainScreenCenterPanel} mspanel
         * @param {String} view
         */
        'changeview');
        
        this.recordClass = Tine.Calendar.Model.Event;
        
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        this.i18nContainerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.i18nContainersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        var state = Ext.state.Manager.get(this.stateId, {});
        Ext.apply(this, state);
        
        this.defaultFilters = [
            {field: 'attender', operator: 'in', value: [Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                user_id: Tine.Tinebase.registry.get('currentAccount')
            })]},
            {field: 'attender_status', operator: 'notin', value: ['DECLINED']}
        ];
        this.filterToolbar = this.getFilterToolbar();
        this.filterToolbar.onFilterChange = this.refresh.createDelegate(this, [false]);
        this.filterToolbar.getAllFilterData = this.getAllFilterData.createDelegate(this);
        
        this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push(
            {field: 'period'},
            {field: 'grants'}
        );
        
        this.initActions();
        this.initLayout();
        
        Tine.Calendar.MainScreenCenterPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.i18nEditActionText ? this.app.i18n._hidden(this.i18nEditActionText) : String.format(Tine.Tinebase.translation._hidden('Edit {0}'), this.i18nRecordName),
            disabled: true,
            handler: this.onEditInNewWindow.createDelegate(this, ["edit"]),
            iconCls: 'action_edit'
        });
        
        this.action_addInNewWindow = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(Tine.Tinebase.translation._hidden('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow.createDelegate(this, ["add"]),
            iconCls: 'action_add'
        });
        
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? i18nDeleteActionText[0] : String.format(Tine.Tinebase.translation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? i18nDeleteActionText[1] : String.format(Tine.Tinebase.translation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.translation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.showDayView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'day',
            text: this.app.i18n._('Day'),
            iconCls: 'cal-day-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["day"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showWeekView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'week',
            text: this.app.i18n._('Week'),
            iconCls: 'cal-week-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["week"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showMonthView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'month',
            text: this.app.i18n._('Month'),
            iconCls: 'cal-month-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["month"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        
        this.changeViewActions = [
            this.showDayView,
            this.showWeekView,
            this.showMonthView
        ];
        
        this.recordActions = [
            this.action_editInNewWindow,
            this.action_deleteRecord
        ];
        
        this.actionUpdater = new  Tine.widgets.ActionUpdater({
            actions: this.recordActions,
            grantsProperty: false,
            containerProperty: false
        });
    },
    
    /**
     * @private
     * 
     * NOTE: Order of items matters! Ext.Layout.Border.SplitRegion.layout() does not
     *       fence the rendering correctly, as such it's impotant, so have the ftb
     *       defined after all other layout items
     */
    initLayout: function() {
        this.items = [{
            region: 'center',
            layout: 'card',
            activeItem: 0,
            border: false,
            items: [this.getCalendarPanel(this.activeView)]
        }];
        
        // preload data
        this.getCalendarPanel(this.activeView).getStore().load({});
        
        // add detail panel
        if (this.detailsPanel) {
            this.items.push({
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
                
            });
            //this.detailsPanel.doBind(this.activeView);
        }
        
        // add filter toolbar
        if (this.filterToolbar) {
            this.items.push({
                region: 'north',
                border: false,
                items: this.filterToolbar,
                listeners: {
                    scope: this,
                    afterlayout: function(ct) {
                        ct.setHeight(this.filterToolbar.getHeight());
                        ct.ownerCt.layout.layout();
                    }
                }
            });
        }
    },
    
    changeView: function(view, startDate) {
        if (startDate && Ext.isDate(startDate)) {
            this.startDate = startDate.clone();
        }
        
        var panel = this.getCalendarPanel(view);
        var cardPanel = this.items.first();
        
        if(panel.rendered) {
            cardPanel.layout.setActiveItem(panel.id);
        } else {
            cardPanel.add(panel);
            cardPanel.layout.setActiveItem(panel.id);
            cardPanel.doLayout();
        }
        
        this.activeView = view;
        
        // move around changeViewButtons
        var rightRow = Ext.get(Ext.DomQuery.selectNode('tr[class=x-toolbar-right-row]', panel.tbar.dom));
        
        for (var i=this.changeViewActions.length-1; i>=0; i--) {
            rightRow.insertFirst(this.changeViewActions[i].getEl().parent().dom);
        }
        this['show' + Ext.util.Format.capitalize(view) +  'View'].toggle(true);
        
        // update actions
        this.updateEventActions();
        
        // update data
        panel.getView().updatePeriod({from: this.startDate});
        panel.getStore().load({});
        
        this.fireEvent('changeview', this, view);
    },
    
    getActionToolbar: Tine.widgets.grid.GridPanel.prototype.getActionToolbar,
    
    getActionToolbarItems: Tine.widgets.grid.GridPanel.prototype.getActionToolbarItems,
    
    /**
     * returns all filter data for current view
     */
    getAllFilterData: function() {
        var store = this.getCalendarPanel(this.activeView).getStore();
        
        var options = {};
        this.onStoreBeforeload(store, options);
        
        return options.params.filter;
    },
    
    getCustomfieldFilters: Tine.widgets.grid.GridPanel.prototype.getCustomfieldFilters,
    
    getFilterToolbar: Tine.widgets.grid.GridPanel.prototype.getFilterToolbar,
    
    /**
     * returns store of currently active view
     */
    getStore: function() {
        return this.getCalendarPanel(this.activeView).getStore();
    },
    
    onContextMenu: function(e) {
        e.stopEvent();
        
        var view = this.getCalendarPanel(this.activeView).getView();
        var event = view.getTargetEvent(e);
        var datetime = view.getTargetDateTime(e);
        
        var addAction;
        if (datetime || event) {
            var dtStart = datetime || event.get('dtstart').clone();
            if (dtStart.format('H:i') == '00:00') {
                dtStart = dtStart.add(Date.HOUR, 9);
            }
            addAction = {
                text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(Tine.Tinebase.translation._hidden('Add {0}'), this.i18nRecordName),
                handler: this.onEditInNewWindow.createDelegate(this, ["add", dtStart]),
                iconCls: 'action_add'
            };
        } else {
            addAction = this.action_addInNewWindow;
        }
        
        if (event) {
            view.getSelectionModel().select(event, e, e.ctrlKey);
        } else {
            view.getSelectionModel().clearSelections();
        }
           
        var ctxMenu = new Ext.menu.Menu({
            items: this.recordActions.concat(addAction)
        });
        ctxMenu.showAt(e.getXY());
    },
    
    onDeleteRecords: function() {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        var containsRecurBase = false;
        var containsRecurInstance = false;
        
        Ext.each(selection, function(event){
            event.ui.markDirty();
            if (event.isRecurInstance()) {
                containsRecurInstance = true;
            }
            if (event.isRecurBase()) {
                containsRecurBase = true;
            }
        });
        
        if (selection.length > 1 && (containsRecurBase || containsRecurInstance)) {
            Ext.Msg.show({
                title: this.app.i18n._('Please Change Selection'), 
                msg: this.app.i18n._('Your selection contains recurring events. Recuring events must be deleted seperatly!'),
                icon: Ext.MessageBox.INFO,
                buttons: Ext.Msg.OK,
                scope: this,
                fn: function() {
                    this.onDeleteRecordsConfirmFail(panel, selection);
                }
            });
            return;
        }
        
        if (selection.length == 1 && (containsRecurBase || containsRecurInstance)) {
            if (containsRecurBase) {
                Ext.MessageBox.confirm(
                    this.app.i18n._('Confirm Deletion of Series'),
                    this.app.i18n._('Do you really want to delete all events of this recurring event series?'),
                    function(btn) {
                        if(btn == 'yes') {
                            panel.loadMask.show();
                            this.onDeleteRecordsConfirmNonRecur(panel, selection);
                            this.refresh(true);
                        } else {
                            this.onDeleteRecordsConfirmFail(panel, selection);
                        }
                    }, this
                );
            } else {
                this.deleteMethodWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                    title: this.app.i18n._('Delete Event'),
                    scope: this,
                    options: [
                        {text: this.app.i18n._('Delete whole series'), name: 'all'},
                        {text: this.app.i18n._('Delete this and all future events'), name: 'future'},
                        {text: this.app.i18n._('Delete this event only'), name: 'this'}
                    ],
                    handler: function(option) {
                        switch (option) {
                            case 'all':
                            case 'this':
                            case 'future':
                                panel.getTopToolbar().beforeLoad();
                                if (option !== 'this') {
                                    panel.loadMask.show();
                                }
                                
                                var options = {
                                    scope: this,
                                    success: function() {
                                        if (option === 'this') {
                                            Ext.each(selection, function(event){
                                                panel.getStore().remove(event);
                                            });
                                            panel.getTopToolbar().onLoad();
                                        } else {
                                            this.refresh(true);
                                        }
                                        
                                    },
                                    failure: function () {
                                        panel.getTopToolbar().onLoad();
                                        Ext.MessageBox.alert(Tine.Tinebase.translation._hidden('Failed'), String.format(this.app.i18n.n_('Failed not delete event', 'Failed to delete the {0} events', selection.length), selection.length)) 
                                    }
                                };
                                
                                if (option === 'all') {
                                    Tine.Calendar.backend.deleteRecurSeries(selection[0], options);
                                } else {
                                    Tine.Calendar.backend.createRecurException(selection[0], true, option === 'future', options);
                                }
                                break;
                            default:
                                this.onDeleteRecordsConfirmFail(panel, selection);
                                break;
                        }
                    }
                });
            }
            return;
        }
        
        // else
        var i18nQuestion = String.format(this.app.i18n.ngettext('Do you really want to delete this event?', 'Do you really want to delete the {0} selected events?', selection.length), selection.length);
        Ext.MessageBox.confirm(Tine.Tinebase.translation._hidden('Confirm'), i18nQuestion, function(btn) {
            if(btn == 'yes') {
                this.onDeleteRecordsConfirmNonRecur(panel, selection);
            } else {
                this.onDeleteRecordsConfirmFail(panel, selection);
            }
        }, this);
        
    },
    
    onDeleteRecordsConfirmNonRecur: function(panel, selection) {
        panel.getTopToolbar().beforeLoad();
        
        var options = {
            scope: this,
            success: function() {
                panel.getTopToolbar().onLoad();
                Ext.each(selection, function(event){
                    panel.getStore().remove(event);
                });
            },
            failure: function () {
                panel.getTopToolbar().onLoad();
                Ext.MessageBox.alert(Tine.Tinebase.translation._hidden('Failed'), String.format(this.app.i18n.n_('Failed not delete event', 'Failed to delete the {0} events', selection.length), selection.length)) 
            }
        };
        
        Tine.Calendar.backend.deleteRecords(selection, options);
    },
    
    onDeleteRecordsConfirmFail: function(panel, selection) {
        Ext.each(selection, function(event){
                event.ui.clearDirty();
        });
    },
    
    /**
     * @param {String} action add|edit
     */
    onEditInNewWindow: function(action, dtStart) {
        var event = null;
        
        if (action == 'edit') {
            var panel = this.getCalendarPanel(this.activeView);
            var selection = panel.getSelectionModel().getSelectedEvents();
            if (Ext.isArray(selection) && selection.length == 1) {
                event = selection[0];
            }
        }
        
        if (! event) {
            event = new Tine.Calendar.Model.Event(Tine.Calendar.Model.Event.getDefaultData(), 0);
            if (Ext.isDate(dtStart)) {
                event.set('dtstart', dtStart);
                event.set('dtend', dtStart.add(Date.HOUR, 1));
            }
        }
        
        Tine.Calendar.EventEditDialog.openWindow({
            record: Ext.util.JSON.encode(event.data),
            recordId: event.data.id,
            listeners: {
                scope: this,
                update: function(eventJson) {
                    //var updatedEvent = new Tine.Calendar.Model.Event(Ext.util.JSON.decode(eventJson), event.id);
                    var updatedEvent = Tine.Calendar.backend.recordReader({responseText: eventJson});
                    updatedEvent.dirty = true;
                    event.phantom = (action == 'edit');
                    
                    var panel = this.getCalendarPanel(this.activeView);
                    var store = panel.getStore();
                    
                    event = store.getById(event.id);
                    
                    store.remove(event);
                    store.add(updatedEvent);
                    
                    panel.onUpdateEvent(updatedEvent);
                }
            }
        });
    },
    
    onKeyDown: function(e) {
        if (e.ctrlKey) {
            switch (e.getKey()) {
                case e.A:
                    // select only current page
                    //this.grid.getSelectionModel().selectAll(true);
                    e.preventDefault();
                    break;
                case e.E:
                    if (!this.action_editInNewWindow.isDisabled()) {
                        this.onEditInNewWindow('edit');
                    }
                    e.preventDefault();
                    break;
                case e.N:
                    if (!this.action_addInNewWindow.isDisabled()) {
                        this.onEditInNewWindow('add');
                    }
                    e.preventDefault();
                    break;
                
            }
        } else {
            switch (e.getKey()) {
                case e.DELETE:
                    if (! this.action_deleteRecord.isDisabled()) {
                        this.onDeleteRecords.call(this);
                    }
                    break;
            }
        }
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        options.params = options.params || {};
        
        // allways start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [];
        
        // note, we can't use the 'normal' plugin approach here, cause we have to deal with n stores
        //var calendarSelectionPlugin = this.app.getMainScreen().getContainerTreePanel().getFilterPlugin();
        //calendarSelectionPlugin.onBeforeLoad.call(calendarSelectionPlugin, store, options);
        
        this.filterToolbar.onBeforeLoad.call(this.filterToolbar, store, options);
    },
    
    /**
     * called when store loaded data
     */
    onStoreLoad: function(store, options) {
        // check if store is current store
        if (store !== this.getCalendarPanel(this.activeView).getStore()) {
            console.log('not active anymore');
            return;
        }
        
        // update filtertoolbar
        this.filterToolbar.setValue(store.proxy.jsonReader.jsonData.filter);
        
        // update tree
        Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContainerTreePanel().getFilterPlugin().setValue(store.proxy.jsonReader.jsonData.filter);
    },
    
    refresh: function(refresh) {
        var panel = this.getCalendarPanel(this.activeView);
        panel.getStore().load({
            refresh: refresh
        });
    },
    
    updateEventActions: function() {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        this.actionUpdater.updateActions(selection);
        if (this.detailsPanel) {
            this.detailsPanel.onDetailsUpdate(panel.getSelectionModel());
        }
    },
    
    updateView: function(which) {
        var panel = this.getCalendarPanel(which);
        var period = panel.getTopToolbar().getPeriod();
        
        panel.getView().updatePeriod(period);
        panel.getStore().load({});
        //this.updateMiniCal();
    },
    
    /**
     * returns requested CalendarPanel
     * 
     * @param {String} which
     * @return {Tine.Calendar.CalendarPanel}
     */
    getCalendarPanel: function(which) {
        if (! this.calendarPanels[which]) {
            var store = new Ext.data.Store({
                //autoLoad: true,
                id: 'id',
                fields: Tine.Calendar.Model.Event,
                proxy: Tine.Calendar.backend,
                reader: new Ext.data.JsonReader({}), //Tine.Calendar.backend.getReader(),
                listeners: {
                    scope: this,
                    'beforeload': this.onStoreBeforeload,
                    'load': this.onStoreLoad
                }
            });
            
            var tbar = new Tine.Calendar.PagingToolbar({
                view: which,
                store: store,
                dtStart: this.startDate,
                listeners: {
                    scope: this,
                    // NOTE: only render the button once for the toolbars
                    //       the buttons will be moved on chageView later
                    render: function(tbar) {
                        for (var i=0; i<this.changeViewActions.length; i++) {
                            if (! this.changeViewActions[i].rendered) {
                                tbar.addButton(this.changeViewActions[i]);
                            }
                        }
                    },
                    change: this.updateView.createDelegate(this, [which]),
                    refresh: this.refresh.createDelegate(this, [true])
                }
            });
            
            var view;
            switch(which) {
                case 'day':
                    view = new Tine.Calendar.DaysView({
                        startDate: tbar.getPeriod().from,
                        numOfDays: 1
                    });
                    break;
                case 'week':
                    view = new Tine.Calendar.DaysView({
                        startDate: tbar.getPeriod().from,
                        numOfDays: 7
                    });
                    break;
                case 'month':
                    view = new Tine.Calendar.MonthView({
                        period: tbar.getPeriod()
                    });
            }
            
            view.on('changeView', this.changeView, this);
            view.on('changePeriod', function(period) {
                this.startDate = period.from;
                this.updateMiniCal();
            }, this);
            
            view.on('dblclick', this.onEditInNewWindow.createDelegate(this, ["edit"]));
            view.on('contextmenu', this.onContextMenu, this);
            
            this.calendarPanels[which] = new Tine.Calendar.CalendarPanel({
                tbar: tbar,
                store: store,
                view: view
            });
            
            this.calendarPanels[which].getSelectionModel().on('selectionchange', this.updateEventActions, this);
            this.calendarPanels[which].on('keydown', this.onKeyDown, this);
        }
        
        return this.calendarPanels[which];
    },
    
    updateMiniCal: function() {
        var miniCal = Ext.getCmp('cal-mainscreen-minical');
        var weekNumbers = null;
        var period = this.getCalendarPanel(this.activeView).getView().getPeriod();
        
        switch (this.activeView) {
            case 'week' :
                weekNumbers = [period.from.add(Date.DAY, 1).getWeekOfYear()]
                break;
            case 'month' :
                weekNumbers = [];
                var startWeek = period.from.add(Date.DAY, 1).getWeekOfYear();
                var numWeeks = Math.round((period.until.getTime() - period.from.getTime()) / Date.msWEEK);
                for (var i=0; i<numWeeks; i++) {
                    weekNumbers.push(startWeek + i);
                }
            break;
        }
        miniCal.update(this.startDate, true, weekNumbers);
    }
    
});
