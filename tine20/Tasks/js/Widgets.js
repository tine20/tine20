/**
 * egroupware 2.0
 * 
 * @package     Egw
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Egw.widgets');

Ext.namespace('Egw.widgets.dialog');
Egw.widgets.dialog.EditRecord = Ext.extend(Ext.FormPanel, {
	labelAlign: 'top',
    bodyStyle:'padding:5px',
    anchor:'100%',
    region: 'center',
    deferredRender: false,
    layoutOnTabChange:true,
	
	//private
    initComponent: function(){
		var action_saveAndClose = new Ext.Action({
            text: 'save and close',
            handler: this.handler_saveAndClose,
            iconCls: 'action_saveAndClose'
        });
    
        var action_applyChanges = new Ext.Action({
            text: 'apply changes',
            handler: this.handler_applyChanges,
            iconCls: 'action_applyChanges',
            disabled: true
        });
    
        var action_delete = new Ext.Action({
            text: 'delete',
            handler: this.handler_pre_delete,
            iconCls: 'action_delete',
            disabled: true
        });
        
        this.tbar = new Ext.Toolbar({
            region: 'south',
            id: 'applicationToolbar',
            split: false,
            height: 26,
            items: [
                action_saveAndClose,
                action_applyChanges,
                action_delete
            ]
        });
		Egw.widgets.dialog.EditRecord.superclass.initComponent.call(this);
	}
});

Ext.namespace('Egw.widgets.Percent');
Egw.widgets.Percent.ComboBox = Ext.extend(Ext.form.ComboBox, {
	/**
	 * @cfs {bool} autoExpand
	 * Autoexpand comboBox on focus.
	 */
	autoExpand: false,
	
    displayField: 'value',
    valueField: 'key',
    //typeAhead: true,
    mode: 'local',
    triggerAction: 'all',
    emptyText: 'percent ...',
    //selectOnFocus: true,
    //editable: false,
	lazyInit: false,
	
    //private
    initComponent: function(){
        Egw.widgets.Percent.ComboBox.superclass.initComponent.call(this);
		// allways set a default
		if(!this.value) 
		    this.value = 0;
			
		this.store = new Ext.data.SimpleStore({
	        fields: ['key','value'],
	        data: [
	                ['0',    '0%'],
	                ['10',  '10%'],
	                ['20',  '20%'],
	                ['30',  '30%'],
	                ['40',  '40%'],
	                ['50',  '50%'],
	                ['60',  '60%'],
	                ['70',  '70%'],
	                ['80',  '80%'],
	                ['90',  '90%'],
	                ['100','100%']
	            ]
	    });
		
		if (this.autoExpand) {
            this.on('focus', function(){
                this.lazyInit = false;
                this.expand();
            });
        }
		
		this.on('select', function(){
			//this.el = Egw.widgets.Percent.ComboBox.progressBar(this.value);
		})
    }
});

Egw.widgets.Percent.ComboBox.progressBar = function(percent) {
    return '<div class="x-progress-wrap TasksProgress">' +
            '<div class="x-progress-inner TasksProgress">' +
                '<div class="x-progress-bar TasksProgress" style="width:' + percent + '%">' +
                    '<div class="TasksProgressText TasksProgress">' +
                        '<div>'+ percent +'%</div>' +
                    '</div>' +
                '</div>' +
                '<div class="x-progress-text x-progress-text-back TasksProgress">' +
                    '<div>&#160;</div>' +
                '</div>' +
            '</div>' +
        '</div>';
};
