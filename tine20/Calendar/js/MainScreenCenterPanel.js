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
    
    initComponent: function() {
        this.recordClass = Tine.Calendar.Event;
        
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        // init some translations
        this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
        this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        this.i18nContainerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.i18nContainersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        this.initActions();
        this.initLayout();
        
        Tine.Calendar.MainScreenCenterPanel.superclass.initComponent.call(this);
    },
    
    initActions: function() {
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.i18nEditActionText ? this.app.i18n._hidden(this.i18nEditActionText) : String.format(Tine.Tinebase.tranlation._hidden('Edit {0}'), this.i18nRecordName),
            disabled: true,
            handler: this.onEditInNewWindow.createDelegate(this, ["edit"]),
            iconCls: 'action_edit'
        });
        
        this.action_addInNewWindow = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(Tine.Tinebase.tranlation._hidden('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow.createDelegate(this, ["add"]),
            iconCls: 'action_add'
        });
        
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? i18nDeleteActionText[0] : String.format(Tine.Tinebase.tranlation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? i18nDeleteActionText[1] : String.format(Tine.Tinebase.tranlation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.tranlation,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(Tine.Tinebase.tranlation.n_hidden('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
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
        
        this.actionToolbarActions = [
            this.action_addInNewWindow,
            this.action_editInNewWindow,
            this.action_deleteRecord
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
        var tbar = panel.getTopToolbar();
        var spacerEl = Ext.fly(Ext.DomQuery.selectNode('div[class=ytb-spacer]', tbar.el.dom)).parent();
        for (var i=this.changeViewActions.length-1; i>=0; i--) {
            this.changeViewActions[i].getEl().parent().insertAfter(spacerEl);
        }
        this['show' + Ext.util.Format.capitalize(view) +  'View'].toggle(true);
        
        // update actions
        this.updateEventActions();
        
        // update data
        panel.getView().updatePeriod({from: this.startDate});
        panel.getStore().load({});
        //this.updateMiniCal();
    },
    
    onDeleteRecords: function() {
        var panel = this.getCalendarPanel(this.activeView);
        var selection = panel.getSelectionModel().getSelectedEvents();
        
        Ext.each(selection, function(event){
            event.ui.markDirty();
        });
        
        var i18nQuestion = String.format(this.app.i18n.n_('Do you really want to delete this event?', 'Do you really want to delete the {0} selected events?', selection.length), selection.length);
        Ext.MessageBox.confirm(Tine.Tinebase.tranlation._hidden('Confirm'), i18nQuestion, function(btn) {
            if(btn == 'yes') {
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
                        Ext.MessageBox.alert(Tine.Tinebase.tranlation._hidden('Failed'), String.format(this.app.i18n.n_('Failed not delete event', 'Failed to delte the {0} evnets', selection.length), selection.length)) 
                    }
                };
                
                Tine.Calendar.backend.deleteRecords(selection, options);
            } else {
                Ext.each(selection, function(event){
                    event.ui.clearDirty();
                });
            }
        }, this);
        
    },
    
    /**
     * @param {String} action add|edit
     */
    onEditInNewWindow: function(action) {
        var event = null;
        
        if (action == 'edit') {
            var panel = this.getCalendarPanel(this.activeView);
            var selection = panel.getSelectionModel().getSelectedEvents();
            if (Ext.isArray(selection) && selection.length == 1) {
                event = selection[0];
            }
        }
        
        if (! event) {
            event = new Tine.Calendar.Event({
                container_id: this.app.getMainScreen().getTreePanel().getAddCalendar()
            }, 0);
        }
        
        Tine.Calendar.EventEditDialog.openWindow({
            record: event,
            listeners: {
                scope: this,
                update: function(eventJson) {
                    var updatedEvent = new Tine.Calendar.Event(Ext.util.JSON.decode(eventJson), event.id);
                    
                    var panel = this.getCalendarPanel(this.activeView);
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
        
        // note, we can't use ne 'normal' plugin approach here, cause we have to deal with n stores
        var calendarSelectionPlugin = this.app.getMainScreen().getTreePanel().getCalSelector().getFilterPlugin();
        calendarSelectionPlugin.onBeforeLoad.call(calendarSelectionPlugin, store, options)
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
            // @todo make this a Ext.data.Store
            var store = new Ext.data.Store({
                //autoLoad: true,
                id: 'id',
                fields: Tine.Calendar.Event,
                proxy: Tine.Calendar.backend,
                reader: new Ext.data.JsonReader({}), //Tine.Calendar.backend.getReader(),
                listeners: {
                    scope: this,
                    'beforeload': this.onStoreBeforeload
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
