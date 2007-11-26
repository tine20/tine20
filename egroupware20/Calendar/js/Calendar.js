Ext.namespace('Egw.Calendar');

Date.Const = {
    msMINUTE : 60*1000,
	msHOUR   : 60*60*1000,
	msDAY    : 24*60*60*1000,
}

Egw.Calendar.today = function()
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
Egw.Calendar.Request = function(){
	this.view = 'day';                                                // {day|month|planer}
    this.viewMultiplier = 1;
    this.start = Egw.Calendar.today;                                  // start Date
    this.end = Egw.Calendar.today.add(Date.MILLI,
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
			this.end = Egw.Calendar.today.add(Date.MILLI,
		        this.viewMultiplier*Date.Const.msDAY-1
		    );
			this.fireEvent('requestchange', this);
		}
    }
	
	Egw.Calendar.Request.superclass.constructor.call(this);
}
Ext.extend(Egw.Calendar.Request, Ext.util.Observable);
Egw.Calendar.Request = new Egw.Calendar.Request();

Egw.Calendar.Preferences = {
	workDayStart: new Date(Date.Const.msHOUR*9),// + new Date().getTimezoneOffset()),
	workDayEnd: new Date(Date.Const.msHOUR*18)// + new Date().getTimezoneOffset())
};

// entry point, required by egwbase
Egw.Calendar.getPanel = function() {
    
    var calPanel =  new Ext.Panel({
        title: 'Calendar',
        html: '<p>Magic Calendar App!</p>',
        border: false
    });
    
    calPanel.on('beforeexpand', function(_calPanel) {
        Egw.Egwbase.MainScreen.setActiveContentPanel(Egw.Calendar.MainScreen.getMainScreen(Egw.Calendar.Request));
        Egw.Egwbase.MainScreen.setActiveToolbar(Egw.Calendar.ToolBar.getToolBar());
    });
    
    return calPanel;
}


Egw.Calendar.MainScreen = function() {
	
	/**
	 * @var {store} events store
	 */
	var store;
	
	/**
	 * @var {grid} time grid
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
		
		with (Egw.Calendar.Request) {
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
	 * 
	 * @param {Egw.Calendar.Request} request
	 */
	var initTimeGrid = function(request)
	{
		States.GridSized = false;
        if (request.view != 'day') {
			throw new Error(request.view + ' not implemeted yet!')
		}
		var nDays = request.viewMultiplier;
		
		var columns = new Array({
            id: 'time',
            header: "",
            width: dims.timeAxisWidth,
            sortable: false,
            dataIndex: 'time'
        });
		
		for (var i=1; i<=nDays; i++) {
			columns.push({
				header: Egw.Egwbase.Common.dateRenderer(request.start.add(Date.DAY, i-1)),
                sortable: false,
				fixed: true,
                dataIndex: 'cdata'
			})
		}
		TimeGrid = new Ext.grid.GridPanel({
			store: new Ext.data.SimpleStore({
				'fields': ['time', 'cdata'],
				'data': mkGridData(Array(nDays))
			}),
			columns: columns,
			sm: new Ext.grid.CellSelectionModel({}),
			//view: new Egw.Calendar.GridView_Days({}),
			iconCls: 'icon-grid',
			border: false
		});
		
		
		TimeGrid.on('resize', function(cmp){
			States.GridSized = false;
			this.MainScreenCmp = cmp;
			
			// resize columns
			var cm = TimeGrid.getColumnModel();
			var nc = cm.getColumnCount();
			var cw = dims.timeSheetWidth = 
			    (
			        cmp.getSize().width - 
				    dims.timeAxisWidth -
				    TimeGrid.getView().scrollOffset
				) / (nc - 1);
				
			for (var i = 1; i < nc; i++) {
				cm.setColumnWidth(i, cw);
			}

			States.GridSized = true;
			DisplayCalendarEvents(store);
		});
	};
		
	var mkGridData = function(days)
    {
        var numberOfDays = days.length;
        var gty = 30*Date.Const.msMINUTE;
        
        var TimeAxis = new Array(Date.Const.msDAY / gty);
        var offset = new Date().getTimezoneOffset() * Date.Const.msMINUTE;
        for (var i=0; i<TimeAxis.length; i++) {
            TimeAxis[i] = new Array(numberOfDays+1);
            TimeAxis[i][0] = new Date(i*gty+offset).format('H:i');
			
			if ( new Date(i*gty+offset).getTime() == Egw.Calendar.Preferences.workDayStart.getTime() ) {
				//TimeAxis[i][0] = 'wday start!';
				Egw.Calendar.wdaystartidx = i;
			}
        }

        return TimeAxis;
    };
	
    DisplayCalendarEvents = function(eventsStore){
		if (!States.GridSized || !States.StoreLoaded) {
			// not ready to draw events!
			return;
		}
		
		var TimeGridBody = TimeGrid.getView().mainBody;
		
		var TimeGridBodyHeight = TimeGridBody.getHeight();
		var dpdt = TimeGridBodyHeight / Date.Const.msDAY;
		
		eventsStore.each(function(event){
			// timestamp based calculations are far more easy!
			var rStart = Egw.Calendar.Request.start.getTime();
			var rEnd = Egw.Calendar.Request.end.getTime();
			var eStart = event.data.cal_start.getTime();
			var eEnd = event.data.cal_end.getTime();
			
			if (eStart > rEnd || eEnd < rStart) {
				// skip, out of range!
				return true;
			}
			
			// in which column is the event? (0...n)
			var col = Math.floor((eStart - rStart) / Date.Const.msDAY);
			// left border of 
			var left = TimeGridBody.getLeft() + dims.timeAxisWidth + col * dims.timeSheetWidth;
			// top position of event
			var top = TimeGridBody.getTop() + (eStart - rStart - col * Date.Const.msDAY) * dpdt;
			// height of event
			var height = (eEnd - eStart) * dpdt;
			
			var e = Ext.get(event.data.cal_id);
			if (!e) {
				var e = eventTpl.insertAfter(TimeGridBody, event.data, true);
				e.addClass(['x-horizontalTimeView-event', 'x-TimeView-event', 'x-form-text']);
			}
			
			e.setSize(dims.timeSheetWidth, height);
			e.position('absolute', 4, left, top);
		}, this);

        TimeGridBody.setHeight(TimeGridBodyHeight);
		
        // scroll to wday start
		TimeGrid.getView().scroller.setStyle('overflow-x', 'hidden');
        TimeGrid.getView().scroller.dom.scrollTop = 
            Egw.Calendar.Preferences.workDayStart.getTime() * dpdt;
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
	);//.compile();
	//eventTpl.compile();
	
	return {
		getMainScreen: function(request){
			initStore(request);
			initTimeGrid(request);
			Egw.Calendar.Request.on('requestchange', function(request){
		        initStore(request);
		        initTimeGrid(request);
		        Egw.Egwbase.MainScreen.setActiveContentPanel(TimeGrid);
		    },this);
			return TimeGrid;
		}
	}
}();

/**
 * Toolbar Object
 */
Egw.Calendar.ToolBar = function() {
	
	// parsing Egw.Calendar.Request.changeView as handler directly
	// does't work for scopeing ishues!
	var changeView = function(requestedView) {
		Egw.Calendar.Request.changeView(requestedView);
	}
	
	// we generate a new toolbar on each request as egwbase throws our
	// toolbar away when changing apps
	var _generateToolBar = function()
	{
		
		Egw.Calendar.Request.on('requestchange', function(request){
            console.log(request.view);
            console.log(request.viewMultiplier);
        },this);
		
		return new Ext.Toolbar({
			id: 'Calendar_ToolBar',
			items: [{
				selectedView: 'day',
	            selectedViewMultiplier: 1,
	            text: '1day view',
	            iconCls: 'action_1dayview',
	            xtype: 'tbbtnlockedtoggle',
	            handler: changeView,
	            enableToggle: true,
	            toggleGroup: 'Calendar_Toolbar_tgViews'
			}, {
				selectedView: 'day',
                selectedViewMultiplier: 5,
				text: '5day view',
				iconCls: 'action_5daysview',
                xtype: 'tbbtnlockedtoggle',
                handler: changeView,
                enableToggle: true,
                toggleGroup: 'Calendar_Toolbar_tgViews'
			}, {
				selectedView: 'day',
                selectedViewMultiplier: 7,
				text: '7day view',
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



