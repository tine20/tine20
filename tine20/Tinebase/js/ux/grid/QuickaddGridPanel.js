/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Ext.ux', 'Ext.ux.grid');

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
 */
Ext.ux.grid.QuickaddGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
	/**
	 * @cfg {String} quickaddMandatory 
     * Mandatory field which must be set before quickadd fields will be enabled
	 */
	quickaddMandatory: false,
    
    /**
     * @cfg {Bool} resetAllOnNew
     * reset all fields after new record got created (per default only den mandatory field gets resetted).
     */
    resetAllOnNew: false,
    
    /**
     * @property {Bool} adding true if a quickadd is in process
     */
    adding: false,
    
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
        
        // init handlers
        this.quickaddHandlers = {
        	scope: this,
            blur: function(){
                this.doBlur.defer(250, this);
            },
            specialkey: function(f, e){
                if(e.getKey()==e.ENTER){
                    e.stopEvent();
                    f.el.blur();
                    if(f.triggerBlur){
                        f.triggerBlur();
                    }
                }
            }
        };
    },
    
    /**
     * renders the quick add fields
     */
    renderQuickAddFields: function() {
        Ext.each(this.getVisibleCols(), function(item){
            if (item.quickaddField) {
                item.quickaddField.render(this.idPrefix + item.id);
                item.quickaddField.setDisabled(item.id != this.quickaddMandatory);
                item.quickaddField.on(this.quickaddHandlers);
            }
        },this);
        
        // rezise quickeditor fields according to parent column
        this.colModel.on('widthchange', this.syncFields, this);
        this.colModel.on('hiddenchange', this.syncFields, this);
        this.on('resize', this.syncFields);
        this.on('columnresize', this.syncFields);
        this.syncFields();
        
        this.colModel.getColumnById(this.quickaddMandatory).quickaddField.on('focus', this.onMandatoryFocus, this);
    },
    
    /**
     * @private
     */
    doBlur: function(){
    	
    	// check if all quickadd fields are blured
    	var hasFocus;
    	Ext.each(this.getVisibleCols(), function(item){
    	    if(item.quickaddField && item.quickaddField.hasFocus){
    	    	hasFocus = true;
    	    }
    	}, this);
    	
    	// only fire a new record if no quickaddField is focused
    	if (!hasFocus) {
    		var data = {};
    		Ext.each(this.getVisibleCols(), function(item){
                if(item.quickaddField){
                    data[item.id] = item.quickaddField.getValue();
                    item.quickaddField.setDisabled(item.id != this.quickaddMandatory);
                }
            }, this);
            
            if (this.colModel.getColumnById(this.quickaddMandatory).quickaddField.getValue() != '') {
            	if (this.fireEvent('newentry', data)){
                    this.colModel.getColumnById(this.quickaddMandatory).quickaddField.setValue('');
                    if (this.resetAllOnNew) {
                        var columns = this.colModel.config;
                        for (var i = 0, len = columns.length; i < len; i++) {
                            if(columns[i].quickaddField != undefined){
                               columns[i].quickaddField.setValue('');
                            }
                        }
                    }
                }
            }
    		
            this.adding = false;
    	}
    	
    },
    /**
     * @private
     */
    getVisibleCols: function(){
    	var enabledCols = [];
    	
    	var cm = this.colModel;
    	var ncols = cm.getColumnCount();
        for (var i=0; i<ncols; i++) {
            if (!cm.isHidden(i)) {
            	var colId = cm.getColumnId(i);
            	enabledCols.push(cm.getColumnById(colId));
            }
        }
        return enabledCols;
    },
    /**
     * @private
     */
    initTemplates: function() {
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
    syncFields: function(){
        var pxToSubstract = 2;
        if (Ext.isSafari3) {pxToSubstract = 11;}

        var cm = this.colModel;
        var visCols = this.getVisibleCols();
        
        var newRow = Ext.DomQuery.selectNode('tr[class=new-row]', this.getView().mainHd.dom);
        
        var ncols = cm.getColumnCount();
        for (var col, i=0; i<ncols; i++) {
            col = cm.getColumnAt(i);
            
            if (visCols.indexOf(col) < 0) {
                newRow.childNodes[i].style.display = 'none';
            } else {
                newRow.childNodes[i].style.display = '';
                if (col.quickaddField) {
                    col.quickaddField.setSize(cm.getColumnWidth(cm.getIndexById(col.id))-pxToSubstract);
                }
            }
        }
    },
    
    /**
     * @private
     */
    onMandatoryFocus: function() {
        this.adding = true;
        Ext.each(this.getVisibleCols(), function(item){
            if(item.quickaddField){
                item.quickaddField.setDisabled(false);
            }
        }, this);
    }
        
});
