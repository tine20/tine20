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
    activeView: 'month',
    
    calendarPanels: {},
    
    border: false,
    layout: 'border',
    
    initComponent: function() {
        
        this.initActions();
        this.initLayout();
        
        Tine.Calendar.MainScreenCenterPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        this.showDayView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'day',
            text: 'day view',
            iconCls: 'cal-day-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["day"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showWeekView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'week',
            text: 'week view',
            iconCls: 'cal-week-view',
            xtype: 'tbbtnlockedtoggle',
            handler: this.changeView.createDelegate(this, ["week"]),
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.showMonthView = new Ext.Toolbar.Button({
            pressed: this.activeView == 'month',
            text: 'month view',
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
        
        // add detail panel
        if (this.detailsPanel) {
            this.items.push({
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
                
            });
            this.detailsPanel.doBind(this.grid);
        }
        
        // add filter toolbar
        if (this.filterToolbar) {
            this.items.push(this.filterToolbar);
            this.filterToolbar.on('bodyresize', function(ftb, w, h) {
                if (this.filterToolbar.rendered && this.layout.rendered) {
                    this.layout.layout();
                }
            }, this);
        }
    },
    
    changeView: function(view) {
        var panel = this.getCalendarPanel(view);
        var cardPanel = this.items.first();
        
        if(panel.rendered) {
            cardPanel.layout.setActiveItem(panel.id);
        } else {
            cardPanel.add(panel);
            cardPanel.layout.setActiveItem(panel.id);
            cardPanel.doLayout();
        }
        
        // move around changeViewButtons
        var tbar = panel.getTopToolbar();
        var spacerEl = Ext.fly(Ext.DomQuery.selectNode('div[class=ytb-spacer]', tbar.el.dom)).parent();
        
        for (var i=this.changeViewActions.length-1; i>=0; i--) {
            this.changeViewActions[i].getEl().parent().insertAfter(spacerEl);
        }
        
        panel.getStore().load({});
    },
    
    updateView: function(which) {
        var panel = this.getCalendarPanel(which);
        var period = panel.getTopToolbar().getPeriod();
        
        panel.getView().updatePeriode(period);
        panel.getStore().load({});
    },
    
    /**
     * returns requested CalendarPanel
     * 
     * @param {String} which
     * @return {Tine.Calendar.CalendarPanel}
     */
    getCalendarPanel: function(which) {
        if (! this.calendarPanels[which]) {
            // @todo make this a Ext.data.Store
            var store = new Ext.data.Store({
                autoLoad: true,
                id: 'id',
                fields: Tine.Calendar.Event,
                proxy: Tine.Calendar.backend,
                reader: new Ext.data.JsonReader({})//, //Tine.Calendar.backend.getReader(),
            });
            
            var tbar = new Tine.Calendar.PagingToolbar({
                view: which,
                store: store,
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
                    change: this.updateView.createDelegate(this, [which])
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
                        periode: tbar.getPeriod()
                    });
            }
            
            this.calendarPanels[which] = new Tine.Calendar.CalendarPanel({
                tbar: tbar,
                store: store,
                view: view
            });
        }
        
        return this.calendarPanels[which];
    },
    
    appendViewSelect: function(tbar) {
        //tbar.
        //if (! this.viewSelect)
    }
});
