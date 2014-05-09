Tine.Calendar.CalendarPanelSplitPlugin = function() {
    
}

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
            useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed,
            centerPanel = this.app.getMainScreen().getCenterPanel(),
            activeCalPanel = centerPanel.getCalendarPanel(centerPanel.activeView),
            activeView = this.getActiveView(),
            attendee = activeView && activeView.ownerCt ? activeView.ownerCt.attendee : null;
        
        if (useSplit && attendee && this.calPanel == activeCalPanel) {
            console.log(activeView);
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
        this.attendeeViews.each(function(attendeeView) {
            attendeeView.store.fireEvent('beforeload', store, options);
        }, this);
    },
    
    onMainStoreLoad: function(store, options) {
        var cp = this.app.getMainScreen().getCenterPanel();
        
        if (store !== cp.getCalendarPanel(cp.activeView).getStore()) {
            Tine.log.debug('Tine.Calendar.CalendarPanelSplitPlugin::onMainStoreLoad try again with active subview');
            cp.onStoreLoad(this.getActiveView().store, options);
        }
        
        // create view for each attendee
        var filteredAttendee = this.attendeeFilterGrid.getValue(),
            attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(filteredAttendee),
            useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed;
            
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
                    id: 'main',
                    border: false,
                    flex: 1,
                    items: view
                });
            } else {
                this.attendeeViews.get('main').initData(this.cloneStore(false));
                this.attendeeViews.get('main').onLoad();
            }
        } else {
            this.attendeeViews.removeKey('main');
            this.calPanel.remove('main');
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
        var useSplit = Tine.Calendar.CalendarPanelSplitPlugin.splitBtn.pressed;
        
        if (useSplit) {
            // add subviews new to filter
            // NOTE: we don't iterate the attendeeStore as we would loose attendee order than
            Ext.each(filteredAttendee, function(attendeeData, idx) {
                var attendee = new Tine.Calendar.Model.Attender(attendeeData, attendeeData.id)
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
            updatePeriod: this.updatePeriod.createDelegate(this),
            scrollBuffer: 0
        }, config));
        
        this.calPanel.relayEvents(view, ['changeView', 'changePeriod', 'addEvent', 'updateEvent', 'click', 'dblclick', 'contextmenu', 'keydown']);
        this.calPanel.view.relayEvents(view, ['changeView', 'changePeriod', 'addEvent', 'updateEvent', 'click', 'dblclick', 'contextmenu', 'keydown']);
        this.calPanel.view.getSelectionModel().relayEvents(view.getSelectionModel(), 'selectionchange');
        view.getSelectionModel().on('selectionchange', this.onSelectionChange.createDelegate(this, [view]));
        
        if (view.onScroll) {
            view.onScroll = view.onScroll.createSequence(this.onScroll, this, view);
        }
        
        return view;
    },
    
    /**
     * is called on scroll of a grid, scrolls the other grids
     * 
     * @param {Object} activeView
     */
    onScroll: function(activeView) {
        
        if (! activeView) {
            return;
        }
        
        var scrollTop = activeView.target.scrollTop;
        
        Tine.log.debug('Caught scroll top of ' + scrollTop);
        
        this.attendeeViews.each(function(view) {
            if (activeView.id != view.id) {
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
    
    onPrint: function() {
        var renderer = new Tine.Calendar.Printer.SplitViewRenderer();
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
        
        this.resizeWholeDayArea.defer(this.attendeeViews.items.length * 120, this);
    },
    
    setActiveAttendeeView: function(view) {
        view = Ext.isString(view) ? this.attendeeViews.get(view) : view;
        
        this.activeAttendeeView = view;
    },
    
    updatePeriod: function() {
        var origArgs = arguments;
        
        this.attendeeViews.each(function(view) {
            view.constructor.prototype.updatePeriod.apply(view, origArgs);
        }, this);
        
        this.resizeWholeDayArea.defer(this.attendeeViews.items.length * 120, this, [true]);
    },
    
    /**
     * set whole day area to the height of the highest whole day area
     */
    resizeWholeDayArea: function(onupdate) {
        if (this.attendeeViews.items.length > 1) {
            var maxWholeDayAreaSize = 10;
            this.attendeeViews.each(function(view) {
                if (onupdate) {
                    view.heightIsCalculated = false;
                }
                if (view.wholeDayArea.children.length > 1  && ((view.wholeDayArea.hasOwnProperty('heightIsCalculated') && view.wholeDayArea.heightIsCalculated == false) || (! view.wholeDayArea.hasOwnProperty('heightIsCalculated')))) {
                    maxWholeDayAreaSize = (parseInt(view.wholeDayArea.clientHeight) > maxWholeDayAreaSize) ? view.wholeDayArea.clientHeight : maxWholeDayAreaSize;
                }
            });
            this.attendeeViews.each(function(view) {
                var wholeDayAreaEl = Ext.get(view.wholeDayArea);
                if (wholeDayAreaEl.getHeight() != maxWholeDayAreaSize) {
                    wholeDayAreaEl.setHeight(maxWholeDayAreaSize);
                    view.wholeDayArea.heightIsCalculated = true;
                } else {
                    view.wholeDayArea.heightIsCalculated = false;
                }
                Ext.fly(view.wholeDayScroller).setHeight(maxWholeDayAreaSize);
            });
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
    pressed: true,
    disabled: true,
    scale: 'medium',
    rowspan: 2,
    icon: 'images/oxygen/22x22/actions/fileview-column.png',
    iconAlign: 'top',
    text: 'Split',
    stateful: true,
    stateId: 'cal-calpanel-split-btn',
    stateEvents: ['toggle'],
    
    initComponent: function() {
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
    generateBody: function(splitView) {
        var viewRenderer = splitView.calPanel.view.printRenderer,
        htmlArray = [];
        
        this.paperHeight = viewRenderer.paperHeight;
        
        splitView.attendeeViews.each(function(v, i) {
            var renderer = new v.printRenderer();
            renderer.extraTitle = v.title + ' // ';
            renderer.titleStyle = i > 0 ? 'page-break-before:always' : '';

            htmlArray.push(renderer.generateBody(v));
        }, this);
        
        return htmlArray.join('');
    }
});

// self register
Tine.Calendar.CalendarPanel.prototype.plugins = '[{"ptype": "Calendar.CalendarPanelSplitPlugin"}]';
Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ViewBtnGrp', Tine.Calendar.CalendarPanelSplitPlugin.SplitBtn, -10);
