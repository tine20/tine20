Ext.namespace('Tine.Calendar');

Date.Const = {
    msMINUTE : 60*1000,
	msHOUR   : 60*60*1000,
	msDAY    : 24*60*60*1000,
}

Tine.Calendar.today = function()
{
	// calculate start of today
	if (!today) {
		var today = new Date();
		today.setSeconds(0);
		today.setMinutes(0);
		today.setHours(0);
		today.setMilliseconds(0);
	}
    return today;
}();

/**
 * Calendar Request singelton
 * gets instantiated by getPanel()!
 */
Tine.Calendar.Request = function(){
	this.view = 'day';                                                // {day|month|planer}
    this.viewMultiplier = 1;
    this.start = Tine.Calendar.today;                                  // start Date
    this.end = Tine.Calendar.today.add(Date.MILLI,
	   this.viewMultiplier*Date.Const.msDAY-1                         // end Date
	);
    this.calendars = 5;                                               // calendars to present
    this.filters = false;                                             // filters to apply
    
	this.addEvents(
        "requestchange"
	);

    this.changeView = function(newView)
    {
		if (newView.selectedView != this.view || newView.selectedViewMultiplier != this.viewMultiplier) {
			this.view = newView.selectedView;
			this.viewMultiplier = newView.selectedViewMultiplier;
			this.end = Tine.Calendar.today.add(Date.MILLI,
		        this.viewMultiplier*Date.Const.msDAY-1
		    );
			this.fireEvent('requestchange', this);
		}
    }
	
	Tine.Calendar.Request.superclass.constructor.call(this);
}
Ext.extend(Tine.Calendar.Request, Ext.util.Observable);
Tine.Calendar.Request = new Tine.Calendar.Request();

Tine.Calendar.Preferences = {
	workDayStart: new Date(Date.Const.msHOUR*9),// + new Date().getTimezoneOffset()),
	workDayEnd: new Date(Date.Const.msHOUR*18)// + new Date().getTimezoneOffset())
};

// entry point, required by tinebase
Tine.Calendar.getPanel = function() {
    
    var calPanel =  new Ext.Panel({
		iconCls: 'CalendarTreePanel',
        title: 'Calendar',
        //html: '<p>Magic Calendar App!</p>',
        items: new Ext.DatePicker({}),
        border: false
    });
    
    calPanel.on('beforeexpand', function(_calPanel) {
        Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Calendar.MainScreen.getMainScreen(Tine.Calendar.Request));
        Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Calendar.ToolBar.getToolBar());
    });
    
    return calPanel;
}


Tine.Calendar.MainScreen = function() {
	
	/**
	 * @var {Ext.data.Store} events store
	 */
	var store;
	
	/**
	 * Layout of day views
	 * @var {Ext.Panel} day view layout
	 */
	var dayViewLayout;
	
    /**
     * @var {Ext.Panel} grid for whole-day events
     */
	var WholeDayGrid;
	
	/**
	 * @var {Ext.Panel} time grid
	 */
	var TimeGrid;
	
	
	
	// internal states
	var States = {
		StoreLoaded: false,
		GridSized:   false,
	};
	
	// layout dimensions
	var dims = {
        timeAxisWidth: 50,
        timeSheetWidth: 0,
    };
	
	/**
     * @param {Tine.Calendar.Request} request
     */
	var initStore = function(request)
	{
		store = new Ext.data.JsonStore({
			baseParams: {
				method: 'Calendar.getEvents'
			},
			root: 'results',
			totalProperty: 'totalcount',
			id: 'cal_id',
			fields: [
			    { name: 'cal_id' }, 
			    { name: 'cal_start', type: 'date', dateFormat: 'c' },
				{ name: 'cal_end', type: 'date', dateFormat: 'c' },
				{ name: 'cal_title'	},
				{ name: 'cal_description' }
			],
			// turn on remote sorting
			remoteSort: false
		});
		
		// some calibration to render events into grid
		store.on('load', function(s, rs, o)	{
			States.StoreLoaded = true;
			DisplayCalendarEvents(s);
		});
		
		with (Tine.Calendar.Request) {
			store.load({
				params: {
					start: start.format('c'),
					end: end.format('c'),
					users: calendars,
					filters: filters
				}
			});
		}
	};
	
	/**
	 * @param {Tine.Calendar.Request} request
	 */
	var initTimeGrid = function(request)
	{
		States.GridSized = false;
        if (request.view != 'day') {
			throw new Error(request.view + ' not implemeted yet!')
		}
		var nDays = request.viewMultiplier;
		
		/**
		 * helper function to create columns
		 * for TimeGrid and WholeDayGrid
		 * 
		 * @param {bool} includeHeader
		 */
		var mkDayColumns = function(includeHeader)
		{
			var columns = new Array({
				id: 'time',
				//header: "",
				width: dims.timeAxisWidth,
				fixed: true,
				sortable: false,
				dataIndex: 'time'
			});
			
			for (var i = 1; i <= nDays; i++) {
				columns.push({
					//header: includeHeader ? Tine.Tinebase.Common.dateRenderer(request.start.add(Date.DAY, i-1)) : '',
					header: includeHeader ? request.start.add(Date.DAY, i-1).format('l, \\t\\he jS \\o\\f F') : '',
					sortable: false,
					fixed: true,
					dataIndex: 'cdata'
				})
			}
			return columns;
		};
		
		
		TimeGrid = new Ext.grid.GridPanel({
			store: new Ext.data.SimpleStore({
				'fields': ['time', 'cdata'],
				'data': mkTimeGridData(nDays, 30*Date.Const.msMINUTE)
			}),
			columns: mkDayColumns(false),
			sm: new Ext.grid.CellSelectionModel({}),
			//view: new Tine.Calendar.GridView_Days({}),
			iconCls: 'icon-grid',
			border: false
		});
		
		
		TimeGrid.on('resize', function(cmp){
			States.GridSized = false;
			this.MainScreenCmp = cmp;
			
			// resize columns
			var cm = TimeGrid.getColumnModel();
			var cm2 = WholeDayGrid.getColumnModel();
			var nc = cm.getColumnCount();
			var cw = dims.timeSheetWidth = 
			    (
			        cmp.getSize().width - 
				    dims.timeAxisWidth -
				    TimeGrid.getView().scrollOffset
				) / (nc - 1);
				
			for (var i = 1; i < nc; i++) {
				cm.setColumnWidth(i, cw);
				cm2.setColumnWidth(i, cw);
			}

			States.GridSized = true;
			DisplayCalendarEvents(store);
		});
		
		WholeDayGrid = new Ext.grid.GridPanel({
			store: new Ext.data.SimpleStore({
                'fields': ['time', 'cdata']
                //'data': [['Gantaegig']]
			}),
			sm: new Ext.grid.CellSelectionModel({}),
            //view: new Tine.Calendar.GridView_Days({}),
            iconCls: 'icon-grid',
            border: false,
            columns: mkDayColumns(true)
		});
		
		dayViewLayout = new Ext.Panel({
	        layout:'border',
	        border: false,
	        items: [{
				id: 'calendar-dayView-WholeDay',
	            region: 'north',
				layout: 'fit',
				border: false,
	            height: 50,
	            //minSize: 50,
	            //maxSize: 100,
				items: [
                    WholeDayGrid
                ]
	        },{
				id: 'calendar-dayView-TimeGrid',
	            region:'center',
				layout: 'fit',
				border: false,
	            items: [
	                TimeGrid
	            ]
	        }]
	    });
	};
		
	/**
	 * helper function to initalize TimeGrid
	 * 
	 * @param {int} numberOfDays
	 * @param {int} granularity of Grid in miliseconds
	 */
	var mkTimeGridData = function(numberOfDays, gty)
    {
        //var numberOfDays = days.length;
        var TimeAxis = new Array(Date.Const.msDAY / gty);
        var offset = new Date().getTimezoneOffset() * Date.Const.msMINUTE;
        for (var i=0; i<TimeAxis.length; i++) {
            TimeAxis[i] = new Array(numberOfDays+1);
            TimeAxis[i][0] = new Date(i*gty+offset).format('H:i');
        }
        
        return TimeAxis;
    };
	
    DisplayCalendarEvents = function(eventsStore){
		if (!States.GridSized || !States.StoreLoaded) {
			// not ready to draw events!
			return;
		}
		
		var TimeGridBody = TimeGrid.getView().mainBody;
		var WholeDayGridBody = WholeDayGrid.getView().mainBody;
		
		var TimeGridBodyHeight = TimeGridBody.getHeight();
		var dpdt = TimeGridBodyHeight / Date.Const.msDAY;
		
		
		var SimultaneousRegistryGranularity = 5*Date.Const.msMINUTE;
		var SimultaneousRegistryMaxColumns = 10;
		var SimultaneousRegistry = mkTimeGridData(Tine.Calendar.Request.viewMultiplier, SimultaneousRegistryGranularity);
		
		eventsStore.each(function(event){
			// timestamp based calculations are far more easy!
			var rStart = Tine.Calendar.Request.start.getTime();
			var rEnd = Tine.Calendar.Request.end.getTime();
			var eStart = event.data.cal_start.getTime();
			var eEnd = event.data.cal_end.getTime();
			
			if (eStart > rEnd || eEnd < rStart) {
				// skip, out of range!
				return true;
			}
			
			// in which column is the event? (0...n)
            var col = Math.floor((eStart - rStart) / Date.Const.msDAY);
			
			// quick hack for egw1.4 compat
			// in the future the bakcend itself shoud know if it is wholedayevent
			var isWholeDayEvent = (eEnd - eStart) >= (Date.Const.msDAY-Date.Const.msMINUTE-1);
			
			// Simultaneously check
			if (!isWholeDayEvent) {
				var simRegDay = col + 1;
				var simRegStartIdx = Math.floor((eStart - rStart - col * Date.Const.msDAY) / SimultaneousRegistryGranularity);
				var simRegStopIdx = Math.ceil((eEnd - rStart - col * Date.Const.msDAY) / SimultaneousRegistryGranularity) - 1;
				var affectedEvents = [event];
				
				// check which column (j) is free betwwen Idxs 
				for (var j = 0; j < SimultaneousRegistryMaxColumns; j++) {
					var columnFree = true;
					for (var i = simRegStartIdx; i <= simRegStopIdx; i++) {
						if (SimultaneousRegistry[i][simRegDay] == undefined) {
							SimultaneousRegistry[i][simRegDay] = new Array(SimultaneousRegistryMaxColumns);
						}
						if (SimultaneousRegistry[i][simRegDay][j] != undefined) {
							columnFree = false;
							if (affectedEvents.indexOf(SimultaneousRegistry[i][simRegDay][j]) < 0) {
								affectedEvents.push(SimultaneousRegistry[i][simRegDay][j]);
							}
						}
					}
					if (columnFree) 
						break;
				}
				
				// register event in the free column
				for (var i = simRegStartIdx; i <= simRegStopIdx; i++) {
					SimultaneousRegistry[i][simRegDay][j] = event;
				}
				event.layout = {
					column: j,
					maxColumns: 0
				};
				
				// we also need to calculate the maximum of simultaneous events,
				// as this is the differnet number as affectedEvents
				for (var i = simRegStartIdx; i <= simRegStopIdx; i++) {
					var maxColumns = 0;
					for (k = 0; k < SimultaneousRegistryMaxColumns; k++) {
						if (SimultaneousRegistry[i][simRegDay][k] instanceof Object) maxColumns++;
					}
					for (k = 0; k < SimultaneousRegistryMaxColumns; k++) {
						if (SimultaneousRegistry[i][simRegDay][k] instanceof Object && SimultaneousRegistry[i][simRegDay][k].layout.maxColumns < maxColumns) {
							SimultaneousRegistry[i][simRegDay][k].layout.maxColumns = maxColumns;
						}
					}
				}
			}
			
			// top position of event
			var top = TimeGridBody.getTop() + (eStart - rStart - col * Date.Const.msDAY) * dpdt;
			// height of event
			var height = (eEnd - eStart) * dpdt;
			
			// left border of event
            var left = TimeGridBody.getLeft() + dims.timeAxisWidth + col * dims.timeSheetWidth;
			// width of evnet
			var width = dims.timeSheetWidth;
			
			if (!isWholeDayEvent) {
				var e = Ext.get(event.data.cal_id);
                if (!e) {
                    var e = eventTpl.insertAfter(TimeGridBody, event.data, true);
                    e.on('mouseover', function(event, element){
                        //console.log(element);
                    }, this);
                    e.addClass(['x-horizontalTimeView-event', 'x-TimeView-event', 'x-form-text']);
                }
				e.setSize(width, height);
				e.position('absolute', 4, left, top);
				
				//console.log('-> now drawing: '+event.data.cal_id);
				for (var i = 0; i < affectedEvents.length; i++) {
					event = affectedEvents[i];
					e = Ext.get(event.data.cal_id);
					
					//console.log('redrawing: ' + event.data.cal_id);
					//console.log(event.layout);
					
					//layout={column:j, maxColumns
	                ovlWidth = width / event.layout.maxColumns; //affectedEvents.length;
	                ovlLeft = left + event.layout.column*ovlWidth;

					e.setWidth(ovlWidth);
					e.setX(ovlLeft);
				}

			} else {
				var e = Ext.get(event.data.cal_id);
				if (!e) {
                    var e = WholeDayEventTpl.insertAfter(WholeDayGridBody, event.data, true);
                    e.addClass(['x-horizontalTimeView-event', 'x-TimeView-event', 'x-form-text']);
                }
				e.setSize(width, 20);
                e.position('absolute', 4, left, WholeDayGridBody.getTop()+3);
			}
			
		}, this);

        TimeGridBody.setHeight(TimeGridBodyHeight);
		
        // scroll to wday start
		TimeGrid.getView().scroller.setStyle('overflow-x', 'hidden');
        TimeGrid.getView().scroller.dom.scrollTop = 
            Tine.Calendar.Preferences.workDayStart.getTime() * dpdt;
    };
		
    var eventTpl = new Ext.XTemplate(
	    '<div id="{cal_id}">',
		' <table>',
		'  <tr class="x-calendar-dayview-event-header">',
		'    {[values.cal_start.format("H:i")]}',
		'  </tr>',
		'  <tr class="x-calendar-dayview-event-body">',
		'    {cal_title}',
		'  </tr>',
		'  <tr class="x-calendar-dayview-event-footer">',
		'  </tr>',
		' </table>',
		'</div>'
	).compile();
	
	var WholeDayEventTpl = new Ext.XTemplate(
        '<div id="{cal_id}">',
		'  {cal_title}',
        '</div>'
    ).compile();
	
	
	return {
		getMainScreen: function(request){
			initStore(request);
			initTimeGrid(request);
			Tine.Calendar.Request.on('requestchange', function(request){
		        initStore(request);
		        initTimeGrid(request);
		        Tine.Tinebase.MainScreen.setActiveContentPanel(dayViewLayout);
		    },this);
			//return TimeGrid;
			return dayViewLayout;
		}
	}
}();

/**
 * Toolbar Object
 */
Tine.Calendar.ToolBar = function() {
	
	// parsing Tine.Calendar.Request.changeView as handler directly
	// does't work for scopeing ishues!
	var changeView = function(requestedView) {
		Tine.Calendar.Request.changeView(requestedView);
	}
	
	// we generate a new toolbar on each request as tinebase throws our
	// toolbar away when changing apps
	var _generateToolBar = function()
	{
		
		Tine.Calendar.Request.on('requestchange', function(request){
            console.log(request.view);
            //console.log(request.viewMultiplier);
        },this);
		
		return new Ext.Toolbar({
			id: 'Calendar_ToolBar',
			items: [{
				selectedView: 'day',
	            selectedViewMultiplier: 1,
	            text: 'day view',
	            iconCls: 'action_1dayview',
	            xtype: 'tbbtnlockedtoggle',
	            handler: changeView,
	            enableToggle: true,
	            toggleGroup: 'Calendar_Toolbar_tgViews'
			}, {
				selectedView: 'day',
                selectedViewMultiplier: 4,
				text: '4 days view',
				iconCls: 'action_5daysview',
                xtype: 'tbbtnlockedtoggle',
                handler: changeView,
                enableToggle: true,
                toggleGroup: 'Calendar_Toolbar_tgViews'
			}, {
				selectedView: 'day',
                selectedViewMultiplier: 7,
				text: 'week view',
				iconCls: 'action_7daysview',
                xtype: 'tbbtnlockedtoggle',
                handler: changeView,
                enableToggle: true,
                toggleGroup: 'Calendar_Toolbar_tgViews'
			}, {
				selectedView: 'month',
                selectedViewMultiplier: 1,
				text: 'month view',
				iconCls: 'action_monthview',
                xtype: 'tbbtnlockedtoggle',
                handler: changeView,
                enableToggle: true,
                toggleGroup: 'Calendar_Toolbar_tgViews'
			}],
		});
	}
	
	return {
		getToolBar: _generateToolBar
	};
}();

/**
 * @class Ext.ux.ButtonLockedToggle & Ext.ux.tbButtonLockedToggle
 * @extends Ext.Button
 * The normal button, when enableToggle is used and toggleGroup is set correctly, still allows the user
 * to toggle off the toggled button by pressing on it. This class overrides the toggle-method so that
 * the toggled button is impossible to 'untoggle' other than programmatically or as a reaction
 * to any of the other buttons in the group getting toggled on.
 *
 * Toggle is by the way a very strange word when you repeat it enough.
 *
 * @author www.steinarmyhre.com
 * @constructor
 * Identical to Ext.Button and/or Ext.Toolbar.Button except that enableToggle is true by default.
 * @param (Object/Array) config A config object
 */
Ext.ux.ButtonLockedToggle = Ext.extend(Ext.Button,{
    enableToggle: true,

    toggle: function(state){
        if(state == undefined && this.pressed) return;
        state = state === undefined ? !this.pressed : state;
        if(state != this.pressed){
            if(state){
                this.el.addClass("x-btn-pressed");
                this.pressed = true;
                this.fireEvent("toggle", this, true);
            }else{
                this.el.removeClass("x-btn-pressed");
                this.pressed = false;
                this.fireEvent("toggle", this, false);
            };
            if(this.toggleHandler){
                this.toggleHandler.call(this.scope || this, this, state);
            };
        };
    }
})

Ext.ux.tbButtonLockedToggle = Ext.extend(Ext.Toolbar.Button, Ext.ux.ButtonLockedToggle);

Ext.ComponentMgr.registerType('btnlockedtoggle', Ext.ux.ButtonLockedToggle);
Ext.ComponentMgr.registerType('tbbtnlockedtoggle', Ext.ux.tbButtonLockedToggle)



