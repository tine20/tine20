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
    layout: 'fit',
    anchor:'100%',
    region: 'center',
    deferredRender: false,
    layoutOnTabChange:true,
    handlerScope: null,
	
	//private
    initComponent: function(){
		this.action_saveAndClose = new Ext.Action({
            text: 'save and close',
            handler: this.handlerSaveAndClose,
            iconCls: 'action_saveAndClose',
            scope: this.handlerScope
        });
    
        this.action_applyChanges = new Ext.Action({
            text: 'apply changes',
            handler: this.handlerApplyChanges,
            iconCls: 'action_applyChanges',
            scope: this.handlerScope
            //disabled: true
        });
    
        this.action_delete = new Ext.Action({
            text: 'delete',
            handler: this.handlerDelete,
            iconCls: 'action_delete',
            scope: this.handlerScope,
            disabled: true
        });
        
        this.tbar = new Ext.Toolbar({
            region: 'south',
            id: 'applicationToolbar',
            split: false,
            height: 26,
            items: [
                this.action_saveAndClose,
                this.action_applyChanges,
                this.action_delete
            ]
        });
		
		Egw.widgets.dialog.EditRecord.superclass.initComponent.call(this);
	}
});

Ext.namespace('Egw.widgets.Percent');
Egw.widgets.Percent.Combo = Ext.extend(Ext.form.ComboBox, {
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
        Egw.widgets.Percent.Combo.superclass.initComponent.call(this);
		// allways set a default
		if(!this.value) {
		    this.value = 0;
		}
			
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
		});
    }
});

Egw.widgets.Percent.renderer = function(percent) {
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

Ext.namespace('Egw.widgets.Priority');
Egw.widgets.Priority.store = new Ext.data.SimpleStore({
	storeId: 'Priorities',
	id: 'key',
    fields: ['key','value', 'icon'],
    data: [
            ['0', 'low',    '' ],
            ['1', 'normal', '' ],
            ['2', 'high',   '' ],
            ['3', 'urgent', '' ]
        ]
});

Egw.widgets.Priority.Combo = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfs {bool} autoExpand
     * Autoexpand comboBox on focus.
     */
    autoExpand: false,
    
    displayField: 'value',
    valueField: 'key',
    mode: 'local',
    triggerAction: 'all',
    //selectOnFocus: true,
    editable: false,
    lazyInit: false,
    
    //private
    initComponent: function(){
        Egw.widgets.Priority.Combo.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 1;
        }
            
        this.store = Egw.widgets.Priority.store;
        
        if (this.autoExpand) {
            this.on('focus', function(){
                this.lazyInit = false;
                this.expand();
            });
        }
    }
});

Egw.widgets.Priority.renderer = function(priority) {
	var s = Egw.widgets.Priority.store;
	var idx = s.find('key', priority);
	return idx !== undefined ? s.getAt(idx).data.value : priority;
};
