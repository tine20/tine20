// namespace object
var EGWNameSpace = EGWNameSpace || {};

// blank image
Ext.BLANK_IMAGE_URL = '/extjs/resources/images/default/s.gif';

EGWNameSpace.Asterisk = function() {

	//var grid;

	// private function
	var showClassesGrid = function(_layout) {
		var center = _layout.getRegion('center', false);
		
		// add a div, which will be the parent element for the grid
		var bodyTag = Ext.Element.get(document.body);
		var gridDivTag = bodyTag.createChild({tag: 'div',id: 'gridAsterisk',cls: 'x-layout-inactive-content'});
		
		// create the Data Store
		var ds = new Ext.data.JsonStore({
			url: 'jsonrpc.php',
			baseParams: {application:'Asterisk_Json', func:'getData', datatype:'classes'},
			root: 'results',
			totalProperty: 'totalcount',
			id: 'class_id',
			fields: [
				{name: 'class_id'},
				{name: 'model'},
				{name: 'description'},
				{name: 'config_id'},
				{name: 'setting_id'},
				{name: 'software_id'},
			],
			// turn on remote sorting
			remoteSort: true
		});

		ds.setDefaultSort('class_id', 'desc');

		ds.load();

		var cm = new Ext.grid.ColumnModel([{
			resizable: true,
			id: 'class_id', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
			header: "ID",
			dataIndex: 'class_id',
			width: 30
		},{
			resizable: true,
			id: 'model',
			header: "model",
			dataIndex: 'model',
			width: 250,
			//renderer: renderLastNamePlain
		},{
			resizable: true,
			id: 'description',
			header: 'description',
			dataIndex: 'description',
			//width: 150
		},{
			resizable: true,
			id: 'config_id',
			header: 'config_id',
			dataIndex: 'config_id',
			//width: 250,
			//renderer: renderLastNamePlain,
			hidden: false
		},{
			resizable: true,
			id: 'setting_id',
			header: 'setting_id',
			dataIndex: 'setting_id',
			//width: 250,
			//renderer: renderLastNamePlain,
			hidden: false
		},{
			resizable: true,
			id: 'software_id',
			header: 'software_id',
			dataIndex: 'software_id',
			//width: 250,
			//renderer: renderLastNamePlain,
			hidden: false
		}]);
	
		cm.defaultSortable = true; // by default columns are sortable

		var grid = new Ext.grid.Grid(gridDivTag, {
			ds: ds,
			cm: cm,
			autoSizeColumns: false,
			selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
			enableColLock:false,
			//monitorWindowResize: true,
			loadMask: true,
			enableDragDrop:true,
			ddGroup: 'TreeDD',
			autoExpandColumn: 'description'
		});

		// remove the first contentpanel from center region
		center.remove(0);

		grid.render();

		var gridHeader = grid.getView().getHeaderPanel(true);
		
		// add a paging toolbar to the grid's footer
		var pagingHeader = new Ext.PagingToolbar(gridHeader, ds, {
			pageSize: 50,
			displayInfo: true,
			displayMsg: 'Displaying classes {0} - {1} of {2}',
			emptyMsg: "No class to display"
		});

		center.add(new Ext.GridPanel(grid));
	}
	
	var showPhonesGrid = function(_layout) {
		var center = _layout.getRegion('center', false);
		
		// add a div, which will be the parent element for the grid
		var bodyTag = Ext.Element.get(document.body);
		var gridDivTag = bodyTag.createChild({tag: 'div',id: 'gridAsterisk',cls: 'x-layout-inactive-content'});
		
		// create the Data Store
		var ds = new Ext.data.JsonStore({
			url: 'jsonrpc.php',
			baseParams: {application:'Asterisk_Json', datatype:'phones', func:'getData'},
			root: 'results',
			totalProperty: 'totalcount',
			id: 'phone_id',
			fields: [
				{name: 'phone_id'},
				{name: 'macaddress'},
				{name: 'phonemodel'},
				{name: 'phoneswversion'},
				{name: 'phoneipaddress'},
				{name: 'lastmodify'},
				{name: 'class_id'},
				{name: 'description'}
			],
			// turn on remote sorting
			remoteSort: true
		});

		ds.setDefaultSort('macaddress', 'desc');

		ds.load();

		var cm = new Ext.grid.ColumnModel([{
			resizable: true,
			id: 'phone_id', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
			header: "ID",
			dataIndex: 'phone_id',
			width: 30
		},{
			resizable: true,
			id: 'macaddress',
			header: "macaddress",
			dataIndex: 'macaddress'
			//width: 250,
			//renderer: renderLastNamePlain
		},{
			resizable: true,
			id: 'description',
			header: 'description',
			dataIndex: 'description'
			//width: 150
		},{
			resizable: true,
			id: 'phonemodel',
			header: 'phonemodel',
			dataIndex: 'phonemodel'
			//width: 250,
			//renderer: renderLastNamePlain
		},{
			resizable: true,
			id: 'phoneswversion',
			header: 'phoneswversion',
			dataIndex: 'phoneswversion'
			//width: 150
		},{
			resizable: true,
			id: 'phoneipaddress',
			header: 'phoneipaddress',
			dataIndex: 'phoneipaddress',
			hidden: true
			//width: 150
			//renderer: renderCityPlain
		},{
			resizable: true,
			id: 'lastmodify',
			header: 'lastmodify',
			dataIndex: 'lastmodify'
			//width: 100
		},{
			resizable: true,
			id: 'class_id',
			header: 'classid',
			dataIndex: 'class_id'
			//width: 450,
			//renderer: renderLastNamePlain,
		}]);
	
		cm.defaultSortable = true; // by default columns are sortable

		var grid = new Ext.grid.Grid(gridDivTag, {
			ds: ds,
			cm: cm,
			autoSizeColumns: false,
			selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
			enableColLock:false,
			//monitorWindowResize: true,
			loadMask: true,
			enableDragDrop:true,
			ddGroup: 'TreeDD',
			autoExpandColumn: 'description'
		});

		// remove the first contentpanel from center region
		center.remove(0);

		grid.render();

		var gridHeader = grid.getView().getHeaderPanel(true);
		
		// add a paging toolbar to the grid's footer
		var pagingHeader = new Ext.PagingToolbar(gridHeader, ds, {
			pageSize: 50,
			displayInfo: true,
			displayMsg: 'Displaying phone {0} - {1} of {2}',
			emptyMsg: "No phones to display"
		});

		center.add(new Ext.GridPanel(grid));
	}

	var showSoftwareGrid = function(_layout) {
		var center = _layout.getRegion('center', false);
		
		// add a div, which will be the parent element for the grid
		var bodyTag = Ext.Element.get(document.body);
		var gridDivTag = bodyTag.createChild({tag: 'div',id: 'gridAsterisk',cls: 'x-layout-inactive-content'});
		
		// create the Data Store
		var ds = new Ext.data.JsonStore({
			url: 'jsonrpc.php',
			baseParams: {application:'Asterisk_Json', func:'getData', datatype:'software'},
			root: 'results',
			totalProperty: 'totalcount',
			id: 'software_id',
			fields: [
				{name: 'software_id'},
				{name: 'phonemodel'},
				{name: 'softwareimage'},
				{name: 'description'}
			],
			// turn on remote sorting
			remoteSort: true
		});

		ds.setDefaultSort('softwareimage', 'desc');

		ds.load();

		var cm = new Ext.grid.ColumnModel([{
			resizable: true,
			id: 'software_id', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
			header: "ID",
			dataIndex: 'software_id',
			width: 30
		},{
			resizable: true,
			id: 'softwareimage',
			header: "softwareimage",
			dataIndex: 'softwareimage',
			width: 250
			//renderer: renderLastNamePlain
		},{
			resizable: true,
			id: 'description',
			header: 'description',
			dataIndex: 'description'
			//width: 150
		},{
			resizable: true,
			id: 'phonemodel',
			header: 'phonemodel',
			dataIndex: 'phonemodel',
			//width: 250,
			//renderer: renderLastNamePlain,
			hidden: false
		}]);
	
		cm.defaultSortable = true; // by default columns are sortable

		var grid = new Ext.grid.Grid(gridDivTag, {
			ds: ds,
			cm: cm,
			autoSizeColumns: false,
			selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
			enableColLock:false,
			//monitorWindowResize: true,
			loadMask: true,
			enableDragDrop:true,
			ddGroup: 'TreeDD',
			autoExpandColumn: 'description'
		});

		// remove the first contentpanel from center region
		center.remove(0);

		grid.render();

		var gridHeader = grid.getView().getHeaderPanel(true);
		
		// add a paging toolbar to the grid's footer
		var pagingHeader = new Ext.PagingToolbar(gridHeader, ds, {
			pageSize: 50,
			displayInfo: true,
			displayMsg: 'Displaying software {0} - {1} of {2}',
			emptyMsg: "No software to display"
		});

		center.add(new Ext.GridPanel(grid));
	}
	
	// public stuff
	return {
		// public function
		show: function(_layout, _node) {
			//console.log(_node.attributes.datatype);
			switch(_node.attributes.datatype) {
				case 'classes':
					showClassesGrid(_layout);
					
					break;

				case 'overview':
					break;
					
				case 'phones':
					showPhonesGrid(_layout);
					
					break;

				case 'software':
					showSoftwareGrid(_layout);
					
					break;
			}
		}
	}
	
}(); // end of application

// initialize the application
//Ext.onReady(EGWNameSpace.app.init, EGWNameSpace.app, true);

// run public functions after delay
//EGWNameSpace.app.pubFun.defer(60000, EGWNameSpace.app);
//EGWNameSpace.app.privPubFun.defer(65000, EGWNameSpace.app);

// end of file  