/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.ux.grid');

/**
 * @class Ext.ux.grid.CheckColumn
 * @extends Ext.util.Observable
 * GridPanel plugin to add a column with check boxes to a grid.
 * <p>Example usage:</p>
 * <pre><code>
// create the column
var checkColumn = new Ext.grid.CheckColumn({
   header: 'Indoor?',
   dataIndex: 'indoor',
   id: 'check',
   width: 55
});

// add the column to the column model
var cm = new Ext.grid.ColumnModel([{
       header: 'Foo',
       ...
    },
    checkColumn
]);

// create the grid
var grid = new Ext.grid.EditorGridPanel({
    ...
    cm: cm,
    plugins: [checkColumn], // include plugin
    ...
});
 * </code></pre>
 * In addition to storing a Boolean value within the record data, this
 * class toggles a css class between <tt>'x-grid3-check-col'</tt> and
 * <tt>'x-grid3-check-col-on'</tt> to alter the background image used for
 * a column.
 */
Ext.ux.grid.CheckColumn = function(config){
    Ext.apply(this, config);
    if(!this.id){
        this.id = Ext.id();
    }

    this.addEvents(
        /**
         * @event beforecheckchange
         * Fires before a check state changes
         * @param {Ext.ux.grid.CheckColumn} this
         * @param {boolean} newvalue
         * @param {boolean} oldvalue
         * @param {Ext.data.Record} record
         */
        'beforecheckchange',
        /**
         * @event checkchange
         * Fires when a check state changes
         * @param {Ext.ux.grid.CheckColumn} this
         * @param {boolean} newvalue
         * @param {boolean} oldvalue
         * @param {Ext.data.Record} record
         */
        'checkchange'
    );

    this.renderer = this.renderer.createDelegate(this);
    Ext.ux.grid.CheckColumn.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.grid.CheckColumn, Ext.util.Observable, {
    readOnly: false,

    init : function(grid){
        this.grid = grid;
        this.grid.on('render', function(){
            var view = this.grid.getView();
            view.mainBody.on('mousedown', this.onMouseDown, this);
        }, this);
    },

    /**
     * Validate action is valid or not here
     *
     * If returned false, the setting won't be changed.
     *
     * @param checkbox
     * @param record
     * @return {boolean}
     */
    onBeforeCheck: function(checkbox, record) {
        if (this.fireEvent('beforecheckchange', this, !record.data[this.dataIndex], record.data[this.dataIndex], record) === false) {
            return false;
        }

        return !this.grid.readOnly && ! this.readOnly;
    },

    onMouseDown : function(e, t){
        if(Ext.fly(t).hasClass(this.createId())){
            e.stopEvent();
            var index = this.grid.getView().findRowIndex(t);
            var record = this.grid.store.getAt(index);

            if (this.onBeforeCheck(this, record)) {
                record.set(this.dataIndex, !record.data[this.dataIndex]);
                this.fireEvent('checkchange', this, record.data[this.dataIndex], !record.data[this.dataIndex], record);
            }
        }
    },

    renderer : function(v, p, record){
        p.css += ' x-grid3-check-col-td';
        return String.format('<div class="x-grid3-check-col{0} {1}">&#160;</div>', +v ? '-on' : '', this.createId());
    },
    
    createId : function(){
        return 'x-grid3-cc-' + this.id;
    }
});

// register ptype
Ext.preg('checkcolumn', Ext.ux.grid.CheckColumn);

// backwards compat
Ext.grid.CheckColumn = Ext.ux.grid.CheckColumn;