EGWNameSpace.Felamimail = function() {

	//var grid;

	// private function
	var showGrid = function(_layout) {
		var center = _layout.getRegion('center', false);
		
		// add a div, which will bneehe parent element for the grid
		var bodyTag = Ext.Element.get(document.body);
		var gridDivTag = bodyTag.createChild({tag: 'div',id: 'gridAddressbook',cls: 'x-layout-inactive-content'});
		
		var ds = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'get-data.php'
				}),		
				reader: new Ext.data.JsonReader({
					root: 'results',
					totalProperty: 'totalcount',
					id: 'userid'
				}, 
				[{name: 'userid'},{name: 'lastname'},{name: 'firstname'},{name: 'street'},{name: 'zip'},{name: 'city'},{name: 'birthday'},{name: 'addressbook'}]
				),		
				remoteSort: true
			});
		ds.load();

		var cm = new Ext.grid.ColumnModel([{
				resizable: true,
				id: 'userid',
				header: "ID",
				dataIndex: 'userid',
				width: 30
			},
			{
				resizable: true,
				id: 'lastname',
				header: "lastname",
				dataIndex: 'lastname',
			},
			{
				resizable: true,
				id: 'firstname',
				header: "firstname",
				dataIndex: 'firstname',
				hidden: true
			},
			{
				resizable: true,
				header: "street",
				dataIndex: 'street',
			},
			{
				resizable: true,
				id: 'city',
				header: "zip/city",
				dataIndex: 'city',
			},
			{
				resizable: true,
				header: "birthday",
				dataIndex: 'birthday',
			},
			{
				resizable: true,
				id: 'addressbook',
				header: "addressbook",
				dataIndex: 'addressbook',
		}]);
		
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
				autoExpandColumn: 'lastname'
			});		
		
		// remove the first contentpanel from center region
		center.remove(0);

		grid.render();

		center.add(new Ext.GridPanel(grid));
    }


    // public stuff
    return {
        // public function
        show: function(_layout) {
        	showGrid(_layout);
        }
    }
	
}(); // end of application

// end of file  