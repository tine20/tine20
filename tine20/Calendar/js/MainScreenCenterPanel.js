/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* global Ext, Tine */

Ext.ns('Tine.Calendar');

Tine.Calendar.MainScreenCenterPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {String} activeView
     */
    activeView: 'weekSheet',
    
    startDate: new Date().clearTime(),
    
    /**
     * $property Object view -> startdate
     */
    startDates: null,
    
    /**
     * @cfg {String} loadMaskText
     * _('Loading events, please wait...')
     */
    loadMaskText: 'Loading events, please wait...',
    
    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,
    
    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,
    
    periodRe: /^(day|week|month)/i,
    presentationRe: /(sheet|grid)$/i,
    
    calendarPanels: {},
    
    border: false,
    layout: 'border',
    
    stateful: true,
    stateId: 'cal-mainscreen',
    stateEvents: ['changeview'],
    
    getState: function () {
        return Ext.copyTo({}, this, 'activeView');
    },
    
    applyState: Ext.emptyFn,
    
    initComponent: function () {
        try {
            var me = this;
            
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
            
            this.loadMaskText = this.app.i18n._hidden(this.loadMaskText);
            
            var state = Ext.state.Manager.get(this.stateId, {});
            Ext.apply(this, state);
            
            this.defaultFilters = [
                {field: 'attender', operator: 'in', value: [Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                    user_id: Tine.Tinebase.registry.get('currentAccount')
                })]},
                {field: 'attender_status', operator: 'notin', value: ['DECLINED']}
            ];
            this.filterToolbar = this.getFilterToolbar({
                onFilterChange: this.refresh.createDelegate(this, [false]),
                getAllFilterData: this.getAllFilterData.createDelegate(this)
            });
            
            this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push(
                {field: 'period'},
                {field: 'grants'}
            );
            
            this.startDates = [];
            this.initActions();
            this.initLayout();
            
            // init autoRefresh
            this.autoRefreshTask = new Ext.util.DelayedTask(this.refresh.createDelegate(this, [true]), this, [{
                refresh: true,
                autoRefresh: true
            }]);
        
            Tine.Calendar.MainScreenCenterPanel.superclass.initComponent.call(this);
        } catch (e) {
            console.err(e.stack ? e.stack : e);
        }
    },
    
    initActions: function () {
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
        
        this.actions_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Page'),
            handler: this.onPrint,
            iconCls:'action_print',
            scope: this
        });
        
        this.showSheetView = new Ext.Button({
            pressed: String(this.activeView).match(/sheet$/i),
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top',
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Sheet'),
            handler: this.changeView.createDelegate(this, ["sheet"]),
            iconCls:'cal-sheet-view-type',
            xtype: 'tbbtnlockedtoggle',
            toggleGroup: 'Calendar_Toolbar_tgViewTypes',
            scope: this
        });
        
        this.showGridView = new Ext.Button({
            pressed: String(this.activeView).match(/grid$/i),
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top',
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Grid'),
            handler: this.changeView.createDelegate(this, ["grid"]),
            iconCls:'cal-grid-view-type',
            xtype: 'tbbtnlockedtoggle',
            toggleGroup: 'Calendar_Toolbar_tgViewTypes',
            scope: this
        });
        
        this.showDayView = new Ext.Toolbar.Button({
            pressed: String(this.activeView).match(/^day/i),
            text: this.app.i18n._('Day'),
            iconCls: 'cal-day-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["day"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showWeekView = new Ext.Toolbar.Button({
            pressed: String(this.activeView).match(/^week/i),
            text: this.app.i18n._('Week'),
            iconCls: 'cal-week-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["week"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showMonthView = new Ext.Toolbar.Button({
            pressed: String(this.activeView).match(/^month/i),
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
    
        
    getActionToolbar: Tine.widgets.grid.GridPanel.prototype.getActionToolbar,
    
    getActionToolbarItems: function() {
        return {
            xtype: 'buttongroup',
            items: [
                this.showSheetView,
                this.showGridView
            ]
        };
    },
    
    /**
     * @private
     * 
     * NOTE: Order of items matters! Ext.Layout.Border.SplitRegion.layout() does not
     *       fence the rendering correctly, as such it's impotant, so have the ftb
     *       defined after all other layout items
     */
    initLayout: function () {
        this.items = [{
            region: 'center',
            layout: 'card',
            activeItem: 0,
            border: false,
            items: [this.getCalendarPanel(this.activeView)]
        }];
        
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
                    afterlayout: function (ct) {
                    	ct.suspendEvents();
                        ct.setHeight(this.filterToolbar.getHeight());
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            });
        }
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Calendar.MainScreenCenterPanel.superclass.onRender.apply(this, arguments);
        
        this.loadMask = new Ext.LoadMask(this.body, {msg: this.loadMaskText});
    },
    
    getViewParts: function (view) {
        view = String(view);
        
        var activeView = String(this.activeView),
            periodMatch = view.match(this.periodRe),
            period = Ext.isArray(periodMatch) ? periodMatch[0] : null,
            activePeriodMatch = activeView.match(this.periodRe),
            activePeriod = Ext.isArray(activePeriodMatch) ? activePeriodMatch[0] : 'week',
            presentationMatch = view.match(this.presentationRe),
            presentation = Ext.isArray(presentationMatch) ? presentationMatch[0] : null,
            activePresentationMatch = activeView.match(this.presentationRe),
            activePresentation = Ext.isArray(activePresentationMatch) ? activePresentationMatch[0] : 'sheet';
            
        return {
            period: period ? period : activePeriod,
            presentation: presentation ? presentation : activePresentation,
            toString: function() {return this.period + Ext.util.Format.capitalize(this.presentation);}
        };
    },
    
    changeView: function (view, startDate) {
        try {
            // autocomplete view
            var viewParts = this.getViewParts(view);
            view = viewParts.toString();
            
            Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::changeView(' + view + ',' + startDate + ')');
            
            // save current startDate
            this.startDates[this.activeView] = this.startDate.clone();
            
            if (startDate && Ext.isDate(startDate)) {
                this.startDate = startDate.clone();
            } else {
                // see if a recent startDate of that view fits
                var lastStartDate = this.startDates[view],
                    currentPeriod = this.getCalendarPanel(this.activeView).getView().getPeriod();
                    
                if (Ext.isDate(lastStartDate) && lastStartDate.between(currentPeriod.from, currentPeriod.until)) {
                    this.startDate = this.startDates[view].clone();
                }
            }
            
            var panel = this.getCalendarPanel(view);
            var cardPanel = this.items.first();
            
            if (panel.rendered) {
                cardPanel.layout.setActiveItem(panel.id);
            } else {
                cardPanel.add(panel);
                cardPanel.layout.setActiveItem(panel.id);
                cardPanel.doLayout();
            }
            
            this.activeView = view;
            
            // move around changeViewButtons
            var rightRow = Ext.get(Ext.DomQuery.selectNode('tr[class=x-toolbar-right-row]', panel.tbar.dom));
            
            for (var i = this.changeViewActions.length - 1; i >= 0; i--) {
                rightRow.insertFirst(this.changeViewActions[i].getEl().parent().dom);
            }
            this['show' + Ext.util.Format.capitalize(viewParts.period) +  'View'].toggle(true);
            this['show' + Ext.util.Format.capitalize(viewParts.presentation) +  'View'].toggle(true);
            
            // update actions
            this.updateEventActions();
            
            // update data
            panel.getView().updatePeriod({from: this.startDate});
            panel.getStore().load({});
            
            this.fireEvent('changeview', this, view);
        } catch (e) {
            console.err(e.stack ? e.stack : e);
        }
    },
    
    /**
     * returns all filter data for current view
     */
    getAllFilterData: function () {
        var store = this.getCalendarPanel(this.activeView).getStore();
        
        var options = {
            refresh: true, // ommit loadMask
            noPeriodFilter: true
        };
        this.onStoreBeforeload(store, options);
        
        return options.params.filter;
    },
    
    getCustomfieldFilters: Tine.widgets.grid.GridPanel.prototype.getCustomfieldFilters,
    
    getFilterToolbar: Tine.widgets.grid.GridPanel.prototype.getFilterToolbar,
    
    /**
     * returns store of currently active view
     */
    getStore: function () {
        return this.getCalendarPanel(this.activeView).getStore();
    },
    
    onContextMenu: function (e) {
        e.stopEvent();
        
        var view = this.getCalendarPanel(this.activeView).getView();
        var event = view.getTargetEvent(e);
        var datetime = view.getTargetDateTime(e);
        
        var addAction, responseAction;
        if (datetime || event) {
            var dtStart = datetime || event.get('dtstart').clone();
            if (dtStart.format('H:i') === '00:00') {
                dtStart = dtStart.add(Date.HOUR, 9);
            }
            
            addAction = {
                text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(Tine.Tinebase.translation._hidden('Add {0}'), this.i18nRecordName),
                handler: this.onEditInNewWindow.createDelegate(this, ["add", {dtStart: dtStart, is_all_day_event: datetime && datetime.is_all_day_event}]),
                iconCls: 'action_add'
            };
            
            // assemble response action
            if (event) {
                var statusStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus'),
                    myAttenderRecord = event.getMyAttenderRecord(),
                    myAttenderStatus = myAttenderRecord ? myAttenderRecord.get('status') : null,
                    myAttenderStatusRecord = statusStore.getById(myAttenderStatus);
                    
                    
                if (myAttenderRecord) {
                    responseAction = {
                        text: this.app.i18n._('Set my response'),
                        icon: myAttenderStatusRecord ? myAttenderStatusRecord.get('icon') : false,
                        menu: []
                    };
                    
                    statusStore.each(function(status) {
                        responseAction.menu.push({
                            text: status.get('i18nValue'),
                            handler: this.setResponseStatus.createDelegate(this, [event, status.id]),
                            icon: status.get('icon'),
                            disabled: myAttenderRecord.get('status') === status.id
                        });
                    }, this);
                }
            }

        } else {
            addAction = this.action_addInNewWindow;
        }
        
        if (event) {
            view.getSelectionModel().select(event, e, e.ctrlKey);
        } else {
            view.getSelectionModel().clearSelections();
        }
           
        var ctxMenu = new Ext.menu.Menu({
            items: this.recordActions.concat(addAction, responseAction || [])
        });
        ctxMenu.showAt(e.getXY());
    },
    
    checkPastEvent: function(event, checkBusyConflicts, actionType) {
        
        var start = event.get('dtstart').getTime();
        var now = new Date().getTime();

        switch (actionType) {
            case 'update':
                var title = this.app.i18n._('Updating event in the past'),
                    optionYes = this.app.i18n._('Update this event'),
                    optionNo = this.app.i18n._('Do not update this event');
                break;                    
            case 'add':            
            default:
                var title = this.app.i18n._('Creating event in the past'),
                    optionYes = this.app.i18n._('Create this event'),
                    optionNo = this.app.i18n._('Do not create this event');
        }
        
        if(start < now) {
            Tine.widgets.dialog.MultiOptionsDialog.openWindow({                
                title: title,
                height: 170,
                scope: this,
                options: [
                    {text: optionYes, name: 'yes'},
                    {text: optionNo, name: 'no'}                   
                ],
                
                handler: function(option) {
                    try {
                        switch (option) {
                            case 'yes':
                                if (actionType == 'update') this.onUpdateEvent(event, true, actionType);
                                else this.onAddEvent(event, checkBusyConflicts, true);
                                break;
                            case 'no':
                            default:
                                try {
                                    var panel = this.getCalendarPanel(this.activeView);
                                    if(panel) {
                                        var store = panel.getStore(),
                                            view = panel.getView();
                                    }
                                } catch(e) {
                                    var panel = null, 
                                        store = null,
                                        view = null;
                                }
                                
                                if (actionType == 'add') {
                                    if(store) store.remove(event);
                                } else {
                                    if (view && view.calPanel && view.rendered) {
                                        this.loadMask.show();
                                        store.reload();
//                                        TODO: restore original event so no reload is needed
//                                        var updatedEvent = event;
//                                        updatedEvent.dirty = false;
//                                        updatedEvent.editing = false;
//                                        updatedEvent.modified = null;
//                                        
//                                        store.replaceRecord(event, updatedEvent);
//                                        view.getSelectionModel().select(event);
                                    }
                                    this.setLoading(false);
                                }
                        }
                    } catch (e) {
                        Tine.log.error('Tine.Calendar.MainScreenCenterPanel::checkPastEvent::handler');
                        Tine.log.error(e);
                    }
                }             
            });
        } else {
            if (actionType == 'update') this.onUpdateEvent(event, true, actionType);
            else this.onAddEvent(event, checkBusyConflicts, true);
        }
    },
    
    onAddEvent: function(event, checkBusyConflicts, pastChecked) {

        if(!pastChecked) {
            this.checkPastEvent(event, checkBusyConflicts, 'add');
            return;
        }
        
        this.setLoading(true);
        
        // remove temporary id
        if (event.get('id').match(/new/)) {
            event.set('id', '');
        }
        
        if (event.isRecurBase()) {
            this.loadMask.show();
        }
        
        var panel = this.getCalendarPanel(this.activeView),
            store = panel.getStore(),
            view = panel.getView();
                        
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(createdEvent) {
                if (createdEvent.isRecurBase()) {
                    store.load({refresh: true});
                } else {
                    store.replaceRecord(event, createdEvent);
                    this.setLoading(false);
                    if (view && view.calPanel && view.rendered) {
                        view.getSelectionModel().select(createdEvent);
                    }
                }
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConflicts: checkBusyConflicts === false ? 0 : 1
        });
    },
    
    onUpdateEvent: function(event, pastChecked, actionType) {
        
        if(!actionType || actionType == 'edit') actionType = 'update';
        this.setLoading(true);
        
        if(!pastChecked) {
            this.checkPastEvent(event, null, actionType);
            return;
        }
        
        if (event.isRecurBase()) {
           if(this.loadMask) this.loadMask.show();
        }
        
        if (event.isRecurInstance() || (event.isRecurBase() && ! event.get('rrule').newrule)) {
            Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Update Event'),
                height: 170,
                scope: this,
                options: [
                    {text: this.app.i18n._('Update this event only'), name: 'this'},
                    {text: this.app.i18n._('Update this and all future events'), name: (event.isRecurBase() && ! event.get('rrule').newrule) ? 'series' : 'future'},
                    {text: this.app.i18n._('Update whole series'), name: 'series'},
                    {text: this.app.i18n._('Update nothing'), name: 'cancel'}
                    
                ],
                handler: function(option) {
                    try {
                        var panel = this.getCalendarPanel(this.activeView),
                            store = panel.getStore(),
                            view = panel.getView();
                            
                        switch (option) {
                            case 'series':
                                this.loadMask.show();
                                
                                var options = {
                                    scope: this,
                                    success: function() {
                                        store.load({refresh: true});
                                    },
                                    failure: this.onProxyFail.createDelegate(this, [event], true)
                                };
                                
                                Tine.Calendar.backend.updateRecurSeries(event, options);
                                break;
                                
                            case 'this':
                            case 'future':
                                var options = {
                                    scope: this,
                                    success: function(updatedEvent) {
                                        if (option === 'this') {
                                            event =  store.indexOf(event) != -1 ? event : store.getById(event.id);
                                            
                                            store.replaceRecord(event ,updatedEvent);
                                            this.setLoading(false);
                                            view.getSelectionModel().select(updatedEvent);
                                        } else {
                                            store.load({refresh: true});
                                        }
                                    },
                                    failure: this.onProxyFail.createDelegate(this, [event], true)
                                };
                                
                                Tine.Calendar.backend.createRecurException(event, false, option == 'future', options);
                                    
                            default:
                                this.loadMask.show();
                                store.load({refresh: true});
                                break;
                        }
                    } catch (e) {
                        Tine.log.error('Tine.Calendar.MainScreenCenterPanel::onUpdateEvent::handle');
                        Tine.log.error(e);
                    }
                } 
            });
        } else {
            this.onUpdateEventAction(event);
        }
    },
    
    onUpdateEventAction: function(event) {
        var panel = this.getCalendarPanel(this.activeView),
            store = panel.getStore(),
            view = panel.getView();
            
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(updatedEvent) {
                if (updatedEvent.isRecurBase()) {
                    store.load({refresh: true});
                } else {
                    event =  store.indexOf(event) != -1 ? event : store.getById(event.id);
                    if(event) store.replaceRecord(event, updatedEvent);
                    else store.add(updatedEvent)
                    
                    this.setLoading(false);
                    // no sm when called from another app
                    if (view && view.calPanel && view.rendered) {
                        view.getSelectionModel().select(updatedEvent);
                    }
                }
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConflicts: 1
        });
    },
    
    onDeleteRecords: function () {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        var containsRecurBase = false;
        var containsRecurInstance = false;
        
        Ext.each(selection, function (event) {
            if(event.ui) event.ui.markDirty();
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
                fn: function () {
                    this.onDeleteRecordsConfirmFail(panel, selection);
                }
            });
            return;
        }
        
        if (selection.length === 1 && (containsRecurBase || containsRecurInstance)) {

            this.deleteMethodWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Delete Event'),
                scope: this,
                height: 170,
                options: [
                    {text: this.app.i18n._('Delete this event only'), name: 'this'},
                    {text: this.app.i18n._('Delete this and all future events'), name: containsRecurBase ? 'all' : 'future'},
                    {text: this.app.i18n._('Delete whole series'), name: 'all'},
                    {text: this.app.i18n._('Delete nothing'), name: 'nothing'}
                ],
                handler: function (option) {
                    try {
                        switch (option) {
                            case 'all':
                            case 'this':
                            case 'future':
                                panel.getTopToolbar().beforeLoad();
                                if (option !== 'this') {
                                    this.loadMask.show();
                                }
                                
                                var options = {
                                    scope: this,
                                    success: function () {
                                        if (option === 'this') {
                                            Ext.each(selection, function (event) {
                                                panel.getStore().remove(event);
                                            });
                                            panel.getTopToolbar().onLoad();
                                        } else {
                                            this.refresh(true);
                                        }
                                        
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
                    
                    } catch (e) {
                        Tine.log.error('Tine.Calendar.MainScreenCenterPanel::onDeleteRecords::handle');
                        Tine.log.error(e);
                    }
                }
            });
            return;
        }
        
        // else
        var i18nQuestion = String.format(this.app.i18n.ngettext('Do you really want to delete this event?', 'Do you really want to delete the {0} selected events?', selection.length), selection.length);
        Ext.MessageBox.confirm(Tine.Tinebase.translation._hidden('Confirm'), i18nQuestion, function (btn) {
            if (btn === 'yes') {
                this.onDeleteRecordsConfirmNonRecur(panel, selection);
            } else {
                this.onDeleteRecordsConfirmFail(panel, selection);
            }
        }, this);
        
    },
    
    onDeleteRecordsConfirmNonRecur: function (panel, selection) {
        panel.getTopToolbar().beforeLoad();
        
        // create a copy of selection so selection changes don't affect this
        var sel = Ext.unique(selection);
                
        var options = {
            scope: this,
            success: function () {
                panel.getTopToolbar().onLoad();
                Ext.each(sel, function (event) {
                    panel.getStore().remove(event);
                });
            },
            failure: function () {
                panel.getTopToolbar().onLoad();
                Ext.MessageBox.alert(Tine.Tinebase.translation._hidden('Failed'), String.format(this.app.i18n.n_('Failed not delete event', 'Failed to delete the {0} events', selection.length), selection.length)); 
            }
        };
        
        Tine.Calendar.backend.deleteRecords(selection, options);
    },
    
    onDeleteRecordsConfirmFail: function (panel, selection) {
        Ext.each(selection, function (event) {
			event.ui.clearDirty();
        });
    },
    
    /**
     * @param {String} action add|edit
     */
    onEditInNewWindow: function (action, defaults, event) {
        if(!event) event = null;

        if (action === 'edit') {
            var panel = this.getCalendarPanel(this.activeView);
            var selection = panel.getSelectionModel().getSelectedEvents();
            if (Ext.isArray(selection) && selection.length === 1) {
                event = selection[0];
                if (! event || event.dirty) {
                    return;
                }
            }
        }

        if (! event) {
            event = new Tine.Calendar.Model.Event(Ext.apply(Tine.Calendar.Model.Event.getDefaultData(), defaults), 0);
            if (defaults && Ext.isDate(defaults.dtStart)) {
                event.set('dtstart', defaults.dtStart);
                event.set('dtend', defaults.dtStart.add(Date.HOUR, 1));
            }
        }

        Tine.Calendar.EventEditDialog.openWindow({
            record: Ext.util.JSON.encode(event.data),
            recordId: event.data.id,
            listeners: {
                scope: this,
                update: function (eventJson) {
                    var updatedEvent = Tine.Calendar.backend.recordReader({responseText: eventJson});
                    updatedEvent.dirty = true;
                    updatedEvent.modified = {};
                    event.phantom = (action === 'edit');
                    var panel = this.getCalendarPanel(this.activeView);
                    var store = panel.getStore();
                    event = store.getById(event.id);
                    if (event) store.replaceRecord(event, updatedEvent);
                    else store.add(updatedEvent);
                    
                    this.onUpdateEvent(updatedEvent, false, action);
                }
            }
        });
    },
    
    onKeyDown: function (e) {
        if (e.ctrlKey) {
            switch (e.getKey()) 
            {
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
        } else if (e.getKey() === e.DELETE) {
        	if (! this.action_deleteRecord.isDisabled()) {
                this.onDeleteRecords.call(this);
            }
        }
    },
    
    onPrint: function() {
        var panel = this.getCalendarPanel(this.activeView),
            view = panel ? panel.getView() : null;
            
        if (view && Ext.isFunction(view.print)) {
            view.print();
        } else {
            Ext.Msg.alert(this.app.i18n._('Could not Print'), this.app.i18n._('Sorry, your current view does not support printing.'));
        }
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function (store, options) {
        options.params = options.params || {};
        
        // define a transaction
        this.lastStoreTransactionId = options.transactionId = Ext.id();
        
        // allways start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [];
        
        if (! options.refresh && this.rendered) {
            // defer to have the loadMask centered in case of rendering actions
            this.loadMask.show.defer(50, this.loadMask);
        }
        
        // note, we can't use the 'normal' plugin approach here, cause we have to deal with n stores
        //var calendarSelectionPlugin = this.app.getMainScreen().getWestPanel().getContainerTreePanel().getFilterPlugin();
        //calendarSelectionPlugin.onBeforeLoad.call(calendarSelectionPlugin, store, options);
        
        this.filterToolbar.onBeforeLoad.call(this.filterToolbar, store, options);
        
        // add period filter as last filter to not conflict with first OR filter
        if (! options.noPeriodFilter) {
            options.params.filter.push({field: 'period', operator: 'within', value: this.getCalendarPanel(this.activeView).getView().getPeriod() });
        }
    },
    
    /**
     * fence against loading of wrong data set
     */
    onStoreBeforeLoadRecords: function(o, options, success) {
        return this.lastStoreTransactionId === options.transactionId;
    },
    
    /**
     * called when store loaded data
     */
    onStoreLoad: function (store, options) {
        if (this.rendered) {
            this.loadMask.hide();
        }
        
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 1000);
        }
        
        // check if store is current store
        if (store !== this.getCalendarPanel(this.activeView).getStore()) {
            Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::onStoreLoad view is not active anymore');
            return;
        }
        
        // update filtertoolbar
        this.filterToolbar.setValue(store.proxy.jsonReader.jsonData.filter);
        
        // update tree
        Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getWestPanel().getContainerTreePanel().getFilterPlugin().setValue(store.proxy.jsonReader.jsonData.filter);
    },
    
    /**
     * on store load exception
     * 
     * @param {Tine.Tinebase.data.RecordProxy} proxy
     * @param {String} type
     * @param {Object} error
     * @param {Object} options
     */
    onStoreLoadException: function(proxy, type, error, options) {
        
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 5000);
        }
        
        this.setLoading(false);
        this.loadMask.hide();
        
        if (! options.autoRefresh) {
            this.onProxyFail(error);
        }
    },
    
    onProxyFail: function(error, event) {
        this.setLoading(false);
        if(this.loadMask) this.loadMask.hide();
        
        if (error.code == 901) {
           
            // resort fbInfo to combine all events of a attender
            var busyAttendee = [];
            var conflictEvents = {};
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(event.get('attendee'));
             
            Ext.each(error.freebusyinfo, function(fbinfo) {
                attendeeStore.each(function(a) {
                    if (a.get('user_type') == fbinfo.user_type && a.getUserId() == fbinfo.user_id) {
                        if (busyAttendee.indexOf(a) < 0) {
                            busyAttendee.push(a);
                            conflictEvents[a.id] = [];
                        }
                        conflictEvents[a.id].push(fbinfo);
                    }
                });
            }, this);
            
            // generate html for each busy attender
            var busyAttendeeHTML = '';
            Ext.each(busyAttendee, function(busyAttender) {
                // TODO refactore name handling of attendee
                //      -> attender model needs knowlege of how to get names!
                //var attenderName = a.getName();
                var attenderName = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, busyAttender.get('user_id'), false, busyAttender);
                busyAttendeeHTML += '<div class="cal-conflict-attendername">' + attenderName + '</div>';
                
                var eventInfos = [];
                Ext.each(conflictEvents[busyAttender.id], function(fbInfo) {
                    var format = 'H:i';
                    var dateFormat = Ext.form.DateField.prototype.format;
                    if (event.get('dtstart').format(dateFormat) != event.get('dtend').format(dateFormat) ||
                        Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(dateFormat) != Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(dateFormat))
                    {
                        format = dateFormat + ' ' + format;
                    }
                    
                    var eventInfo = Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(format) + ' - ' + Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(format);
                    if (fbInfo.event && fbInfo.event.summary) {
                        eventInfo += ' : ' + fbInfo.event.summary;
                    }
                    eventInfos.push(eventInfo);
                }, this);
                busyAttendeeHTML += '<div class="cal-conflict-eventinfos">' + eventInfos.join(', <br />') + '</div>';
                
            });
            
            this.conflictConfirmWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                modal: true,
                allowCancel: false,
                height: 180 + 15*error.freebusyinfo.length,
                title: this.app.i18n._('Scheduling Conflict'),
                questionText: '<div class = "cal-conflict-heading">' +
                                   this.app.i18n._('The following attendee are busy at the requested time:') + 
                               '</div>' +
                               busyAttendeeHTML,
                options: [
                    {text: this.app.i18n._('Ignore Conflict'), name: 'ignore'},
                    {text: this.app.i18n._('Edit Event'), name: 'edit', checked: true},
                    {text: this.app.i18n._('Cancel this action'), name: 'cancel'}
                ],
                scope: this,
                handler: function(option) {
                    var panel = this.getCalendarPanel(this.activeView),
                        store = panel.getStore();

                    switch (option) {
                        case 'ignore':
                            this.onAddEvent(event, false, true);
                            this.conflictConfirmWin.close();
                            break;
                        
                        case 'edit':
                            
                            var presentationMatch = this.activeView.match(this.presentationRe),
                                presentation = Ext.isArray(presentationMatch) ? presentationMatch[0] : null;
                            
                            if (presentation != 'Grid') {
                                var view = panel.getView();
                                view.getSelectionModel().select(event);
                                // mark event as not dirty to allow edit dlg
                                event.dirty = false;
                                view.fireEvent('dblclick', view, event);
                            } else {
                                // add or edit?
                                this.onEditInNewWindow(null, null, event);
                            }
                            
                            this.conflictConfirmWin.close();
                            break;                            
                        case 'cancel':
                        default:
                            this.conflictConfirmWin.close();
                            this.loadMask.show();
                            store.load({refresh: true});
                            break;
                    }
                }
            });
            
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(error);
        }
    },
    
    refresh: function (refresh) {
        Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::refresh(' + refresh + ')');
        var panel = this.getCalendarPanel(this.activeView);
        panel.getStore().load({
            refresh: refresh
        });
        
        // clear favorites
        Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getWestPanel().getFavoritesPanel().getSelectionModel().clearSelections();
    },
    
    setLoading: function(bool) {
        var panel = this.getCalendarPanel(this.activeView),
            tbar = panel.getTopToolbar();
            
        if (tbar && tbar.loading) {
            tbar.loading[bool ? 'disable' : 'enable']();
        }
    },
    
    setResponseStatus: function(event, status) {
        var myAttenderRecord = event.getMyAttenderRecord();
        if (myAttenderRecord) {
            myAttenderRecord.set('status', status);
            event.dirty = true;
            event.modified = {};
            
            var panel = this.getCalendarPanel(this.activeView);
            var store = panel.getStore();
            
            store.replaceRecord(event, event);
            
            this.onUpdateEvent(event);
        }
    },
    
    updateEventActions: function () {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        this.actionUpdater.updateActions(selection);
        if (this.detailsPanel) {
            this.detailsPanel.onDetailsUpdate(panel.getSelectionModel());
        }
    },
    
    updateView: function (which) {
        Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::updateView(' + which + ')');
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
    getCalendarPanel: function (which) {
        var whichParts = this.getViewParts(which);
        which = whichParts.toString();
        
        if (! this.calendarPanels[which]) {
            Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::getCalendarPanel creating new calender panel for view ' + which);
            
            var store = new Ext.data.JsonStore({
                //autoLoad: true,
                id: 'id',
                fields: Tine.Calendar.Model.Event,
                proxy: Tine.Calendar.backend,
                reader: new Ext.data.JsonReader({}), //Tine.Calendar.backend.getReader(),
                listeners: {
                    scope: this,
                    'beforeload': this.onStoreBeforeload,
                    'beforeloadrecords' : this.onStoreBeforeLoadRecords,
                    'load': this.onStoreLoad,
                    'loadexception': this.onStoreLoadException
                },
                replaceRecord: function(o, n) {
                    var idx = this.indexOf(o);
                    this.remove(o);
                    this.insert(idx, n);
                }
            });
            
            var tbar = new Tine.Calendar.PagingToolbar({
                view: whichParts.period,
                store: store,
                dtStart: this.startDate,
                listeners: {
                    scope: this,
                    // NOTE: only render the button once for the toolbars
                    //       the buttons will be moved on chageView later
                    render: function (tbar) {
                        for (var i = 0; i < this.changeViewActions.length; i += 1) {
                            if (! this.changeViewActions[i].rendered) {
                                tbar.addButton(this.changeViewActions[i]);
                            }
                        }
                    }
                }
            });
            
            tbar.on('change', this.updateView.createDelegate(this, [which]), this, {buffer: 200});
            tbar.on('refresh', this.refresh.createDelegate(this, [true]), this, {buffer: 200});
            
            if (whichParts.presentation.match(/sheet/i)) {
                var view;
                switch (which) {
                    case 'daySheet':
                        view = new Tine.Calendar.DaysView({
                            startDate: tbar.getPeriod().from,
                            numOfDays: 1
                        });
                        break;
                    case 'monthSheet':
                        view = new Tine.Calendar.MonthView({
                            period: tbar.getPeriod()
                        });
                        break;
                    default:
                    case 'weekSheet':
                        view = new Tine.Calendar.DaysView({
                            startDate: tbar.getPeriod().from,
                            numOfDays: 7
                        });
                        break;
                }
                
                view.on('changeView', this.changeView, this);
                view.on('changePeriod', function (period) {
                    this.startDate = period.from;
                    this.startDates[which] = this.startDate.clone();
                    this.updateMiniCal();
                }, this);
                
                // quick add/update actions
                view.on('addEvent', this.onAddEvent, this);
                view.on('updateEvent', this.onUpdateEvent, this);
            
                this.calendarPanels[which] = new Tine.Calendar.CalendarPanel({
                    tbar: tbar,
                    store: store,
                    view: view
                });
            } else if (whichParts.presentation.match(/grid/i)) {
                this.calendarPanels[which] = new Tine.Calendar.GridView({
                    tbar: tbar,
                    store: store
                });
            }
            
            this.calendarPanels[which].on('dblclick', this.onEditInNewWindow.createDelegate(this, ["edit"]));
            this.calendarPanels[which].on('contextmenu', this.onContextMenu, this);
            this.calendarPanels[which].getSelectionModel().on('selectionchange', this.updateEventActions, this);
            this.calendarPanels[which].on('keydown', this.onKeyDown, this);
            
            this.calendarPanels[which].on('render', function () {
                var defaultFavorite = Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(this.app.appName);
                var favoritesPanel  = this.app.getMainScreen().getWestPanel().getFavoritesPanel();
                // NOTE: this perfoms the initial load!
                favoritesPanel.selectFilter(defaultFavorite);
            }, this);
            
            this.calendarPanels[which].relayEvents(this, ['show', 'beforehide']);
        }
        
        return this.calendarPanels[which];
    },
    
    updateMiniCal: function () {
        var miniCal = Ext.getCmp('cal-mainscreen-minical');
        var weekNumbers = null;
        var period = this.getCalendarPanel(this.activeView).getView().getPeriod();
        
        switch (this.activeView) 
        {
        case 'week' :
            weekNumbers = [period.from.add(Date.DAY, 1).getWeekOfYear()];
            break;
        case 'month' :
            weekNumbers = [];
            var startWeek = period.from.add(Date.DAY, 1).getWeekOfYear();
            var numWeeks = Math.round((period.until.getTime() - period.from.getTime()) / Date.msWEEK);
            for (var i = 0; i < numWeeks; i += 1) {
                weekNumbers.push(startWeek + i);
            }
        	break;
        }
        miniCal.update(this.startDate, true, weekNumbers);
    }
});
