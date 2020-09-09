/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * i18n._('Loading events, please wait...')
     */
    loadMaskText: 'Loading events, please wait...',
    
    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,

    /**
     * @cfg {Boolean} initialLoadAfterRender
     */
    initialLoadAfterRender: true,

    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,
    
    /**
     * add records from other applications using the split add button
     * - activated by default
     * 
     * @type Bool
     * @property splitAddButton
     */
    splitAddButton: true,
    
    /**
     * default print mode
     * 
     * @type String {sheet|grid}
     * @property defaultPrintMode
     */
    defaultPrintMode: 'sheet',
    
    periodRe: /^(day|week|month|year|custom)/i,
    presentationRe: /(sheet|grid|timeline)$/i,
    
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
        this.autoRefreshTask = new Ext.util.DelayedTask(this.refresh, this, [{
            refresh: true,
            autoRefresh: true
        }]);
        
        Tine.Calendar.MainScreenCenterPanel.superclass.initComponent.call(this);
    },
    
    initActions: function () {
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.i18nEditActionText ? this.app.i18n._hidden(this.i18nEditActionText) : String.format(i18n._hidden('Edit {0}'), this.i18nRecordName),
            disabled: true,
            handler: this.onEditInNewWindow.createDelegate(this, ["edit"], 0),
            iconCls: 'action_edit'
        });
        
        this.action_addInNewWindow = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(i18n._hidden('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow.createDelegate(this, ["add"], 0),
            iconCls: 'action_add'
        });
        
        this.action_cut = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: this.app.i18n._('Cut event'),
            handler: this.onCutEvent.createDelegate(this),
            iconCls: 'action_cut'
        });

        this.action_copy_to = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: this.app.i18n._('Copy Event to clipboard'),
            handler: this.onCopyToEvent.createDelegate(this),
            iconCls: 'action_copy'
        });
        
        this.action_cancelPasting = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: this.app.i18n._('Stop cut / copy & paste'),
            handler: this.onCutCancelEvent.createDelegate(this),
            iconCls: 'action_cut_break'
        });
        
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? i18nDeleteActionText[0] : String.format(i18n.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? i18nDeleteActionText[1] : String.format(i18n.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : i18n,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(i18n.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.actions_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Page'),
            handler: this.onPrint.createDelegate(this, []),
            iconCls:'action_print',
            scope: this,
            listeners: {
                arrowclick: this.onPrintMenuClick.createDelegate(this)
            },
            menu:{
                items:[{
                    text: this.app.i18n._('Loose-Leaf'),
                    iconCls: 'cal-print-loose-leaf',
                    handler: this.onPrint.createDelegate(this, ['grid'])
                }, {
                    text: this.app.i18n._('Sheet'),
                    iconCls: 'cal-week-view',
                    handler: this.onPrint.createDelegate(this, ['sheet']),
                    disabled: Ext.isNewIE
                }]
            }
        });
        
        this.showSheetView = new Ext.Button({
            pressed: this.isActiveView('Sheet'),
            scale: 'medium',
            minWidth: 60,
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
            pressed: this.isActiveView('Grid'),
            scale: 'medium',
            minWidth: 60,
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

        this.showTimelineView = new Ext.Button({
            pressed: this.isActiveView('Timeline'),
            scale: 'medium',
            minWidth: 60,
            rowspan: 2,
            iconAlign: 'top',
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Timeline'),
            handler: this.changeView.createDelegate(this, ["timeline"]),
            iconCls:'cal-timeline-view-type',
            xtype: 'tbbtnlockedtoggle',
            toggleGroup: 'Calendar_Toolbar_tgViewTypes',
            scope: this
        });

        this.showDayView = new Ext.Toolbar.Button({
            pressed: this.isActiveView('Day'),
            text: this.app.i18n._('Day'),
            iconCls: 'cal-day-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["day"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showWeekView = new Ext.Toolbar.Button({
            pressed: this.isActiveView('Week'),
            text: this.app.i18n._('Week'),
            iconCls: 'cal-week-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["week"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showMonthView = new Ext.Toolbar.Button({
            pressed: this.isActiveView('Month'),
            text: this.app.i18n._('Month'),
            iconCls: 'cal-month-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["month"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showYearView = new Ext.Toolbar.Button({
            pressed: String(this.activeView).match(/^year/i),
            hidden: !this.app.featureEnabled('featureYearView'),
            text: this.app.i18n._('Year'),
            iconCls: 'cal-year-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["year"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews',
            checkState: (mainScreen, btn) => {
                _.defer(() => {btn.setVisible(this.app.featureEnabled('featureYearView'));});
            }
        });
        this.showCustomView = new Ext.Toolbar.Button({
            pressed: String(this.activeView).match(/^custom/i),
            tooltip: this.app.i18n._('Custom Selection'),
            text: '&nbsp;',
            iconCls: 'cal-customperiod-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["custom"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews',
            hidden: !this.activeView.match(/grid/i),
            checkState: (mainScreen, btn) => {
                let isGridView = mainScreen.activeView.match(/grid/i);
                btn.setVisible(isGridView);

                if (!isGridView && btn.pressed) {
                    _.defer(() => {this.showWeekView.toggle(true);});
                }
            }
        });
        this.toggleFullScreen = new Ext.Toolbar.Button({
            text: '\u2197',
            scope: this,
            handler: function() {
                if (this.ownerCt.ref == 'tineViewportMaincardpanel') {
                    Tine.Tinebase.viewport.tineViewportMaincardpanel.remove(this, false);
                    Tine.Tinebase.viewport.tineViewportMaincardpanel.layout.setActiveItem(Tine.Tinebase.viewport.tineViewportMaincardpanel.layout.lastActiveItem);
                    this.originalOwner.add(this);
                    this.originalOwner.layout.setActiveItem(this);
                    this.toggleFullScreen.setText('\u2197');
                    this.southPanel.expand();
                } else {
                    this.originalOwner = this.ownerCt;
                    this.originalOwner.remove(this, false);
                    Tine.Tinebase.viewport.tineViewportMaincardpanel.layout.lastActiveItem = Tine.Tinebase.viewport.tineViewportMaincardpanel.layout.activeItem;
                    Tine.Tinebase.viewport.tineViewportMaincardpanel.add(this);
                    Tine.Tinebase.viewport.tineViewportMaincardpanel.layout.setActiveItem(this);
                    this.toggleFullScreen.setText('\u2199');
                    this.southPanel.collapse();
                }
            }
        });
        
       this.action_import = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.app.i18n._('Import Events'),
            disabled: false,
            handler: this.onImport,
            minWidth: 60,
            iconCls: 'action_import',
            scope: this,
            allowMultiple: true
        });

        this.action_export = Tine.widgets.exportAction.getExportButton(
            Tine.Calendar.Model.Event, {
                getExportOptions: (function() {
                    return {
                        filter: this.getAllFilterData({
                            noPeriodFilter: false
                        })
                    }
                }).createDelegate(this)
        }, Tine.widgets.exportAction.SCOPE_MULTI);
        // FIXME: how can this be null? is it ok??
        if (this.action_export) {
            this.action_export.setDisabled(false);
            this.action_export.setText(this.action_export.initialConfig.pluralText);
        }

        this.changeViewActions = [
            this.showDayView,
            this.showWeekView,
            this.showMonthView,
            this.showYearView,
            this.showCustomView
        ];

        this.changeViewActions.push(this.toggleFullScreen);

        this.recordActions = [
            this.action_editInNewWindow,
            this.action_deleteRecord
        ];
        
        this.actionUpdater = new  Tine.widgets.ActionUpdater({
            actions: this.recordActions
        });
    },

    /**
     * get current view type (Grid/Day/Week/...)
     *
     * @param {String} view
     * @returns {Boolean}
     */
    isActiveView: function(view)
    {
        var re = new RegExp(String(view).toLowerCase(), 'i');
        return String(this.activeView).match(re);
    },
    
    /**
     * returns the paste action
     * 
     * @param {Date} datetime
     * @param {Tine.Calendar.Model.Event} event
     */
    getPasteAction: function(datetime, event) {
        var shortSummary = Ext.util.Format.ellipsis(event.get('summary'), 15);
        return new Ext.Action({
            requiredGrant: 'addGrant',
            text: String.format(this.app.i18n._('Paste event "{0}"'), shortSummary),
            handler: this.onPasteEvent.createDelegate(this, [datetime]),
            iconCls: 'action_paste'
        });
    },
        
    getActionToolbar: Tine.widgets.grid.GridPanel.prototype.getActionToolbar,
    onActionToolbarResize: Tine.widgets.grid.GridPanel.prototype.onActionToolbarResize,
    
    getActionToolbarItems: function() {
        var items = [this.action_import];
        if (this.action_export) {
            items = items.concat(this.action_export);
        }
        return [{
            xtype: 'buttongroup',
            columns: 1,
            rows: 2,
            frame: false,
            items: items
        }, {xtype: 'tbseparator'}, {
            xtype: 'buttongroup',
            frame: false,
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Calendar-MainScreenPanel-ViewBtnGrp'
            }],
            items: [
                this.showSheetView,
                this.showTimelineView,
                this.showGridView
            ]
        }];
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
                ref: 'southPanel',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
                
            });
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
     * import events
     * 
     * @param {Button} btn 
     */
    onImport: function(btn) {
        var popupWindow = Tine.Calendar.ImportDialog.openWindow({
            appName: 'Calendar',
            modelName: 'Event',
            defaultImportContainer: this.app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer('defaultContainer'),
            
            ignoreConflicts: true,
            doTryRun: false,
            
            
            // update grid after import
            listeners: {
                scope: this,
                'finish': function() {
                    this.refresh();
                }
            }
        });
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Calendar.MainScreenCenterPanel.superclass.onRender.apply(this, arguments);

        this.loadMask = new Ext.LoadMask(this.body, {msg: this.loadMaskText});

        var defaultFavorite = Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(this.app.appName, this.recordClass.prototype.modelName);

        if (this.initialLoadAfterRender) {
            if (defaultFavorite) {
                this.selectFavorite(defaultFavorite);
            } else {
                this.refresh();
            }
        }
    },

    selectFavorite: function(favorite) {
        var favorite = favorite || Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(this.app.appName, this.recordClass.prototype.modelName),
            favoritesPanel  = this.app.getMainScreen().getWestPanel().getFavoritesPanel();

        favoritesPanel.selectFilter(favorite);
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

        this.movePeriodBtns(panel.tbar);
        this['show' + Ext.util.Format.capitalize(viewParts.period) +  'View'].toggle(true);
        this['show' + Ext.util.Format.capitalize(viewParts.presentation) +  'View'].toggle(true);
        
        // update actions
        this.updateEventActions();
        
        // update data
        // NOTE: monthView periods differ for views.
        //  - sheetView begins with end of last month and ends with beginning of next month
        //  - timelineView starts with the first and ends with the last
        //    BUT startDate sticks to first of month!
        // monthSheet is a bitch!
        panel.getView().updatePeriod({from: this.startDate}, currentPeriod);
        panel.getStore().load({});
        
        this.fireEvent('changeview', this, view);
    },

    movePeriodBtns: function(tgt) {
        var rightRow = Ext.get(Ext.DomQuery.selectNode('tr[class=x-toolbar-right-row]', tgt.dom));

        for (var i = this.changeViewActions.length - 1; i >= 0; i--) {
            if (_.isFunction(this.changeViewActions[i].checkState)) {
                this.changeViewActions[i].checkState(this, this.changeViewActions[i]);
            }
            rightRow.insertFirst(this.changeViewActions[i].getEl().parent().dom);
        }
    },

    /**
     * returns all filter data for current view
     */
    getAllFilterData: function (options) {
        var store = this.getCalendarPanel(this.activeView).getStore();
        
        options = Ext.apply({
            refresh: true, // ommit loadMask
            noPeriodFilter: true
        }, options || {});
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
    
    /**
     * onContextMenu
     * 
     * @param {Event} e
     */
    onContextMenu: function (e) {
        e.stopEvent();

        var view = this.getCalendarPanel(this.activeView).getView();
        var event = view.getTargetEvent(e);
        var datetime = view.getTargetDateTime(e);
        
        if (event && event.id.match(/new-ext-gen/) ) {
            // new event, no ctx menu possible atm
            // @see 0008948: deactivate context menu on new events
            return;
        }
        
        var addAction, responseAction, copyAction, eventStatusAction;

        if (datetime || event) {
            var dtStart = datetime || event.get('dtstart').clone();
            if (dtStart.format('H:i') === '00:00') {
                dtStart = dtStart.add(Date.HOUR, 9);
            }
            
            addAction = {
                text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(i18n._hidden('Add {0}'), this.i18nRecordName),
                handler: this.onEditInNewWindow.createDelegate(this, ["add", null, null, {dtStart: dtStart, is_all_day_event: datetime && datetime.is_all_day_event}]),
                iconCls: 'action_add'
            };
            
            // assemble event actions
            if (event) {
                responseAction = this.getResponseAction(event);
                copyAction = this.getCopyAction(event);
                eventStatusAction = this.getEventStatusAction(event);
            }
        } else {
            addAction = this.action_addInNewWindow;
        }
        
        if (event) {
            view.getSelectionModel().select(event, e, e.ctrlKey);
        } else {
            view.getSelectionModel().clearSelections();
        }

        var menuitems = this.recordActions.concat(addAction, copyAction || [], eventStatusAction || [], '-', responseAction || []);
        if (event && event.get('poll_id') && event.get('editGrant')) {
            require('./PollSetDefiniteEventAction');
            menuitems = menuitems.concat(['-',{
                text: this.app.i18n._('Set as definite event'),
                iconCls: 'cal-polls-set-definite-action',
                scope: this,
                handler: function() {
                    var me = this,
                        ns = Tine.Calendar.eventActions.setDefiniteEventAction;
                    ns.confirm()
                        .then(function() {
                            me.loadMask.show();
                            return Tine.Calendar.setDefinitePollEvent(event.data);
                        })
                        .then(function() {
                            me.getStore().load({refresh: true});
                        })
                        .catch(function(error) {
                            me.loadMask.hide();
                        });
                }
            }]);
        }
        if (event) {
            this.action_copy_to.setDisabled(event.isRecurInstance() || event.isRecurException() || event.isRecurBase());
            menuitems = menuitems.concat(['-', this.action_cut, this.action_copy_to, '-']);
        } else if (Tine.Tinebase.data.Clipboard.has('Calendar', 'Event')) {
            menuitems = menuitems.concat(['-', this.getPasteAction(datetime, Tine.Tinebase.data.Clipboard.pull('Calendar', 'Event', true)), this.action_cancelPasting, '-']);
        }

        var ctxMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Calendar-MainScreenPanel-ContextMenu',
                config: {
                    event: event,
                    datetime: datetime
                }
            },
            // to allow gridpanel hooks (like email compose)
            {
                ptype: 'ux.itemregistry',
                key:   'Calendar-Event-GridPanel-ContextMenu'
            }, {
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: menuitems
        });

        // run action updater
        ctxMenu.showAt(e.getXY());
    },
    
    /**
     * get response action
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @return {Object}
     */
    getResponseAction: function(event) {
        var statusStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus'),
            myAttenderRecord = event.getMyAttenderRecord(),
            myAttenderStatus = myAttenderRecord ? myAttenderRecord.get('status') : null,
            myAttenderStatusRecord = statusStore.getById(myAttenderStatus),
            responseAction = null;
        
        if (myAttenderRecord) {
            responseAction = {
                text: this.app.i18n._('Set my response'),
                icon: myAttenderStatusRecord ? myAttenderStatusRecord.get('icon') : false,
                menu: []
            };
            
            statusStore.each(function(status) {
                var isCurrent = myAttenderRecord.get('status') === status.id;
                
                // NOTE: we can't use checked items here as we use icons already
                responseAction.menu.push({
                    text: status.get('i18nValue'),
                    handler: this.setResponseStatus.createDelegate(this, [event, status.id]),
                    icon: status.get('icon'),
                    disabled: myAttenderRecord.get('status') === status.id
                });
            }, this);
        }
        
        return responseAction;
    },

    /**
     * get copy action
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @return {Object}
     */
    getCopyAction: function(event) {
        var copyAction = {
            text: String.format(this.app.i18n._('Copy {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow.createDelegate(this, ["copy", event]),
            iconCls: 'action_editcopy',
            // TODO allow to copy recurring events / exceptions
            disabled: event.isRecurInstance() || event.isRecurException() || event.isRecurBase()
        };
        
        return copyAction
    },

    /**
     * get eventStatusAction action
     *
     * @param {Tine.Calendar.Model.Event} event
     * @return {Object}
     */
    getEventStatusAction: function(event) {
        let statusStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'eventStatus');
        let statusRecord = statusStore.getById(event.get('status'));
        let eventStatusAction = {
            text: this.app.i18n._('Set event status'),
            icon: statusRecord ? statusRecord.get('icon') : false,
            menu: []
        };

        statusStore.each(function(status) {
            let isCurrent = statusRecord && statusRecord.id === status.id;

            // NOTE: we can't use checked items here as we use icons already
            eventStatusAction.menu.push({
                text: status.get('i18nValue'),
                handler: this.setEventStatus.createDelegate(this, [event, status.id]),
                icon: status.get('icon'),
                disabled: isCurrent
            });
        }, this);
        
        return eventStatusAction;
    },
    
    checkPastEvent: function(event, checkBusyConflicts, actionType, oldEvent) {
        var start = event.get('dtstart').getTime();
        var morning = new Date().clearTime().getTime();

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
        
        if(start < morning) {
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
                                if (actionType == 'update') this.onUpdateEvent(event, true, oldEvent);
                                else this.onAddEvent(event, checkBusyConflicts, true);
                                break;
                            case 'no':
                            default:
                                try {
                                    var panel = this.getCalendarPanel(this.activeView);
                                    if(panel) {
                                        var store = this.getStore(),
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
                                    if (view && view.rendered) {
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
            if (actionType == 'update') this.onUpdateEvent(event, true, oldEvent);
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
            if (this.loadMask) {
                this.loadMask.show();
            }
        }
        
        var panel = this.getCalendarPanel(this.activeView),
            store = this.getStore();
        
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(createdEvent) {
                this.congruenceFilterCheck(event, createdEvent);
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConflicts: checkBusyConflicts === false ? 0 : 1
        });
    },
    
    onUpdateEvent: function(event, pastChecked, oldEvent) {
        this.setLoading(true);
        
        if(!pastChecked) {
            this.checkPastEvent(event, null, 'update', oldEvent);
            return;
        }
        
        if (event.isRecurBase() && this.loadMask) {
           this.loadMask.show();
        }
        
        if (event.id && (event.isRecurInstance() || event.isRecurException() || (event.isRecurBase() && ! event.get('rrule').newrule))) {
            
            var options = [];
            

            options.push({text: this.app.i18n._('Update this event only'), name: 'this'});

            // if
            options.push({text: this.app.i18n._('Update this and all future events'), name: (event.isRecurBase() && ! event.get('rrule').newrule) ? 'series' : 'future'});
            options.push({text: this.app.i18n._('Update whole series'), name: 'series'});
            // endif

            options.push({text: this.app.i18n._('Update nothing'), name: 'cancel'});
            
            Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Update Event'),
                height: 170,
                scope: this,
                options: options,
                handler: function(option) {
                    var store = this.getStore();
                    
                    switch (option) {
                        case 'series':
                            if (this.loadMask) {
                                this.loadMask.show();
                            }
                            
                            var options = {
                                scope: this,
                                success: function(updatedEvent) {
                                    this.congruenceFilterCheck(event, updatedEvent);
                                }
                            };
                            options.failure = this.onProxyFail.createDelegate(this, [event, Tine.Calendar.backend.updateRecurSeries.createDelegate(Tine.Calendar.backend, [event, false, options])], true);
                            
                            if (event.isRecurException()) {
                                Tine.Calendar.backend.saveRecord(event, options, {range: 'ALL', checkBusyConflicts: true});
                            } else {
                                Tine.Calendar.backend.updateRecurSeries(event, true, options);
                            }
                            break;
                            
                        case 'this':
                        case 'future':
                            var options = {
                                scope: this,
                                success: function(updatedEvent) {
                                    this.congruenceFilterCheck(event, updatedEvent);
                                },
                                failure: this.onProxyFail.createDelegate(this, [event], true)
                            };
                            
                            
                            if (event.isRecurException()) {
                                var range = (option === 'this' ? 'THIS' : 'THISANDFUTURE');
                                options.failure = this.onProxyFail.createDelegate(this, [event, Tine.Calendar.backend.saveRecord.createDelegate(Tine.Calendar.backend, [event, options, {range: range, checkBusyConflicts: false}])], true);
                                Tine.Calendar.backend.saveRecord(event, options, {range: range, checkBusyConflicts: true});
                            } else {
                                options.failure = this.onProxyFail.createDelegate(this, [event, Tine.Calendar.backend.createRecurException.createDelegate(Tine.Calendar.backend, [event, false, option == 'future', false, options])], true);
                                Tine.Calendar.backend.createRecurException(event, false, option === 'future', true, options);
                            }
                            break;
                            
                        default:
                            if(this.loadMask) { //no loadMask called from another app
                                this.loadMask.show();
                                store.load({refresh: true});
                            }
                            break;
                    }

                } 
            });
        } else {
            this.onUpdateEventAction(event);
        }
    },
    
    onUpdateEventAction: function(event) {
        var panel = this.getCalendarPanel(this.activeView),
            store = this.getStore(),
            view = panel.getView();
            
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(updatedEvent) {
                this.congruenceFilterCheck(event, updatedEvent);
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConflicts: 1
        });
    },

    /**
     * checks, if the last filter still matches after update
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Tine.Calendar.Model.Event} updatedEvent
     */
    congruenceFilterCheck: function(event, updatedEvent) {
        var filterData = this.getAllFilterData(),
            panel = this.getCalendarPanel(this.activeView),
            store = this.getStore(),
            view = panel.getView(),
            isSelected = panel.getSelectionModel().isSelected(event),
            me = this,
            promise = Promise.resolve();

        if (updatedEvent.ui) {
            updatedEvent.ui.markDirty();
        }

        store.replaceRecord(event, updatedEvent);

        if (isSelected) {
            panel.getSelectionModel().select(updatedEvent);
        }

        if (! event.inPeriod(view.getPeriod())) {
            view.updatePeriod({from: event.get('dtstart')});
            promise = store.promiseLoad({});
        } else if (event.isRecurBase()
            || updatedEvent.isRecurBase()
            || updatedEvent.isRecurException() // NOTE: we also need to refresh for 'this' as otherwise baseevent is not refreshed -> concurrency failures
            || event.hasPoll()
            || updatedEvent.hasPoll()) {
            promise = store.promiseLoad({refresh: true});
        }

        promise.then(function() {
            me.setLoading(true);

            filterData[0].filters[0].filters.push({field: 'id', operator: 'in', value: [ updatedEvent.get('id') ]});
            filterData.push({field: 'period', operator: 'within', value: me.getCalendarPanel(me.activeView).getView().getPeriod()});

            Tine.Calendar.searchEvents(filterData, {}, /* fixed calendars */ true, function(r) {
                if (updatedEvent.ui) {
                    updatedEvent.ui.clearDirty();
                }
                if(r.totalcount == 0) {
                    var renderedEvent = me.getStore().getById(updatedEvent.id);
                    if (! renderedEvent) {
                        me.getStore().add(updatedEvent);
                        renderedEvent = updatedEvent;
                    }
                    if (renderedEvent.ui) {
                        renderedEvent.ui.markOutOfFilter();
                        renderedEvent.ui.clearDirty();
                    }
                }

                me.setLoading(false);
            }, me);
        });
    },
    
    onDeleteRecords: function () {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        var containsRecurBase = false,
            containsRecurInstance = false,
            containsRecurException = false;
        
        Ext.each(selection, function (event) {
            if(event.ui) event.ui.markDirty();
            if (event.isRecurInstance()) {
                containsRecurInstance = true;
            }
            if (event.isRecurBase()) {
                containsRecurBase = true;
            }
            if (event.isRecurException()) {
                containsRecurException = true;
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
        
        if (selection.length === 1 && (containsRecurBase || containsRecurInstance || containsRecurException)) {
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
                                        }
                                        this.refresh(true);
                                    }
                                };
                                
                                if (containsRecurException) {
                                    var range = (option === 'future') ? 'THISANDFUTURE' : Ext.util.Format.uppercase(option);
                                    Tine.Calendar.backend.deleteRecords([selection[0]], options, {range: range});
                                } else {
                                    if (option === 'all') {
                                        Tine.Calendar.backend.deleteRecurSeries(selection[0], options);
                                    } else {
                                        Tine.Calendar.backend.createRecurException(selection[0], true, option === 'future', false, options);
                                    }
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
        Ext.MessageBox.confirm(i18n._hidden('Confirm'), i18nQuestion, function (btn) {
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
                Ext.MessageBox.alert(i18n._hidden('Failed'), String.format(this.app.i18n.n_('Failed to delete event', 'Failed to delete the {0} events', selection.length), selection.length));
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
     * is called on action cut
     * 
     * @param {} action
     * @param {} event
     */
    onCutEvent: function(action, event) {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        if (Ext.isArray(selection) && selection.length === 1) {
            event = selection[0];
        }
        if (event.ui) {
            event.ui.markDirty();
        }
        Tine.Tinebase.data.Clipboard.push(event);
    },

    /**
     * Is called on copy to clipboard
     *
     * @param action
     * @param event
     */
    onCopyToEvent: function(action, event) {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        if (Ext.isArray(selection) && selection.length === 1) {
            event = selection[0];
        }

        event.isCopy = true;
        event.view = event.view ? event.view : panel.view;

        Tine.Tinebase.data.Clipboard.push(event);
    },
    
    /**
     * is called on cancelling cut & paste
     */
    onCutCancelEvent: function() {
        var store = this.getStore();
        var ids = Tine.Tinebase.data.Clipboard.getIds('Calendar', 'Event');
        
        for (var index = 0; index < ids.length; index++) {
            var record = store.getAt(store.findExact('id', ids[index]));
            if (record && record.ui) {
                record.ui.clearDirty();
            }
        }

        Tine.Tinebase.data.Clipboard.clear('Calendar', 'Event');
    },
    
    /**
     * is called on action paste
     * 
     * @param {Date} datetime
     */
    onPasteEvent: function(datetime) {
        var record = Tine.Tinebase.data.Clipboard.pull('Calendar', 'Event'),
            isCopy = record.isCopy,
            sourceView = record.view,
            sourceRecord = record,
            sourceViewAttendee = sourceView.ownerCt.attendee,
            destinationView = this.getCalendarPanel(this.activeView).getView(),
            destinationViewAttendee = destinationView.ownerCt.attendee;

        if (! record) {
            return;
        }
        
        var dtend   = record.get('dtend'),
            dtstart = record.get('dtstart'),
            eventLength = dtend - dtstart,
            store = this.getStore();

        record.beginEdit();

        if (isCopy !== true) {
            // remove from ui before update
            var oldRecord = store.getAt(store.findExact('id', record.getId()));
            if (oldRecord && oldRecord.hasOwnProperty('ui')) {
                oldRecord.ui.remove();
            }
        } else {
            this.omitCopyTitle = record.hasPoll();
            record = Tine.Calendar.EventEditDialog.prototype.doCopyRecordToReturn(record);

            record.set('editGrant', true);
            record.set('id', '');
            record.view = sourceView;

            // remove attender ids
            Ext.each(record.data.attendee, function(attender) {
                delete attender.id;
            }, this);
        }

        // @TODO move to common function with daysView::notifyDrop parts
        // change attendee in split view
        if (sourceViewAttendee || destinationViewAttendee) {
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(sourceRecord.get('attendee')),
                sourceAttendee = sourceViewAttendee ? Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, sourceViewAttendee) : false,
                destinationAttendee = destinationViewAttendee ? Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, destinationViewAttendee) : false;

            if (destinationViewAttendee && !destinationAttendee) {
                destinationAttendee = new Tine.Calendar.Model.Attender(destinationViewAttendee.data);

                attendeeStore.remove(sourceAttendee);
                attendeeStore.add(destinationAttendee);
                record.view = destinationView;
                
                Tine.Calendar.Model.Attender.getAttendeeStore.getData(attendeeStore, record);
            }
        }


        if (datetime.is_all_day_event) {
            record.set('dtstart', datetime);
            record.set('dtend', datetime.clone().add(Date.DAY, 1).add(Date.SECOND, -1));
            record.set('is_all_day_event', true);
        } else if (datetime.date_only) {
            var adoptedDtStart = datetime.clone();
            adoptedDtStart.setHours(dtstart.getHours());
            adoptedDtStart.setMinutes(dtstart.getMinutes());
            adoptedDtStart.setSeconds(dtstart.getSeconds());

            record.set('dtstart', adoptedDtStart);
            record.set('dtend', new Date(adoptedDtStart.getTime() + eventLength));
        } else {
            record.set('dtstart', datetime);
            record.set('dtend', new Date(datetime.getTime() + eventLength));
        }

        record.endEdit();

        if (isCopy === true) {
            record.isCopy = true;
            Tine.Tinebase.data.Clipboard.push(record);
            if (record.ui) {
                record.ui.clearDirty();
            }

            this.onAddEvent(record);
        } else {
            this.onUpdateEvent(record);
        }
    },
    
    /**
     * open event in new window
     * 
     * @param {String} action  add|edit|copy
     * @param {String} event   edit given event instead of selected event
     * @param {Object} plugins event dlg plugins
     * @param {Object} default properties for new items
     */
    onEditInNewWindow: function (action, event, plugins, defaults) {
        if (! (event && Ext.isFunction(event.beginEdit))) {
            event = null;
        }

        // needed for addToEventPanel
        if (Ext.isObject(action)) {
            action = action.actionType;
        }
        
        if (action === 'edit' && ! event) {
            var panel = this.getCalendarPanel(this.activeView);
            var selection = panel.getSelectionModel().getSelectedEvents();
            if (Ext.isArray(selection) && selection.length === 1) {
                event = selection[0];
            }
        }

        if (action === 'edit' && ! event) {
            return;
        }

        if (! event) {
            event = new Tine.Calendar.Model.Event(Ext.apply(Tine.Calendar.Model.Event.getDefaultData(), defaults), 0);

            if (defaults && Ext.isDate(defaults.dtStart)) {
                event.set('dtstart', defaults.dtStart);
                event.set('dtend', defaults.dtStart.add(Date.MINUTE, Tine.Calendar.Model.Event.getMeta('defaultEventDuration')));
            }
        }
        
        Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::onEditInNewWindow() - Opening event edit dialog with action: ' + action);

        Tine.Calendar.EventEditDialog.openWindow({
            plugins: Ext.isArray(plugins) ? Ext.encode(plugins) : null,
            record: Ext.encode(event.data),
            recordId: event.data.id,
            copyRecord: (action == 'copy'),
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
                    
                    if (event && action !== 'copy') {
                        store.replaceRecord(event, updatedEvent);
                    } else {
                        store.add(updatedEvent);
                    }
                    
                    this.onUpdateEvent(updatedEvent, false, event);
                }
            }
        });
    },
    
    onKeyDown: function (e) {
        // no keys for quickadds etc.
        if (e.getTarget('input') || e.getTarget('textarea')) return;

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
            case e.DELETE:
                e.stopEvent();
                if (! this.action_deleteRecord.isDisabled()) {
                    this.onDeleteRecords.call(this);
                }
                break;
        }
    },
    
    onPrintMenuClick: function(splitBtn, e) {
        if (! String(this.activeView).match(/(day|week)sheet$/i)) {
            splitBtn.menu.hide();
        }
    },
    
    onPrint: function(printMode) {
        var printMode = printMode ? printMode : this.defaultPrintMode,
            panel = this.getCalendarPanel(this.activeView),
            view = panel ? panel.getView() : null;
            
        if (view && Ext.isFunction(view.print)) {
            view.print(printMode);
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
        
        if (this.rendered) {
            // update filtertoolbar
            if(this.filterToolbar) {
                this.filterToolbar.setValue(store.proxy.jsonReader.jsonData.filter);
            }
            // update container tree
            Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getWestPanel().getContainerTreePanel().getFilterPlugin().setValue(store.proxy.jsonReader.jsonData.filter);
            
            // update attendee filter grid
            Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getWestPanel().getAttendeeFilter().setFilterValue(store.proxy.jsonReader.jsonData.filter);
        }
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

    onConflict: function (error, event, ignoreFn) {
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(error.event.attendee),
            fbInfo = new Tine.Calendar.FreeBusyInfo(error.freebusyinfo),
            denyIgnore = fbInfo.getStateOfAllAttendees() == Tine.Calendar.FreeBusyInfo.states.BUSY_UNAVAILABLE;

        this.conflictConfirmWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
            modal: true,
            allowCancel: false,
            width: 550,
            height: 180 + fbInfo.attendeeCount * 14 + 12 * error.freebusyinfo.length,
            title: this.app.i18n._('Scheduling Conflict'),
            questionText: '<div class = "cal-conflict-heading">' +
            this.app.i18n._('The following attendee are busy at the requested time:') +
                '</div>' +
                fbInfo.getInfoByAttendee(attendeeStore, event),
            options: [
                {text: this.app.i18n._('Ignore Conflict'), name: 'ignore', disabled: denyIgnore},
                {text: this.app.i18n._('Edit Event'), name: 'edit', checked: true},
                {text: this.app.i18n._('Cancel this action'), name: 'cancel'}
            ],
            scope: this,
            handler: function (option) {
                var panel = this.getCalendarPanel(this.activeView),
                    store = this.getStore();

                switch (option) {
                    case 'ignore':
                        if (Ext.isFunction(ignoreFn)) {
                            ignoreFn();
                        } else {
                            this.onAddEvent(event, false, true);
                        }
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
                            this.onEditInNewWindow(null, event);
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
    },

    onProxyFail: function(error, event, ignoreFn) {
        this.setLoading(false);
        if(this.loadMask) this.loadMask.hide();
        
        if (error.code === 901) {
            this.onConflict(error, event, ignoreFn);
        } else {
            error.clientProps = ['component', 'proxyEvent'];
            error.component = this;
            error.proxyEvent = event;
            Tine.Tinebase.ExceptionHandler.handleRequestException(error);
        }
    },
    
    refresh: function (options) {
        // convert old boolean argument
        options = Ext.isObject(options) ? options : {
            refresh: !!options
        };
        Tine.log.debug('Tine.Calendar.MainScreenCenterPanel::refresh(' + options.refresh + ')');

        // reset autoRefresh it might get lost if request fails
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 2000);
        }

        var panel = this.getCalendarPanel(this.activeView);
        panel.getStore().load(options);
        
        // clear favorites
        Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getWestPanel().getFavoritesPanel().getSelectionModel().clearSelections();
    },
    
    setLoading: function(bool) {
        if (this.rendered) {
            var panel = this.getCalendarPanel(this.activeView),
                tbar = panel.getTopToolbar();
                
            if (tbar && tbar.loading) {
                tbar.loading[bool ? 'disable' : 'enable']();
            }
        }
    },
    
    setResponseStatus: function(event, status) {
        var myAttenderRecord = event.getMyAttenderRecord();
        if (myAttenderRecord) {
            myAttenderRecord.set('status', status);
            this.updateEvent(event);
        }
    },

    setEventStatus: function(event, status) {
        event.set('status', status);
        this.updateEvent(event);
    },
    
    updateEvent: function(event) {
        event.dirty = true;
        event.modified = {};

        var panel = this.getCalendarPanel(this.activeView);
        var store = this.getStore();

        store.replaceRecord(event, event);

        this.onUpdateEvent(event);
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

        let currentCalendarPanel = _.get(this, `calendarPanels.${this.activeView}`);
        let currentPeriod = _.isFunction(_.get(currentCalendarPanel, 'getView')) &&
            _.isFunction(_.get(currentCalendarPanel.getView(), 'getPeriod')) ?
            currentCalendarPanel.getView().getPeriod() : null;

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
                period: _.cloneDeep(currentPeriod),
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
                            store: store,
                            startDate: tbar.getPeriod().from,
                            numOfDays: 1
                        });
                        break;
                    case 'monthSheet':
                        view = new Tine.Calendar.MonthView({
                            store: store,
                            period: tbar.getPeriod()
                        });
                        break;
                    case 'yearSheet':
                        view = new Tine.Calendar.YearView({
                            store: store,
                            period: tbar.getPeriod()
                        });
                        break;
                    default:
                    case 'weekSheet':
                        view = new Tine.Calendar.DaysView({
                            store: store,
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
                    view: view,
                    mainScreen: this,
                    canonicalName: ['Event', 'Sheet']
                });
            } else if (whichParts.presentation.match(/grid/i)) {
                this.calendarPanels[which] = new Tine.Calendar.GridView({
                    tbar: tbar,
                    store: store,
                    mainScreen: this,
                    canonicalName: ['Event', 'Grid']
                });
            }  else if (whichParts.presentation.match(/timeline/i)) {
                this.calendarPanels[which] = new Tine.Calendar.TimelinePanel({
                    tbar: tbar,
                    period: tbar.periodPicker.getPeriod(),
                    viewType: whichParts.period,
                    store: store,
                    canonicalName: ['Event', 'Timeline']
                });
            }

            this.calendarPanels[which]['canonicalName'].push(Ext.util.Format.capitalize(which.match(/[a-z]+/)[0]));
            this.calendarPanels[which]['canonicalName'] = this.calendarPanels[which]['canonicalName'].join(Tine.Tinebase.CanonicalPath.separator);

            this.calendarPanels[which].on('dblclick', this.onEditInNewWindow.createDelegate(this, ["edit"], 0));
            this.calendarPanels[which].on('contextmenu', this.onContextMenu, this);
            this.calendarPanels[which].getSelectionModel().on('selectionchange', this.updateEventActions, this);
            this.calendarPanels[which].on('keydown', this.onKeyDown, this);
            
            this.calendarPanels[which].relayEvents(this, ['show', 'beforehide']);

            // rebind store so it's event listeners get called at last.
            // -> otherwise loading spinner would be active event if the view beforeload cancels the request
            tbar.unbind(store);
            tbar.bind(store);
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
