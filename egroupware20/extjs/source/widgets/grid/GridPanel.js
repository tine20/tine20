/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.grid.GridPanel
 * @extends Ext.Panel
 * This class represents the primary interface of a component based grid control.
 * <br><br>Usage:
 * <pre><code>var grid = new Ext.grid.GridPanel({
    store: new Ext.data.Store({
        reader: reader,
        data: xg.dummyData
    }),
    columns: [
        {id:'company', header: "Company", width: 200, sortable: true, dataIndex: 'company'},
        {header: "Price", width: 120, sortable: true, renderer: Ext.util.Format.usMoney, dataIndex: 'price'},
        {header: "Change", width: 120, sortable: true, dataIndex: 'change'},
        {header: "% Change", width: 120, sortable: true, dataIndex: 'pctChange'},
        {header: "Last Updated", width: 135, sortable: true, renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange'}
    ],
    sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
    width:600,
    height:300,
    frame:true,
    title:'Framed with Checkbox Selection and Horizontal Scrolling',
    iconCls:'icon-grid'
});</code></pre>
 * <b>Note:</b> Although this class inherits many configuration options from base classes, some of them 
 * (such as autoScroll, layout, items, etc) won't function as they do with the base Panel class.
 */

Ext.grid.GridPanel = Ext.extend(Ext.Panel, {
    elements:'body',

    /**
     * @cfg {Array} columns An array of columns to auto create a ColumnModel
     */

    /**
     * @cfg {Store} store The Ext.data.Store the grid should use as it's data source
     */

    /**
     * @cfg {Store} cm An Ext.grid.ColumnModel for this grid
     */

    /**
     * @cfg {Object} sm The SelectionModel the grid should use to handle selections (shortcut of selModel)
     */

    /**
     * @cfg {Object} selModel The SelectionModel the grid should use to handle selections
     */

    /**
     * @cfg {Array} columns An array of columns to auto create a ColumnModel
     */

    /**
     * @cfg {Array} columns An array of columns to auto create a ColumnModel
     */

    /**
     * @cfg {Number} minColumnWidth The minimum width a column can be resized to. Defaults to 25.
	 */
	minColumnWidth : 25,

    /**
	 * @cfg {Boolean} monitorWindowResize True to autoSize the grid when the window resizes. Defaults to true.
	 */
	monitorWindowResize : true,

	/**
	 * @cfg {Boolean} maxRowsToMeasure If autoSizeColumns is on, maxRowsToMeasure can be used to limit the number of
	 * rows measured to get a columns size - defaults to 0 (all rows).
	 */
	maxRowsToMeasure : 0,

	/**
	 * @cfg {Boolean} trackMouseOver True to highlight rows when the mouse is over. Default is false.
	 */
	trackMouseOver : true,

	/**
	 * @cfg {Boolean} enableDragDrop True to enable drag and drop of rows.
	 */
	enableDragDrop : false,

	/**
	 * @cfg {Boolean} enableColumnMove True to enable drag and drop reorder of columns.
	 */
	enableColumnMove : true,

	/**
	 * @cfg {Boolean} enableColumnHide True to enable hiding of columns with the header context menu.
	 */
	enableColumnHide : true,

	/**
	 * @cfg {Boolean} enableHdMenu True to enable the drop down button for menu in the headers.
	 */
	enableHdMenu : true,

    /**
	 * @cfg {Boolean} enableRowHeightSync True to manually sync row heights across locked and not locked rows.
	 */
	enableRowHeightSync : false,

	/**
	 * @cfg {Boolean} stripeRows True to stripe the rows. Default is true.
	 */
	stripeRows : true,

	/**
     * @cfg {String} autoExpandColumn The id of a column in this grid that should expand to fill unused space. This id can not be 0.
     */
    autoExpandColumn : false,

    /**
    * @cfg {Number} autoExpandMin The minimum width the autoExpandColumn can have (if enabled).
    * defaults to 50.
    */
    autoExpandMin : 50,

    /**
    * @cfg {Number} autoExpandMax The maximum width the autoExpandColumn can have (if enabled). Defaults to 1000.
    */
    autoExpandMax : 1000,

    /**
	 * @cfg {Object} view The {@link Ext.grid.GridView} used by the grid. This can be set before a call to render().
	 */
	view : null,

	/**
     * @cfg {Object} loadMask An {@link Ext.LoadMask} config or true to mask the grid while loading (defaults to false).
	 */
	loadMask : false,

    /**
     * @cfg {Boolean} disableSelection (defaults to false).
	 */

    // private
    rendered : false,
    // private
    viewReady: false,

    stateEvents: ["columnmove", "columnresize", "sortchange"],
    /**
    * @cfg {Number} maxHeight Sets the maximum height of the grid - ignored if autoHeight is not on.
    */

    /**
     * Configures the text is the drag proxy (defaults to "{0} selected row(s)").
     * {0} is replaced with the number of selected rows.
     * @type String
     */
    ddText : "{0} selected row{1}",

    // private
    initComponent : function(){
        Ext.grid.GridPanel.superclass.initComponent.call(this);

        if(this.columns && (this.columns instanceof Array)){
            this.colModel = new Ext.grid.ColumnModel(this.columns);
            delete this.columns;
        }

        // check and correct shorthanded configs
        if(this.ds){
            this.store = this.ds;
            delete this.ds;
        }
        if(this.cm){
            this.colModel = this.cm;
            delete this.cm;
        }
        if(this.sm){
            this.selModel = this.sm;
            delete this.sm;
        }
        this.store = Ext.StoreMgr.lookup(this.store);

        this.addEvents({
            // raw events
            /**
             * @event click
             * The raw click event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "click" : true,
            /**
             * @event dblclick
             * The raw dblclick event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "dblclick" : true,
            /**
             * @event contextmenu
             * The raw contextmenu event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "contextmenu" : true,
            /**
             * @event mousedown
             * The raw mousedown event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "mousedown" : true,
            /**
             * @event mouseup
             * The raw mouseup event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "mouseup" : true,
            /**
             * @event mouseover
             * The raw mouseover event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "mouseover" : true,
            /**
             * @event mouseout
             * The raw mouseout event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "mouseout" : true,
            /**
             * @event keypress
             * The raw keypress event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "keypress" : true,
            /**
             * @event keydown
             * The raw keydown event for the entire grid.
             * @param {Ext.EventObject} e
             */
            "keydown" : true,

            // custom events
            /**
             * @event cellmousedown
             * Fires before a cell is clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "cellmousedown" : true,
            /**
             * @event rowmousedown
             * Fires before a row is clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Ext.EventObject} e
             */
            "rowmousedown" : true,
            /**
             * @event headermousedown
             * Fires before a header is clicked
             * @param {Grid} this
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "headermousedown" : true,

            /**
             * @event cellclick
             * Fires when a cell is clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "cellclick" : true,
            /**
             * @event celldblclick
             * Fires when a cell is double clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "celldblclick" : true,
            /**
             * @event rowclick
             * Fires when a row is clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Ext.EventObject} e
             */
            "rowclick" : true,
            /**
             * @event rowdblclick
             * Fires when a row is double clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Ext.EventObject} e
             */
            "rowdblclick" : true,
            /**
             * @event headerclick
             * Fires when a header is clicked
             * @param {Grid} this
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "headerclick" : true,
            /**
             * @event headerdblclick
             * Fires when a header cell is double clicked
             * @param {Grid} this
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "headerdblclick" : true,
            /**
             * @event rowcontextmenu
             * Fires when a row is right clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Ext.EventObject} e
             */
            "rowcontextmenu" : true,
            /**
             * @event cellcontextmenu
             * Fires when a cell is right clicked
             * @param {Grid} this
             * @param {Number} rowIndex
             * @param {Number} cellIndex
             * @param {Ext.EventObject} e
             */
            "cellcontextmenu" : true,
            /**
             * @event headercontextmenu
             * Fires when a header is right clicked
             * @param {Grid} this
             * @param {Number} columnIndex
             * @param {Ext.EventObject} e
             */
            "headercontextmenu" : true,
            /**
             * @event bodyscroll
             * Fires when the body element is scrolled
             * @param {Number} scrollLeft
             * @param {Number} scrollTop
             */
            "bodyscroll" : true,
            /**
             * @event columnresize
             * Fires when the user resizes a column
             * @param {Number} columnIndex
             * @param {Number} newSize
             */
            "columnresize" : true,
            /**
             * @event columnmove
             * Fires when the user moves a column
             * @param {Number} oldIndex
             * @param {Number} newIndex
             */
            "columnmove" : true,
            /**
             * @event sortchange
             * Fires when the grid's store sort changes
             * @param {Grid} this
             * @param {Object} sortInfo An object with the keys field and direction
             */
            "sortchange" : true
        });
    },

    // private
    onRender : function(ct, position){
        Ext.grid.GridPanel.superclass.onRender.apply(this, arguments);

        var c = this.body;

        this.el.addClass('x-grid-panel');
        
        var view = this.getView();
        view.init(this);

        c.on("mousedown", this.onMouseDown, this);
        c.on("click", this.onClick, this);
        c.on("dblclick", this.onDblClick, this);
        c.on("contextmenu", this.onContextMenu, this);
        c.on("keydown", this.onKeyDown, this);

        this.relayEvents(c, ["mousedown","mouseup","mouseover","mouseout","keypress"]);

        this.getSelectionModel().init(this);
        this.view.render();
    },

    // private
    initEvents : function(){
        Ext.grid.GridPanel.superclass.initEvents.call(this);

        if(this.loadMask){
            this.loadMask = new Ext.LoadMask(this.bwrap,
                    Ext.apply({store:this.store}, this.loadMask));
        }
    },

    initStateEvents : function(){
        Ext.grid.GridPanel.superclass.initStateEvents.call(this);
        this.colModel.on('hiddenchange', this.saveState, this, {delay: 100});
    },

    applyState : function(state){
        var cm = this.colModel;
        var cs = state.columns;
        if(cs){
            for(var i = 0, len = cs.length; i < len; i++){
                var s = cs[i];
                var c = cm.getColumnById(s.id);
                if(c){
                    c.hidden = s.hidden;
                    c.width = s.width;
                    var oldIndex = cm.getIndexById(s.id);
                    if(oldIndex != i){
                        cm.moveColumn(oldIndex, i);
                    }
                }
            }
        }
        if(state.sort){
            this.store.sort(state.sort.field, state.sort.direction);
        }
    },

    getState : function(){
        var o = {columns: []};
        for(var i = 0, c; c = this.colModel.config[i]; i++){
            o.columns[i] = {
                id: c.id,
                width: c.width
            };
            if(c.hidden){
                o.columns[i].hidden = true;
            }
        }
        var ss = this.store.getSortState();
        if(ss){
            o.sort = ss;
        }
        return o;
    },

    // private
    afterRender : function(){
        Ext.grid.GridPanel.superclass.afterRender.call(this);
        this.view.layout();
        this.viewReady = true;
    },

	/**
	 * Reconfigures the grid to use a different Store and Column Model.
	 * The View will be bound to the new objects and refreshed.
	 * @param {Ext.data.Store} dataSource The new {@link Ext.data.Store} object
	 * @param {Ext.grid.ColumnModel} The new {@link Ext.grid.ColumnModel} object
	 */
    reconfigure : function(store, colModel){
        if(this.loadMask){
            this.loadMask.destroy();
            this.loadMask = new Ext.LoadMask(this.body,
                    Ext.apply({store:store}, this.loadMask));
        }
        this.view.bind(store, colModel);
        this.store = store;
        this.colModel = colModel;
        if(this.rendered){
            this.view.refresh(true);
        }
    },

    // private
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    },

    // private
    onDestroy : function(){
        if(this.loadMask){
            this.loadMask.destroy();
        }
        if(this.rendered){
            var c = this.body;
            c.removeAllListeners();
            this.view.destroy();
            c.update("");
        }
        this.colModel.purgeListeners();
        Ext.grid.GridPanel.superclass.onDestroy.call(this);
    },

    // private
    processEvent : function(name, e){
        this.fireEvent(name, e);
        var t = e.getTarget();
        var v = this.view;
        var header = v.findHeaderIndex(t);
        if(header !== false){
            this.fireEvent("header" + name, this, header, e);
        }else{
            var row = v.findRowIndex(t);
            var cell = v.findCellIndex(t);
            if(row !== false){
                this.fireEvent("row" + name, this, row, e);
                if(cell !== false){
                    this.fireEvent("cell" + name, this, row, cell, e);
                }
            }
        }
    },

    // private
    onClick : function(e){
        this.processEvent("click", e);
    },

    // private
    onMouseDown : function(e){
        this.processEvent("mousedown", e);
    },

    // private
    onContextMenu : function(e, t){
        this.processEvent("contextmenu", e);
    },

    // private
    onDblClick : function(e){
        this.processEvent("dblclick", e);
    },

    // private
    walkCells : function(row, col, step, fn, scope){
        var cm = this.colModel, clen = cm.getColumnCount();
        var ds = this.store, rlen = ds.getCount(), first = true;
        if(step < 0){
            if(col < 0){
                row--;
                first = false;
            }
            while(row >= 0){
                if(!first){
                    col = clen-1;
                }
                first = false;
                while(col >= 0){
                    if(fn.call(scope || this, row, col, cm) === true){
                        return [row, col];
                    }
                    col--;
                }
                row--;
            }
        } else {
            if(col >= clen){
                row++;
                first = false;
            }
            while(row < rlen){
                if(!first){
                    col = 0;
                }
                first = false;
                while(col < clen){
                    if(fn.call(scope || this, row, col, cm) === true){
                        return [row, col];
                    }
                    col++;
                }
                row++;
            }
        }
        return null;
    },

    // private
    getSelections : function(){
        return this.selModel.getSelections();
    },

    // private
    onResize : function(){
        Ext.grid.GridPanel.superclass.onResize.apply(this, arguments);
        if(this.viewReady){
            this.view.layout();
        }
    },

    /**
     * Returns the grid's underlying element.
     * @return {Element} The element
     */
    getGridEl : function(){
        return this.body;
    },

    // private for compatibility, overridden by editor grid
    stopEditing : function(){},

    /**
     * Returns the grid's SelectionModel.
     * @return {SelectionModel}
     */
    getSelectionModel : function(){
        if(!this.selModel){
            this.selModel = new Ext.grid.RowSelectionModel(
                    this.disableSelection ? {selectRow: Ext.emptyFn} : null);
        }
        return this.selModel;
    },

    /**
     * Returns the grid's DataSource.
     * @return {DataSource}
     */
    getStore : function(){
        return this.store;
    },

    /**
     * Returns the grid's ColumnModel.
     * @return {ColumnModel}
     */
    getColumnModel : function(){
        return this.colModel;
    },

    /**
     * Returns the grid's GridView object.
     * @return {GridView}
     */
    getView : function(){
        if(!this.view){
            this.view = new Ext.grid.GridView(this.viewConfig);
        }
        return this.view;
    },
    /**
     * Called to get grid's drag proxy text, by default returns this.ddText.
     * @return {String}
     */
    getDragDropText : function(){
        var count = this.selModel.getCount();
        return String.format(this.ddText, count, count == 1 ? '' : 's');
    }
});
Ext.reg('grid', Ext.grid.GridPanel);
