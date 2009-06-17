/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: CalendarPanel.js 7900 2009-04-30 07:23:02Z c.weiss@metaways.de $
 */

Ext.namespace('Tine.Calendar');

/**
 * update app icon to reflect the current date
 */
Ext.onReady(function(){
    Ext.util.CSS.updateRule('.CalendarIconCls', 'background-image', 'url(../../images/view-calendar-day-' + new Date().getDate() + '.png)');
});

/**
 * default mainscreen
 * 
 * @type Tine.Tinebase.widgets.app.MainScreen
 */
Tine.Calendar.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    /**
     * sets content panel in mainscreen
     */
    setContentPanel: function() {
        if (! this.contentPanel) {
            this.contentPanel = new Tine.Calendar.MainScreenCenterPanel({
            
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.contentPanel, true);
    },
    
    getContentPanel: function() {
        return this.contentPanel;
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                items: this.contentPanel.actionToolbarActions
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    }
});

/**
 * @class Tine.Calendar.TreePanel
 * @extends Ext.Panel
 * 
 * @todo add d&d support to tree (change calendar)
 */
Tine.Calendar.TreePanel = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'border',
    cls: 'cal-tree',
    defaults: {
        border: false
    },
    
    initComponent: function() {
        
        this.calSelector = new Tine.widgets.container.TreePanel({
            region: 'center',
            app: Tine.Tinebase.appMgr.get('Calendar'),
            recordClass: Tine.Calendar.Model.Event,
            allowMultiSelection: true,
            afterRender: Tine.widgets.container.TreePanel.prototype.afterRender.createSequence(function() {
                this.selectPath('/root/all/user');
            }),
            listeners: {
                scope: this,
                click: function() {
                    var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                    contentPanel.refresh();
                }
            }
        });
        
        this.items = [this.calSelector, {
            region: 'south',
            collapsible: true,
            height: 190,
            items: new Ext.DatePicker({
                id :'cal-mainscreen-minical',
                plugins: [new Ext.ux.DatePickerWeekPlugin()],
                listeners: {
                    scope: this, 
                    select: function(picker, value, weekNumber) {
                        var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                        contentPanel.changeView(weekNumber ? 'week' : 'day', value);
                    },
                    render: function(picker) {
                        // fix height of minipicker panel (south panel)
                        var layout = this.layout;
                        layout.south.el.setHeight(picker.el.getHeight());
                        layout.layout();
                    }
                }
            })
        }];   
        Tine.Calendar.TreePanel.superclass.initComponent.call(this);
    },
    
    getCalSelector: function() {
        return this.calSelector;
    },
    
    /**
     * returns a calendar to take for a add event action
     * 
     * @todo add more sophisticated logic
     * 
     * @return {Tine.Model.Container}
     */
    getAddCalendar: function() {
        var selections = this.getCalSelector().getSelectionModel().getSelectedNodes();
        
        // take first container with add grant
        Ext.each(selections, function(node){
            var attr = node.attributes;
            if (attr.containerType == 'singleContainer' && attr.container.account_grants.addGrant) {
                return attr.container;
            }
        });
        
        return Tine.Calendar.registry.get('defaultCalendar');
    }
});

