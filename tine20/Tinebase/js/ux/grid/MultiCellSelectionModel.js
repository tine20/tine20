/**
 * Original version by harley.333
 * Updated 5/6/2011 by taylorbarstow
 *
 * FOUND HERE: http://www.sencha.com/forum/showthread.php?53118-Multiple-Cells-Selection-Model
 *
 * @licence @see https://www.sencha.com/legal/terms-of-use/
 *   Code posted on Sencha Forum is made available as is and we license to customers any
 *   rights we may have to such code under our commercial license agreement. This Code is
 *   not officially supported. Any such code is subject to the restrictions of the license
 *   for the related Sencha product.
 *
 * @class Ext.ux.grid.MultiCellSelectionModel
 * @extends Ext.grid.AbstractSelectionModel
 * Supports multiple selections and keyboard selection/navigation.
 * @constructor
 * @param {Object} config
 */
Ext.ux.grid.MultiCellSelectionModel = function(config){
    Ext.apply(this, config);
    this.selections = [];

    this.last = false;
    this.lastActive = false;

    this.addEvents(
        /**
         * @event selectionchange
         * Fires when the selection changes
         * @param {SelectionModel} this
         * @param {Array} selections A multi-dimensional array containing the indices of all selected cells ([[0,0],[1,1]])
         */
        "selectionchange",
        /**
         * @event beforecellselect
         * Fires when a cell is being selected, return false to cancel.
         * @param {Ext.ux.grid.MultiCellSelectionModel} this
         * @param {Array} cellInfo An array of cell indices ([rowIndex, columnIndex])
         * @param {Boolean} keepExisting False if other selections will be cleared
         */
        "beforecellselect",
        /**
         * @event cellselect
         * Fires when a cell is selected.
         * @param {Ext.ux.grid.MultiCellSelectionModel} this
         * @param {Array} cellInfo An array of cell indices ([rowIndex, columnIndex])
         */
        "cellselect",
        /**
         * @event celldeselect
         * Fires when a cell is deselected.
         * @param {Ext.ux.grid.MultiCellSelectionModel} this
         * @param {Array} cellInfo An array of cell indices ([rowIndex, columnIndex])
         */
        "celldeselect"
    );

    Ext.ux.grid.MultiCellSelectionModel.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.grid.MultiCellSelectionModel, Ext.grid.AbstractSelectionModel,  {
    /**
     * @cfg {Boolean} singleSelect
     * True to allow selection of only one cell at a time (defaults to false)
     */
    singleSelect : false,

    /**
     * @cfg {Boolean} moveEditorOnEnter
     * False to turn off moving the editor to the next cell when the enter key is pressed
     */

    // private
    initEvents : function(){

        if(!this.grid.enableDragDrop && !this.grid.enableDrag){
            this.grid.on("cellmousedown", this.handleMouseDown, this);
        }else{ // allow click to work like normal
            this.grid.on("cellclick", function(grid, rowIndex, columnIndex, e) {
                if(e.button === 0 && !e.shiftKey && !e.ctrlKey) {
                    this.selectCell([rowIndex, columnIndex], false);
                    grid.view.focusCell(rowIndex, columnIndex);
                }
            }, this);
        }

        this.rowNav = new Ext.KeyNav(this.grid.getGridEl(), {
            "up" : function(e) {
                if (this.last == false) {
                    this.selectCell([0, 0]);
                } else if (this.lastActive[0] == 0) {
                    if (!e.shiftKey) {
                        var row = this.lastActive[0], col = this.lastActive[1];
                        this.clearSelections();
                        this.selectCell([row, col]);
                        this.grid.getView().focusCell(row, col);
                    }
                    return;
                } else {
                    var row, col;
                    row = this.lastActive[0] - 1;
                    col = this.lastActive[1];
                    if (!e.shiftKey) {
                        this.clearSelections();
                        this.selectCell([row, col]);
                    } else {
                        var last = this.last
                        this.selectRange(this.last, [row, col]);
                        this.last = last;
                    }
                    this.grid.getView().focusCell(row, col);
                    this.lastActive = [row, col];
                }
            },
            "down" : function(e) {
                if (this.last == false) {
                    this.selectCell([0, 0]);
                } else if (this.lastActive[0] == this.grid.getStore().getCount() - 1) {
                    if (!e.shiftKey) {
                        var row = this.lastActive[0], col = this.lastActive[1];
                        this.clearSelections();
                        this.selectCell([row, col]);
                        this.grid.getView().focusCell(row, col);
                    }
                    return;
                } else {
                    var row, col;
                    row = this.lastActive[0] + 1;
                    col = this.lastActive[1];
                    if (!e.shiftKey) {
                        this.clearSelections();
                        this.selectCell([row, col]);
                    } else {
                        var last = this.last
                        this.selectRange(this.last, [row, col]);
                        this.last = last;
                    }
                    this.grid.getView().focusCell(row, col);
                    this.lastActive = [row, col];
                }
            },
            "left" : function(e) {
                if (this.last == false) {
                    this.selectCell([0, 0]);
                } else if (this.lastActive[1] == 0) {
                    if (!e.shiftKey) {
                        var row = this.lastActive[0], col = this.lastActive[1];
                        this.clearSelections();
                        this.selectCell([row, col]);
                        this.grid.getView().focusCell(row, col);
                    }
                    return;
                } else {
                    var row, col;
                    row = this.lastActive[0];
                    col = this.lastActive[1] - 1;
                    if (!e.shiftKey) {
                        this.clearSelections();
                        this.selectCell([row, col]);
                    } else {
                        var last = this.last
                        this.selectRange(this.last, [row, col]);
                        this.last = last;
                    }
                    this.grid.getView().focusCell(row, col);
                    this.lastActive = [row, col];
                }
            },
            "right" : function(e) {
                if (this.last == false) {
                    this.selectCell([0, 0]);
                } else if (this.lastActive[1] == this.grid.getColumnModel().getColumnCount() - 1) {
                    if (!e.shiftKey) {
                        var row = this.lastActive[0], col = this.lastActive[1];
                        this.clearSelections();
                        this.selectCell([row, col]);
                        this.grid.getView().focusCell(row, col);
                    }
                    return;
                } else {
                    var row, col;
                    row = this.lastActive[0];
                    col = this.lastActive[1] + 1;
                    if (!e.shiftKey) {
                        this.clearSelections();
                        this.selectCell([row, col]);
                    } else {
                        var last = this.last
                        this.selectRange(this.last, [row, col]);
                        this.last = last;
                    }
                    this.grid.getView().focusCell(row, col);
                    this.lastActive = [row, col];
                }
            },
            scope: this
        });

        var view = this.grid.view;
        view.on("refresh", this.onRefresh, this);
        view.on("rowremoved", this.onRemove, this);
    },

    // private
    onRefresh : function(){
        this.clearSelections();
    },

    // private
    onRemove : function(v, index, r){
        this.deselectRange([index, 0], [index, this.grid.getColumnModel().getColumnCount()]);
    },

    /**
     * Gets the number of selected cells.
     * @return {Number}
     */
    getCount : function(){
        return this.selections.length;
    },

    /**
     * Selects the cell to the right of the last selected cell.
     * @param {Boolean} keepExisting (optional) True to keep existing selections
     * @return {Boolean} True if selection is successful, else false
     */
    selectRight : function(keepExisting){
        if(this.hasNext()){
            var row, col = this.last[1];
            if (col == this.grid.getColumnModel().getColumnCount() - 1) {
                row = this.last[0] + 1;
                col = 0;
            } else {
                row = this.last[0];
                col += 1;
            }

            this.selectCell([row, col], keepExisting);
            this.grid.getView().focusCell(this.last[0], this.last[1]);
            return true;
        }
        return false;
    },

    /**
     * Selects the cell underneath the last selected cell.
     * @param {Boolean} keepExisting (optional) True to keep existing selections
     * @return {Boolean} True if selection is successful, else false
     */
    selectDown : function(keepExisting) {
        var r, cols = this.grid.getColumnModel().getColumnCount();
        for (var i = 0; i < cols; i++) {
            r = this.selectRight(keepExisting);
            if (!r) break;
        }
        return r;
    },

    /**
     * Selects the cell above the last selected cell.
     * @param {Boolean} keepExisting (optional) True to keep existing selections
     * @return {Boolean} True if selection is successful, else false
     */
    selectUp : function(keepExisting) {
        var r, cols = this.grid.getColumnModel().getColumnCount();
        for (var i = 0; i < cols; i++) {
            r = this.selectLeft(keepExisting);
            if (!r) break;
        }
        return r;
    },

    /**
     * Selects the cell to the left of the last selected cell.
     * @param {Boolean} keepExisting (optional) True to keep existing selections
     * @return {Boolean} True if selection is successful, else false
     */
    selectLeft : function(keepExisting) {
        if (this.hasPrevious()) {
            var row, col = this.last[1];
            if (col == 0) {
                row = this.last[0] - 1;
                col = this.grid.getColumnModel().getColumnCount() - 1;
            } else {
                row = this.last[0];
                col -= 1;
            }

            this.selectCell([row, col], keepExisting);
            this.grid.getView().focusCell(this.last[0], this.last[1]);
            return true;
        }
        return false;
    },

    /**
     * Returns true if there is a next cell to select
     * @return {Boolean}
     */
    hasNext : function() {
        return this.last !== false && ((this.last[0] + 1) < this.grid.store.getCount() || (this.last[1] + 1) < this.grid.getColumnModel().getColumnCount());
    },

    /**
     * Returns true if there is a previous cell to select
     * @return {Boolean}
     */
    hasPrevious : function(){
        return this.last !== false && (this.last[0] != 0 || this.last[1] != 0);
    },


    /**
     * Returns the selected cell indices
     * @return {Array} Array of cell indices ([rowIndex, columnIndex])
     */
    getSelections : function() {
        return [].concat(this.selections);
    },

    /**
     * Returns the first selected cell index.
     * @return {Array} An array containing the row and column indexes of the first selected cell, or null if none selected.
     */
    getSelectedCell : function() {
        return this.selections.length > 0 ? [].concat(this.selections[0]) : null;
    },

    /**
     * Calls the passed function with each selection. If the function returns false, iteration is
     * stopped and this function returns false. Otherwise it returns true.
     * @param {Function} fn
     * @param {Object} scope (optional)
     * @return {Boolean} true if all selections were iterated
     */
    each : function(fn, scope) {
        var s = this.getSelections();
        for (var i = 0, len = s.length; i < len; i++) {
            if (fn.call(scope || this, s[i], i) === false) {
                return false;
            }
        }
        return true;
    },

    /**
     * Clears all selections.
     */
    clearSelections : function(){
        if(this.locked) return;
        for (var i = this.selections.length - 1; i >= 0 ; i--) {
            this.deselectCell(this.selections[i]);
        }
        this.selections = [];
        this.last = false;
    },


    /**
     * Selects all cells.
     */
    selectAll : function(){
        if(this.locked) return;
        this.selections = [];
        var row, col;
        var rowCount = this.grid.GetStore().getCount()
        var colCount = this.grid.GetColumnModel().getColumnCount()
        for(row = 0; row < rowCount; row++){
            for(col = 0; col < colCount; col++){
                this.selectCell([row, cell], true);
            }
        }
    },

    /**
     * Returns True if there is a selection.
     * @return {Boolean}
     */
    hasSelection : function(){
        return this.selections.length > 0;
    },

    /**
     * Returns True if the specified cell is selected.
     * @param {Array/Record} record The cell-index ([rowIndex, columnIndex]) to check
     * @return {Boolean}
     */
    isSelected : function(index){
        var s = this.selections;
        for (var i = 0; i < s.length; i++) {
            if (s[i][0] == index[0] && s[i][1] == index[1]) {
                return true;
            }
        }
        return false;
    },

    // private
    handleMouseDown : function(g, rowIndex, columnIndex, e){
        if(e.button !== 0 || this.isLocked()){
            return;
        };
        var view = this.grid.getView();
        if(e.shiftKey && this.last !== false){
            var last = this.last;
            this.selectRange(last, [rowIndex, columnIndex], e.ctrlKey);
            this.last = last; // reset the last
            this.lastActive = [rowIndex, columnIndex];
            view.focusCell(rowIndex, columnIndex);
        }else{
            var isSelected = this.isSelected([rowIndex, columnIndex]);
            if(e.ctrlKey && isSelected){
                this.deselectCell([rowIndex, columnIndex]);
            }else if(!isSelected || this.getCount() > 1){
                this.selectCell([rowIndex, columnIndex], e.ctrlKey || e.shiftKey);
                view.focusCell(rowIndex, columnIndex);
            }
        }
    },

    /**
     * Selects multiple cells.
     * @param {Array} cells Array of the indices ([rowIndex, columnIndex]) of the cells to select
     * @param {Boolean} keepExisting (optional) True to keep existing selections (defaults to false)
     */
    selectCells : function(cells, keepExisting){
        if(!keepExisting){
            this.clearSelections();
        }
        for(var i = 0, len = cells.length; i < len; i++){
            this.selectCell(cells[i], true);
        }
    },

    /**
     * Selects a range of cells. All cells in between startCell and endCell are also selected.
     * @param {Array} startCell The index of the first cell ([rowIndex, columnIndex]) in the range
     * @param {Array} endCell The index of the last cell ([rowIndex, columnIndex]) in the range
     * @param {Boolean} keepExisting (optional) True to retain existing selections
     */
    selectRange : function(startCell, endCell, keepExisting){
        if(this.locked) return;
        if(!keepExisting){
            this.clearSelections();
        }
        var row, col, colCount;
        var startRow = startCell[0];
        var startCol = startCell[1];
        var endRow = endCell[0];
        var endCol = endCell[1];
        if (endRow < startRow) {
            // flip 'em
            row = endRow;
            endRow = startRow;
            startRow = row;
        }
        if (endCol < startCol) {
            // flip 'em
            col = endCol;
            endCol = startCol;
            startCol = col;
        }
        for (row = startRow; row <= endRow; row++) {
            for (col = startCol; col <= endCol; col++) {
                this.selectCell([row, col], true);
            }
        }
    },

    /**
     * Deselects a range of cells. All cells in between startCell and endCell are also deselected.
     * @param {Array} startCell The index of the first cell ([rowIndex, columnIndex]) in the range
     * @param {Array} endCell The index of the last cell ([rowIndex, columnIndex]) in the range
     */
    deselectRange : function(startCell, endCell, preventViewNotify) {
        if(this.locked) return;
        var row, col, colCount;
        var startRow = startCell[0];
        var startCol = startCell[1];
        var endRow = endCell[0];
        var endCol = endCell[1];
        if (endRow < startRow) {
            // flip 'em
            row = endRow;
            endRow = startRow;
            startRow = row;
        }
        if (endCol < startCol) {
            // flip 'em
            col = endCol;
            endCol = startCol;
            startCol = col;
        }
        for (row = startRow; row <= endRow; row++) {
            for (col = startCol; col <= endCol; col++) {
                this.deselectCell([row, col], preventViewNotify);
            }
        }
    },

    /**
     * Selects a cell.
     * @param {Array} cell The index of the cell ([rowIndex, columnIndex]) to select
     * @param {Boolean} keepExisting (optional) True to keep existing selections
     */
    selectCell : function(index, keepExisting, preventViewNotify){
        if (this.locked) return;
        if (this.isSelected(index)) return;
        var row = index[0];
        var col = index[1];
        if (row < 0 || row >= this.grid.store.getCount()) return;
        if (col < 0 || col >= this.grid.getColumnModel().getColumnCount()) return;

        if (this.fireEvent("beforecellselect", this, index, keepExisting) !== false) {
            if (!keepExisting || this.singleSelect) {
                this.clearSelections();
            }
            this.selections.push(index);
            this.last = this.lastActive = index;
            if(!preventViewNotify) {
                this.grid.getView().onCellSelect(row, col);
            }
            this.fireEvent("cellselect", this, index);
            this.fireEvent("selectionchange", this, [].concat(this.selections));
        }
    },

    /**
     * Deselects a cell.
     * @param {Array} cell The index of the cell ([rowIndex, columnIndex]) to deselect
     */
    deselectCell : function(index, preventViewNotify){
        if (this.locked) return;
        if (this.last[0] == index[0] && this.last[1] == index[1]) {
            this.last = false;
        }
        if (this.lastActive[0] == index[0] && this.lastActive[1] == index[1]) {
            this.lastActive = false;
        }

        var s = this.selections;
        for (var i = 0; i < s.length; i++) {
            if (s[i][0] == index[0] && s[i][1] == index[1]) {
                this.selections.remove(s[i]);
                if (!preventViewNotify) {
                    this.grid.getView().onCellDeselect(index[0], index[1]);
                }
                this.fireEvent("celldeselect", this, index);
                this.fireEvent("selectionchange", this, [].concat(this.selections));
                return;
            }
        }

    },

    // private
    acceptsNav : function(row, col, cm){
        return !cm.isHidden(col) && cm.isCellEditable(col, row);
    },

    // private
    onEditorKey : function(field, e){
        var k = e.getKey(), newCell, g = this.grid, ed = g.activeEditor;
        var shift = e.shiftKey;
        if (k == e.TAB || k == e.ENTER) {
            e.stopEvent();
            g.stopEditing();
            this.clearSelections();

            var row, col, delta = shift ? -1 : 1;
            if (k == e.TAB) {
                newCell = g.walkCells(ed.row, ed.col+delta, delta, this.acceptsNav, this);
            } else {
                newCell = g.walkCells(ed.row+delta, ed.col, delta, this.acceptsNav, this);
            }

            if (newCell) {
                g.startEditing(newCell[0], newCell[1]);
            } else {
                this.selectCell([ed.row, ed.col]);
                g.getView().focusCell(ed.row, ed.col);
            }
        } else if (k == e.ESC) {
            g.stopEditing(true);
            this.selectCell([ed.row, ed.col]);
            g.getView().focusCell(ed.row, ed.col);
        }
    }
});