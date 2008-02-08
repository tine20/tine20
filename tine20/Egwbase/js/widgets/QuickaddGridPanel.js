/*
 * egroupware 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Ext.ux', 'Ext.ux.grid');

/**
 * @class       Ext.ux.grid.QuickaddGridPanel
 * @constructor
 * @package     Ext
 * @subpackage  ux
 * @extends     Ext.grid.EditorGridPanel
 * @param       {Object} config Configuration options
 * @description
 * 
 * <p>Class for creating a edittable grid with quick add row on top.</p>
 * <p>As form field for the quick add row, the quickaddField of the column definition is used.<p>
 * <p>The event 'newentry' is fired after the user finished editing the new row.<p>
 * <p>Example usage:</p>
 * <pre><code>
 var g =  new Ext.ux.grid.QuickaddGridPanel({
     ...
     quickaddMandatory: 'summaray',
     columns: [
         {
             ...
             id: 'summaray',
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
 </code></pre>
 */
Ext.ux.grid.QuickaddGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
	/**
	 * @cfg {string} quickaddMandatory Mandatory field which must be set before quickadd fields will be enabled
	 */
	quickaddMandatory: false,
    /**
     * @private
     */
    initComponent: function(){
        Ext.ux.grid.QuickaddGridPanel.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event newentry
             * Fires when add process is finished 
             * @param {Object} data of the new entry
             */
            'newentry'
        );
        
        // The customized header template
        this.getView().templates = {
            header: this.makeHeaderTemplate()
        }
        
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
     * @private
     */
    onRender : function(ct, position){
    	Ext.ux.grid.QuickaddGridPanel.superclass.onRender.apply(this, arguments);
    	
    	// generate quickadd form fields before grid gets rendered
    	Ext.each(this.getVisibleCols(), function(item){
    		if (item.quickaddField) {
    			item.quickaddField.render('new-' + item.id);
    			item.quickaddField.setDisabled(item.id != this.quickaddMandatory);
    			item.quickaddField.on(this.quickaddHandlers);
    		}
        },this);
    	
        // rezise quickeditor fields according to parent column
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
    	    if(item.quickaddField.hasFocus){
    	    	hasFocus = true;
    	    }
    	}, this);
    	
    	// only fire a new record if no quickaddField is focused
    	if (!hasFocus) {
    		var data = {};
    		Ext.each(this.getVisibleCols(), function(item){
                data[item.id] = item.quickaddField.getValue();
                item.quickaddField.setDisabled(item.id != this.quickaddMandatory);
            }, this);
            
            if (this.colModel.getColumnById(this.quickaddMandatory).quickaddField.getValue() != '') {
            	if (this.fireEvent('newentry', data)){
                    this.colModel.getColumnById(this.quickaddMandatory).quickaddField.setValue('');
                }
            }
    		
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
    makeHeaderTemplate: function() {
    	var newRows = '';
    	
    	Ext.each(this.getVisibleCols(), function(item){
    	    newRows += '<td><div class="x-small-editor" id="new-' + item.id + '"></div></td>';
    	}, this);
        
    	return new Ext.Template(
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
        if (Ext.isSafari) {pxToSubstract = 11;}

        var cm = this.colModel;
        Ext.each(this.getVisibleCols(), function(item){
            if(item.quickaddField){
            	item.quickaddField.setSize(cm.getColumnWidth(cm.getIndexById(item.id))-pxToSubstract);
            }
        }, this);
    },
    /**
     * @private
     */
    onMandatoryFocus: function() {
        Ext.each(this.getVisibleCols(), function(item){
            item.quickaddField.setDisabled(false);
        }, this);
    }
        
});
