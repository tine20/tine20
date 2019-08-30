Tine.Calendar.CalendarPanelSplitPlugin = function() {
    
};

/**
 * @TODO ui for active view?
 * @TODO we could also create views beforeload for better performace
 * 
 */
Tine.Calendar.CalendarPanelSplitPlugin.prototype = {
    /**
     * @property app
     * @type Tine.Calendar.Application
     */
    app: null,
    
    /**
     * @property attendeeViews
     * @type Ext.util.MixedCollection
     */
    attendeeViews: null,
    
    /**
     * @property mainStore store of main view
     * @type Ext.Store
     */
    mainStore: null,
    
    init: function(calPanel) {

        if (! Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featureSplitView')) {
            return;
        }

        this.calPanel = calPanel;
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.attendeeViews = new Ext.util.MixedCollection();
        
        // NOTE: we can't use a normal hbox layout as it can't deal with minWidth and overflow.
        //       As ext has no suiteable layout for this, we do a little hack
        this.calPanel.layout = new Ext.layout.HBoxLayout({
            align : 'stretch',
            pack  : 'start'
        });
        this.calPanel.layout.onLayout = this.calPanel.layout.onLayout.createInterceptor(function() {
            var viewCount = this.attendeeViews.getCount(),
                availableWidth = this.calPanel.getWidth(),
                minViewWidth = this.calPanel.view.boxMinWidth;

            var width = availableWidth/viewCount < minViewWidth ? minViewWidth * viewCount : availableWidth;
            this.calPanel.body.setWidth(width);
            this.calPanel.body.setStyle('overflow-x', width > availableWidth ? 'scroll' : 'hidden');
        }, this);
        this.calPanel.on('afterlayout', function() {
            this.calPanel.body.setWidth(this.calPanel.getWidth());
        }, this);
        
        this.mainStore = this.calPanel.view.store;
        this.mainStore.on('load', this.onMainStoreLoad, this);
        this.mainStore.on('beforeload', this.onMainStoreBeforeLoad, this);
        
        // NOTE: we remove from items to avoid autoDestroy
        this.calPanel.items.remove(this.calPanel.view);
        
        this.calPanel.getView = this.getActiveView.createDelegate(this);
        
        this.calPanel.on('show', this.onCalPanelShow, this);
        this.calPanel.on('hide', this.onCalPanelHide, this);
        
        this.calPanel.on('afterrender', function() {
            this.attendeeFilterGrid = this.app.getMainScreen().getWestPanel().getAttendeeFilter();
            this.attendeeFilterGrid.on('sortchange', this.onAttendeeFilterSortChange, this);
        }, this);
        
        // hook into getDefaultData 
        this.originalGetDefaultData = Tine.Calendar.Model.Event.getDefaultData;
        Tine.Calendar.Model.Event.getDefaultData = this.getDefaultData.createDelegate(this);
    },
    
    getDefaultData: function() {
        var defaultData = this.originalGetDefaultData(),
            useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn && Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed,
            centerPanel = this.app.getMainScreen().getCenterPanel(),
            activeCalPanel = centerPanel.getCalendarPanel(centerPanel.activeView),
            activeView = this.getActiveView(),
            attendee = activeView && activeView.ownerCt ? activeView.ownerCt.attendee : null;
        
        if (useSplit && attendee && this.calPanel == activeCalPanel) {
            Ext.apply(attendee.data, {
                status: 'ACCEPTED'
            });
            defaultData.attendee = [attendee.data];
        }
        
        return defaultData;
    },
    
    onCalPanelShow: function() {
        if (Tine.Calendar.CalendarPanelSplitPlugin.splitBtn) {
            Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.enable();
        } else {
            Tine.Calendar.CalendarPanelSplitPlugin.SplitBtn.prototype.disabled = false;
        }
    },
    
    onCalPanelHide: function() {
        if (Tine.Calendar.CalendarPanelSplitPlugin.splitBtn) {
            Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.disable();
        }
    },
    
    onMainStoreBeforeLoad: function(store, options) {
        var ret = true;
        this.attendeeViews.each(function(attendeeView) {
            ret = ret && attendeeView.store.fireEvent('beforeload', store, options);
        }, this);

        return ret;
    },
    
    onMainStoreLoad: function(store, options) {
        var cp = this.app.getMainScreen().getCenterPanel();
        
        if (store !== cp.getCalendarPanel(cp.activeView).getStore()) {
            Tine.log.debug('Tine.Calendar.CalendarPanelSplitPlugin::onMainStoreLoad try again with active subview');
            cp.onStoreLoad(this.getActiveView().store, options);
        }

        if (! this.attendeeFilterGrid) {
            return;
        }
        
        // create view for each attendee
        var filteredAttendee = this.attendeeFilterGrid.getValue(),
            attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(filteredAttendee),
            useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn && Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed;
            
        // remove views not longer in filter
        this.calPanel.items.each(function(view) {
            if (view.attendee && (! useSplit || ! Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, view.attendee))) {
                this.removeAttendeeView(view.attendee);
            }
            
        }, this);
        
        this.manageSplitViews(filteredAttendee);
        
        // manage main (main is shown if no split criteria is present)
        if (! filteredAttendee.length || ! useSplit) {
            if (! this.attendeeViews.get('main')) {
                var view = this.createView({
                    store: this.cloneStore(false)
                });
                this.attendeeViews.add('main', view);
                
                this.calPanel.add({
                    layout: 'fit',
                    border: false,
                    flex: 1,
                    height: this.calPanel.getHeight(), // <- initialHeight
                    items: view
                });
            } else {
                this.attendeeViews.get('main').initData(this.cloneStore(false));
                this.attendeeViews.get('main').onLoad();
            }
        } else {
            var main = this.attendeeViews.get('main');
            if (main && main.ownerCt) {
                this.calPanel.remove(main.ownerCt);
            }
            
            this.attendeeViews.removeKey('main');
        }

        this.calPanel.doLayout();
    },
    
    addAttendeeView: function(attendee, pos) {
        var attendeeName = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attendee.get('user_id'), false, attendee),
            attendeeViewId = this.getAttendeeViewId(attendee);
        
        var filter = function(r) {
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(r.get('attendee'));
            return Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(attendeeStore, attendee);
        };
        
        var store = this.cloneStore(filter);
        
        var view = this.createView({
            title: attendeeName,
            store: store,
            print: this.onPrint.createDelegate(this)
        });
        
        // manage active views
        view.on('render', function(v){
            v.mon(v.getEl(), 'mousedown', this.setActiveAttendeeView.createDelegate(this, [attendeeViewId]), this);
        }, this);
        
        this.attendeeViews.add(attendeeViewId, view);
        this.calPanel.insert(pos, {
            xtype: 'tabpanel',
            style: 'padding: 5px;',
            id: attendeeViewId,
            attendee: attendee,
            plain: true,
            flex: 1,
            height: this.calPanel.getHeight(), // <- initialHeight
            activeItem: 0,
            items: view
        });
    },
    
    onAttendeeFilterSortChange: function() {
        var filteredAttendee = this.attendeeFilterGrid.getValue();
        
        this.manageSplitViews(filteredAttendee);
        this.calPanel.doLayout();
    },
    
    manageSplitViews: function(filteredAttendee) {
        // create view for each attendee
        var useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn && Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed;
        
        if (useSplit) {
            // add subviews new to filter
            // NOTE: we don't iterate the attendeeStore as we would loose attendee order than
            Ext.each(filteredAttendee, function(attendeeData, idx) {
                var attendee = new Tine.Calendar.Model.Attender(attendeeData, attendeeData.id);
                var attendeeView = this.attendeeViews.get(this.getAttendeeViewId(attendee));
                if (! attendeeView) {
                    this.addAttendeeView(attendee, idx);
                } else {
                    //assert position
                    this.calPanel.items.remove(attendeeView.ownerCt);
                    this.calPanel.items.insert(idx, attendeeView.ownerCt);

                    var filterFn = attendeeView.store.filterFn;
                    attendeeView.initData(this.cloneStore(filterFn));
                    attendeeView.onLoad();
                }
            }, this);
        }
    },
    
    getAttendeeViewId: function(attendee) {
        return this.calPanel.id + '-' + attendee.get('user_type') + '-' + attendee.getUserId();
    },
    
    removeAttendeeView: function(attendee) {
        var attendeeViewId = this.getAttendeeViewId(attendee);
        
        //@TODO remove relayed events?
        
        this.attendeeViews.removeKey(attendeeViewId);
        this.calPanel.remove(attendeeViewId);
    },
    
    createView: function(config) {
        var view = Ext.create(Ext.apply({
            xtype: this.calPanel.view.getXType(),
            startDate: this.calPanel.getTopToolbar().periodPicker.getPeriod().from,
            numOfDays: this.calPanel.view.numOfDays,
            period: this.calPanel.getTopToolbar().periodPicker.getPeriod(),
            updatePeriod: this.updatePeriod.createDelegate(this)
        }, config));

        // NOTE: this is already done by the view itself - don't have dublicate events in mainscreen!
        // this.calPanel.relayEvents(view, ['changeView', 'changePeriod', 'addEvent', 'updateEvent', 'click', 'dblclick', 'contextmenu', 'keydown']);
        this.calPanel.view.relayEvents(view, ['changeView', 'changePeriod', 'addEvent', 'updateEvent', 'click', 'dblclick', 'contextmenu', 'keydown']);
        this.calPanel.view.getSelectionModel().relayEvents(view.getSelectionModel(), 'selectionchange');
        view.getSelectionModel().on('selectionchange', this.onSelectionChange.createDelegate(this, [view]));
        
        if (view.onBeforeScroll) {
            view.onBeforeScroll = view.onBeforeScroll.createSequence(this.onScroll.createDelegate(this, [view], 0));
        }

        view.on('onBeforeAllDayScrollerResize', this.onAllDayAreaResize, this);
        return view;
    },
    
    /**
     * is called on scroll of a grid, scrolls the other grids
     * 
     * @param {Object} activeView
     * @param {Object} e Event
     */
    onScroll: function(activeView, e) {
        if (! (activeView && activeView.scroller && activeView.scroller.dom && activeView.getHeight() > 100)) {
            return;
        }

        var scrollTop = activeView.scroller.dom.scrollTop;

        this.attendeeViews.each(function(view) {
            if (activeView.id != view.id && view.scroller) {
                view.scroller.dom.scrollTop = scrollTop;
            }
        }, this);
    },

    getActiveView: function() {
        if (! this.attendeeViews.getCount()) {
            return this.calPanel.view;
        }

        if (! this.activeAttendeeView || this.attendeeViews.indexOf(this.activeAttendeeView) < 0) {
            this.activeAttendeeView = this.attendeeViews.itemAt(0);
        }
        return this.activeAttendeeView;
    },
    
    onPrint: function(printMode) { 
        var renderer = new Tine.Calendar.Printer.SplitViewRenderer({printMode: printMode});
        renderer.print(this);
    },
    
    onSelectionChange: function(view) {
        view = Ext.isString(view) ? this.attendeeViews.get(view) : view;
        this.setActiveAttendeeView(view);
        
        this.attendeeViews.each(function(v) {
            if (v !== view) {
                v.getSelectionModel().clearSelections(true);
            }
        }, this);
        
        if (this.calPanel.mainScreen) {
            this.calPanel.mainScreen.updateEventActions.call(this.calPanel.mainScreen);
        }
    },
    
    setActiveAttendeeView: function(view) {
        view = Ext.isString(view) ? this.attendeeViews.get(view) : view;

        if (view != this.activeAttendeeView && this.activeAttendeeView.endEditSummary && this.activeAttendeeView.editing) {
            this.activeAttendeeView.endEditSummary();
        }

        this.activeAttendeeView = view;
    },
    
    updatePeriod: function() {
        var origArgs = arguments;
        
        this.attendeeViews.each(function(view) {
            view.constructor.prototype.updatePeriod.apply(view, origArgs);
        }, this);
    },

    onAllDayAreaResize: function (originView, rzEvent) {
        if (this.attendeeViews.items.length > 1) {
            var maxWholeDayAreaSize = 10;
            this.attendeeViews.each(function(view) {
                if (view.wholeDayArea) {
                    var allDayAreaHeight = view.computeAllDayAreaHeight();

                    if (allDayAreaHeight > maxWholeDayAreaSize) {
                        maxWholeDayAreaSize = allDayAreaHeight;
                    }
                }
            }, this);

            rzEvent.wholeDayScrollerHeight = Math.min(maxWholeDayAreaSize, rzEvent.maxAllowedHeight);

            this.attendeeViews.each(function(view) {
                if (view.wholeDayArea) {
                    var allDayAreaEl = Ext.get(view.wholeDayArea),
                        allDayAreaHeight = allDayAreaEl.getHeight();
                    view.wholeDayScroller.setHeight(rzEvent.wholeDayScrollerHeight);
                    allDayAreaEl.setHeight(Math.max(allDayAreaHeight, maxWholeDayAreaSize));
                }
            }, this);
        }
    },

    cloneStore: function(filterFn) {
        var clone = new Ext.data.Store({
            fields: Tine.Calendar.Model.Event,
            load: this.mainStore.load.createDelegate(this.mainStore),
            proxy: this.mainStore.proxy,
            filterFn: filterFn,
            replaceRecord: function(o, n) {
                var idx = this.indexOf(o);
                this.remove(o);
                this.insert(idx, n);
            }
        });
        
        var rs = [];
        this.mainStore.each(function(r) {
            if (! filterFn || filterFn(r)) {
                rs.push(r.copy());
            }
        }, this);
        
        clone.add(rs);
        
        clone.on('add', this.onCloneStoreAdd, this);
        clone.on('update', this.onCloneStoreUpdate, this);
        clone.on('remove', this.onCloneStoreRemove, this);
        return clone;
    },
    
    onCloneStoreAdd: function(store, rs) {
        Ext.each(rs, function(r){
            this.attendeeViews.each(function(view) {
                if (view.store != store) {
                    if (! view.store.filterFn || view.store.filterFn(r)) {
                        view.store.un('add', this.onCloneStoreAdd, this);
                        view.store.add([r.copy()]);
                        view.store.on('add', this.onCloneStoreAdd, this);
                    }
                }
            }, this);
            
            // check if events fits into view @see Tine.Calendar.MainScreenCenterPanel::congruenceFilterCheck
            if (store.filterFn && !store.filterFn(r)) {
                (function() {
                    if (this.ui && this.ui.rendered) {
                        this.ui.markOutOfFilter();
                    }
                }).defer(25, r)
            }
        }, this);
    },
    
    onCloneStoreUpdate: function(store, r) {
        this.attendeeViews.each(function(view) {
            if (view.store != store) {
                view.store.un('add', this.onCloneStoreAdd, this);
                view.store.un('remove', this.onCloneStoreRemove, this);
                
                var cr = view.store.getById(r.id);
                if (cr) {
                    view.store.remove(cr);
                    view.store.add([r.copy()]);
                }
                
                view.store.on('add', this.onCloneStoreAdd, this);
                view.store.on('remove', this.onCloneStoreRemove, this);
            }
        }, this);
    },
    
    onCloneStoreRemove: function(store, r) {
        this.attendeeViews.each(function(view) {
            if (view.store != store) {
                view.store.un('remove', this.onCloneStoreRemove, this);
                view.store.remove(view.store.getById(r.id));
                view.store.on('remove', this.onCloneStoreRemove, this);
            }
        }, this);
    }
};

Ext.preg('Calendar.CalendarPanelSplitPlugin', Tine.Calendar.CalendarPanelSplitPlugin);

Tine.Calendar.CalendarPanelSplitPlugin.SplitBtn = Ext.extend(Ext.Button, {
    enableToggle: true,
    pressed: false,
    disabled: true,
    scale: 'medium',
    rowspan: 2,
    iconCls: 'cal-split-view',
    iconAlign: 'top',
    text: 'Split',
    stateful: true,
    stateId: 'cal-calpanel-split-btn',
    stateEvents: ['toggle'],
    
    initComponent: function() {
        if (! Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featureSplitView')) {
            // hide button and make sure it isn't pressed
            Tine.log.info('Split view feature is deactivated');
            this.hidden = true;
            this.pressed = false;
        }

        Tine.Calendar.CalendarPanelSplitPlugin.SplitBtn.superclass.initComponent.apply(this, arguments);
        Tine.Calendar.CalendarPanelSplitPlugin.splitBtn = this;
    },
    
    handler: function() {
        Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel().refresh();
    },
    
    getState: function() {
        return {pressed: this.pressed}
    }
});

Tine.Calendar.Printer.SplitViewRenderer = Ext.extend(Tine.Calendar.Printer.BaseRenderer, {
    getAdditionalHeaders: Tine.Calendar.Printer.DaysViewRenderer.prototype.getAdditionalHeaders,
    generateBody: function(splitView) {
        var viewRenderer = splitView.calPanel.view.printRenderer,
            htmlArray = [],
            me = this,
            _ = window.lodash;

        me.rendererArray = [];
        me.viewArray = [];
        me.paperHeight = viewRenderer.paperHeight;
        me.useHtml2Canvas = me.printMode == 'sheet' && splitView.calPanel.view.cls != "cal-monthview";
        
        return _.reduce(splitView.attendeeViews.items, function(promise, view, i) {
            return promise.then(function() {
                var renderer = new view.printRenderer({printMode: me.printMode});

                me.rendererArray.push(renderer);
                me.viewArray.push(view);
                renderer.extraTitle = view.title + ' // ';
                renderer.titleStyle = i > 0 ? 'page-break-before:always' : '';
                return renderer.generateBody(view);

            }).then(function(html) {
                htmlArray.push('<div class="page">' + html + '</div>');
            })
        }, Promise.resolve('')).then(function(){
            return htmlArray.join('');
        });
    },

    onBeforePrint: function(doc, view) {
        Ext.each(this.rendererArray, function(renderer, i) {
            renderer.onBeforePrint(doc, this.viewArray[i]);
        }, this)
    }
});

// self register
Tine.Calendar.CalendarPanel.prototype.plugins = '[{"ptype": "Calendar.CalendarPanelSplitPlugin"}]';
Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ViewBtnGrp', Tine.Calendar.CalendarPanelSplitPlugin.SplitBtn, -10);
