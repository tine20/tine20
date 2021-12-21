/*
 * Tine 2.0
 *
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux', 'Ext.ux.grid');

/**
 * Class for creating a edittable grid with quick add row on top.
 * <p>As form field for the quick add row, the quickaddField of the column definition is used.<p>
 * <p>The event 'newentry' is fired after the user finished editing the new row.<p>
 * <p>Example usage:</p>
 * <pre><code>
 var g =  new Ext.ux.grid.QuickaddGridPanel({
     ...
     quickaddMandatory: 'summary',
     columns: [
         {
             ...
             id: 'summary',
             quickaddField = new Ext.form.TextField({
                 emptyText: 'Add a task...'
             })
         },
         {
             ...
             id: 'due',
             quickaddField = new Ext.form.DateField({
                 ...
             })
         }
     ]
 });
 * </code></pre>
 *
 * @namespace   Ext.ux.grid
 * @class       Ext.ux.grid.QuickaddGridPanel
 * @extends     Ext.grid.EditorGridPanel
 */
Ext.ux.grid.QuickaddGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * @cfg quickaddMode (header|inline|sorted)
     */
    quickaddMode: 'header',
    /**
     * @cfg {String} quickaddMandatory
     * Mandatory field which must be set before quickadd fields will be enabled
     */
    quickaddMandatory: false,
    /**
     * if set, the quickadd fields are validated on blur
     * @type Boolean
     */
    validate: false,

    /**
     * @cfg {Bool} resetAllOnNew
     * reset all fields after new record got created (per default only den mandatory field gets resetted).
     */
    resetAllOnNew: false,

    /**
     * @property {Bool} adding true if a quickadd is in process
     */
    adding: false,

    parentScope: null,

    /**
     * @private
     */
    initComponent: function(){

        this.idPrefix = Ext.id();

        Ext.ux.grid.QuickaddGridPanel.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event newentry
             * Fires when add process is finished
             * @param {Object} data of the new entry
             */
            'newentry'
        );

        this.cls = 'x-grid3-quickadd';

        // The customized header template
        this.initTemplates();

        // add our fields after view is rendered
        this.getView().afterRender = this.getView().afterRender.createSequence(this.renderQuickAddFields, this);

        if (! this.autoExpandColumn && this.quickaddMandatory) {
            this.autoExpandColumn = this.quickaddMandatory;
        }

        // init handlers
        this.quickaddHandlers = {
            scope: this,
            blur: function(field){
                this.doBlur.defer(250, this);
            },
            specialkey: function(f, e){
                var key = e.getKey();
                switch (key) {
                    case e.ENTER:
                        e.stopEvent();
                        f.el.blur();
                        if (f.triggerBlur){
                            f.triggerBlur();
                        }
                        break;
                    case e.ESC:
                        e.stopEvent();
                        f.setValue('');
                        f.el.blur();
                        break;
                }
            }
        };

        this.on('beforeedit', this.onBeforeEdit, this);
        this.on('validateedit', this.onValidateEdit, this);
        this.selModel.on('beforerowselect', this.onBeforeRowSelect, this);
    },

    /**
     * renders the quick add header fields
     */
    renderQuickAddFields: function() {
        const me = this;

        Ext.each(this.getCols(), function (column) {
            if (column.quickaddField) {
                if (this.quickaddMode === 'header') {
                    column.quickaddField.render(this.getQuickAddWrap(column));
                } else {
                    const renderer = Ext.isFunction(column.renderer) ? column.renderer : Tine.widgets.grid.RendererManager.prototype.defaultRenderer;
                    column.renderer = function(value, meta, record, row, col) {
                        if (record !== me.quickaddRecord) return renderer.apply(this, arguments);
                        // if (! column.quickaddEditor) column.quickaddEditor = new Ext.Editor({
                        //     field: column.quickaddField
                        // });
                        // if (! column.quickaddField.rendered)
                        _.defer(() => {
                            const cell = me.getView().getCell(row, col).firstElementChild;
                            cell.innerHTML = '';
                            if (! column.quickaddField.rendered) {
                                column.quickaddField.render(cell);
                            } else {
                                Ext.fly(cell).appendChild(column.quickaddField.wrap ? column.quickaddField.wrap : column.quickaddField.el);
                                _.defer(() => { column.quickaddField.setWidth(Ext.fly(cell).getWidth()-4); })
                            }
                            // column.quickaddEditor.startEdit(cell, null);
                        });
                        return /*column.dataIndex === me.quickaddMandatory ? '+ add new ...' : */'';
                    };
                }


                column.quickaddField.setDisabled(this.readOnly || column.id != this.quickaddMandatory);
                column.quickaddField.on(this.quickaddHandlers);
            }
        }, this);


        this.colModel.on('configchange', this.syncFields, this);
        this.colModel.on('hiddenchange', this.syncFields, this);
        this.on('resize', this.syncFields);
        this.on('columnresize', this.syncFields);
        this.colModel.on('columnmoved', this.syncFields, this);
        this.view.on('beforerefresh', this.onBeforeRefresh, this);
        this.view.on('refresh', this.onRefresh, this);

        if (this.quickaddMode === 'header') {
            // this.view.on('beforerefresh', this.onBeforeRefresh, this);
            // this.view.on('refresh', this.onRefresh, this);
        } else {
            this.renderQuickAddRow();
        }
        this.syncFields();

        this.colModel.getColumnById(this.quickaddMandatory).quickaddField.on('focus', this.onMandatoryFocus, this);
    },

    // quickaddMode !== 'header'
    renderQuickAddRow(idx) {
        this.store.remove(this.quickaddRecord);

        if (!this.quickaddRecord) {
            const defaultData = Ext.isFunction(this.recordClass.getDefaultData) ?
                this.recordClass.getDefaultData() : {};
            this.quickaddRecord = new this.recordClass(defaultData, Ext.id());
        }

        this.store.insert(idx || this.store.getCount(), [this.quickaddRecord]);
    },

    onValidateEdit: function(o) {
        var _ = window.lodash,
            me = this,
            fieldDef = _.isFunction(_.get(me, 'recordClass.getField')) ?
                _.get(me.recordClass.getField(o.field), 'fieldDefinition') : null;


        if (_.get(fieldDef, 'type') == 'record') {
            var col = _.find(me.getCols(), {dataIndex: o.field}),
                recordData = _.get(col, 'editor.field.selectedRecord.data');

            // o.record.setValue(o.field, recordData);

            o.value = recordData ? recordData : o.value;
        }
    },

    /**
     * @private
     */
    doBlur: function(){

        // check if all quickadd fields are blured, validate them if required
        var focusedOrInvalid;
        Ext.each(this.getCols(true), function(item){
            if(item.quickaddField && (item.quickaddField.hasFocus || item.quickaddField.hasFocusedSubPanels || (this.validate && !item.quickaddField.isValid()))) {
                focusedOrInvalid = true;
            }
        }, this);

        // only fire a new record if no quickaddField is focused
        if (!focusedOrInvalid) {
            var data = {};
            Ext.each(this.getCols(true), function(item){
                if(item.quickaddField){
                    data[item.id] = item.quickaddField.selectedRecord ?
                        item.quickaddField.selectedRecord.data :
                        item.quickaddField.getValue();
                    // NOTE quickAddField might set more data at once
                    if (item.quickaddField.getQuickAddRecordData) {
                        Ext.apply(data, item.quickaddField.getQuickAddRecordData(this));
                    }
                    item.quickaddField.setDisabled(item.id != this.quickaddMandatory);
                }
            }, this);

            if (this.colModel.getColumnById(this.quickaddMandatory).quickaddField.getValue() != '') {
                if (this.fireEvent('newentry', data)){
                    var quickAddField = this.colModel.getColumnById(this.quickaddMandatory).quickaddField;
                    if (quickAddField.resetOnNew !== false) {
                        quickAddField[quickAddField.clearValue ? 'clearValue' : 'setValue']('');
                        if (this.validate) {
                            quickAddField.clearInvalid();
                        }
                    }
                    if (this.resetAllOnNew) {
                        var columns = this.colModel.config;
                        for (var i = 0, len = columns.length; i < len; i++) {
                            if (columns[i].quickaddField != undefined) {
                                if (columns[i].quickaddField.xtype === 'extuxmoneyfield') {
                                    // prevent 0,00 € in moneyfields
                                    columns[i].quickaddField.setRawValue('');
                                } else {
                                    columns[i].quickaddField.setValue('');
                                }
                            }
                        }
                    }
                }
            }

            this.adding = false;
        }

    },

    /**
     * gets columns
     *
     * @param {Boolean} visibleOnly
     * @return {Array}
     */
    getCols: function(visibleOnly) {
        if(visibleOnly === true){
            var visibleCols = [];
            Ext.each(this.colModel.config, function(column) {
                if (! column.hidden) {
                    visibleCols.push(column);
                }
            }, this);
            return visibleCols;
        }
        return this.colModel.config;
    },

    /**
     * returns wrap el for quick add filed of given col
     *
     * @param {Ext.grid.Colum} col
     * @return {Ext.Element}
     */
    getQuickAddWrap: function(column) {
        if (this.quickaddMode === 'header') {
            return Ext.get(this.idPrefix + column.id);
        } /*else {
            const row = this.store.indexOf(this.quickaddRecord);
            const col = this.colModel.columns.indexOf(column);
            const cell = this.getView().getCell(row, col);
            if (cell) {
                // cell.innerHTML = '';
                return Ext.fly(cell);
            }
        } */
    },

    /**
     * @private
     */
    initTemplates: function() {
        if (this.quickaddMode !== 'header') return;

        this.getView().templates = this.getView().templates ? this.getView().templates : {};
        var ts = this.getView().templates;

        var newRows = '';

        var cm = this.colModel;
        var ncols = cm.getColumnCount();
        for (var i=0; i<ncols; i++) {
            var colId = cm.getColumnId(i);
            newRows += '<td><div class="x-small-editor" id="' + this.idPrefix + colId + '"></div></td>';
        }

        ts.header = new Ext.Template(
            '<table border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
            '<thead><tr class="x-grid3-hd-row">{cells}</tr></thead>',
            '<tbody><tr class="new-row">',
                newRows,
            '</tr></tbody>',
            '</table>'
        );
    },

    /**
     * @private
     */
    syncFields: function() {
        var newRowEl = Ext.get(Ext.DomQuery.selectNode('tr[class=new-row]', this.getView().mainHd.dom));

        var columns = this.getCols();
        for (var column, tdEl, i=columns.length -1; i>=0; i--) {
            column = columns[i];
            if (this.quickaddMode === 'header') {
                var tdEl = this.getQuickAddWrap(column).parent();

                // resort columns
                newRowEl.insertFirst(tdEl);

                // set hidden state
                tdEl.dom.style.display = column.hidden ? 'none' : '';
            }
            // resize
            //tdEl.setWidth(column.width);
            if (column.quickaddField) {
                column.quickaddField.setSize(column.width -1);
            }
        }
    },

    // save our editors - they get overwritten on hd-refresh
    onBeforeRefresh: function() {
        Ext.each(this.getCols(), function(col) {
            if (col.quickaddField && col.quickaddField.rendered) {
                var el = col.quickaddField.el,
                    hdEl = el.up('.x-grid3');

                hdEl?.appendChild(col.quickaddField.wrap ? col.quickaddField.wrap : el);
            }
        }, this);
    },

    // restore editors
    onRefresh:  function() {
        Ext.each(this.getCols(), function(col) {
            if (col.quickaddField && col.quickaddField.rendered) {
                var wrap = this.getQuickAddWrap(col);

                wrap?.appendChild(col.quickaddField.wrap ? col.quickaddField.wrap : col.quickaddField.el);
            }
        }, this);
    },

    /**
     * @private
     */
    onMandatoryFocus: function() {
        if (this.readOnly) {
            return;
        }

        this.adding = true;
        Ext.each(this.getCols(true), function(item){
            if(item.quickaddField){
                item.quickaddField.setDisabled(false);
            }
        }, this);
    },

    beforeDragDrop: function() {
        if (this.readOnly) {
            return false;
        }
    },

    onBeforeRowSelect: function(sm, row, keep, record) {
        // NOTE: can't dd if not selectable
        // if (record === this.quickaddRecord) return false;
    },

    onBeforeEdit: function(o) {
        if (this.readOnly) {
            o.cancel = true;
        }
        if (this.quickaddMode !== 'header') {
            if (o.row === this.store.indexOf(this.quickaddRecord)) {
                o.cancel = true;
                // // start all editors
                // const columns = this.getCols();
                // columns.forEach((col) => {
                //     if (col.quickaddEditor) {
                //         col.quickaddEditor.startEdit(o.row, columns.indexOf(col));
                //     }
                // });

                const quickaddField = this.colModel.columns[o.column].quickaddField;
                if (quickaddField) {
                    _.defer(() => {
                        quickaddField.focus();
                    });
                }

            }
        }
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;

        Ext.each(this.getCols(true), function(item){
            if(item.quickaddField){
                item.quickaddField.setDisabled(readOnly || item.id != this.quickaddMandatory);
            }
        }, this);
    }
});
