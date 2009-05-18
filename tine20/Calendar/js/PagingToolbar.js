/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.PagingToolbar = Ext.extend(Ext.Toolbar, {
    
    /**
     * @private
     * @property activeView
     */
    activeView: 'week',
    /**
     * @private
     * @property periodPickers
     * holds a period picker/buttion for each view
     */
    periodPickers: {},
    
    
    
    onRender: function(ct, position) {
        Tine.Calendar.PagingToolbar.superclass.onRender.call(this, ct, position);
        
        this.prevBtn = this.addButton({
            tooltip: this.prevText,
            iconCls: "x-tbar-page-prev",
            handler: this.onClick.createDelegate(this, ["prev"])
        });
        this.addSeparator();
        
        // period selectors
        this.dayPicker = this.addField(new Ext.form.DateField({
            hidden: this.activeView != 'day'
        }));
        this.weekPickerText = this.addField(new Ext.form.Label({
            text: 'week',
            style: 'padding-right: 3px',
            hidden: this.activeView != 'week'
        }));
        this.weekPicker = this.addField(new Ext.form.TextField({
            width: 30,
            cls: "x-tbar-page-number",
            hidden: this.activeView != 'week'
        }));
        this.monthPicker = this.addButton({
            text: 'Mai 2009',
            hidden: this.activeView != 'month',
            menu: new Ext.menu.DateMenu({
                hideMonthPicker: Ext.DatePicker.prototype.hideMonthPicker.createSequence(function() {
                    if (this.monthPickerActive) {
                        this.monthPickerActive = false;
                        var dtStart = Date.parseDate(this.activeDate.format('Y-m') + '-01 00:00:00', Date.patterns.ISO8601Long);
                        //var dtEnd = dtStart.add(Date.MONTH, 1).add(Date.SECOND, -1);
                        
                        this.value = dtStart;
                        this.fireEvent('select', this, this.value);
                    }
                }),
                listeners: {
                    scope: this,
                    select: function(field) {
                        if (typeof(field.getValue) == 'function') {
                            this.monthPicker.value = field.getValue();
                            this.monthPicker.setText(this.monthPicker.value.format('M Y'));
                        }
                    }
                }
            }),
            listeners: {
                scope: this,
                menushow: function(btn, menu) {
                    menu.picker.showMonthPicker();
                    menu.picker.monthPickerActive = true;
                },
                menuhide: function(btn, menu) {
                    menu.picker.monthPickerActive = false;
                }
            }
        });

        this.addSeparator();
        this.nextBtn = this.addButton({
            tooltip: this.nextText,
            iconCls: "x-tbar-page-next",
            handler: this.onClick.createDelegate(this, ["next"])
        });
        this.addSeparator();
        this.loading = this.addButton({
            tooltip: this.refreshText,
            iconCls: "x-tbar-loading",
            handler: this.onClick.createDelegate(this, ["refresh"])
        });
        this.addFill();
        
        // view selectors
        this.dayBtn = this.addButton({
            //selectedView: 'day',
            //selectedViewMultiplier: 1,
            pressed: this.activeView == 'day',
            text: 'day view',
            iconCls: 'cal-day-view',
            xtype: 'tbbtnlockedtoggle',
            //handler: changeView,
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.weekBtn = this.addButton({
            //selectedView: 'day',
            //selectedViewMultiplier: 7,
            pressed: this.activeView == 'week',
            text: 'week view',
            iconCls: 'cal-week-view',
            xtype: 'tbbtnlockedtoggle',
            //handler: changeView,
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        this.MonthBtn = this.addButton({
            //selectedView: 'month',
            //selectedViewMultiplier: 1,
            pressed: this.activeView == 'month',
            text: 'month view',
            iconCls: 'cal-month-view',
            xtype: 'tbbtnlockedtoggle',
            //handler: changeView,
            enableToggle: true,
            toggleGroup: 'Calendar_Toolbar_tgViews'
        });
        
        if(this.dsLoaded){
            this.onLoad.apply(this, this.dsLoaded);
        }
        //this.first.hide();
        //this.last.hide();

    },
    
    onClick: function(which) {
        
    }
});