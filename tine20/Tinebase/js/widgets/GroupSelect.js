/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.group');

/**
 * @class Tine.widgets.group.selectionComboBox
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.form.ComboBox
 * 
 * Group select ComboBox widget
 */
Tine.widgets.group.selectionComboBox = Ext.extend(Ext.form.ComboBox, {

	gotJson: false,
	//group: null,
	group: { id: 2, name: 'Users' },
 
    mode: 'local',
    triggerAction: 'all',
    allowBlank: false,
    editable: false,

    // private
    initComponent: function(){
    	
    	this.store =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getGroups',
                filter: '',
                sort: 'name',
                dir: 'asc',
                start: 0,
                limit: 50
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Ext.data.Record.create([{name: 'id'}, {name: 'name'}])       
        });
                        
        Tine.widgets.group.selectionComboBox.superclass.initComponent.call(this, arguments);
        
        this.onTriggerClick = function(e) {
        	
        	//console.log ( this.store );

            // get data via json request
        	if ( !this.gotJson ) {
    
                this.store.load();
    
                this.gotJson = true;
        	}
            
        	Tine.widgets.group.selectionComboBox.superclass.onTriggerClick.call(this, arguments);        	
        	
        };
    },

    // private
    getValue: function(){
    	return this.group.id;
    },

    // private
    setValue: function(_group)
    {
    	//console.log ( _group );
        
    	if ( !this.gotJson ) {
    	    this.setRawValue(_group.name);

            this.group = _group;
  	    } else {
            //console.log ( this.store.getById(_group) );
            
            var groupRecord = this.store.getById(_group).data;    
            this.setRawValue(groupRecord.name);
            
            this.group = groupRecord;
        }
    	
    }
    
});

/************** isn't used at the moment / build window with search function etc. later on ****************/

/**
 * This widget shows a modal group selection dialog
 * @class Tine.widgets.group.selectionDialog
 * @extends Ext.Component
 * @package Tinebase
 * @subpackage Widgets
 */
Tine.widgets.group.selectionDialog = Ext.extend(Ext.Component, {
	/**
	 * @cfg {string}
	 * title of dialog
	 */
    title: null,

    // private
    
    initComponent: function(){
        this.title = this.title ? this.title : _('Please Select a Group');
        Tine.widgets.group.selectionDialog.superclass.initComponent.call(this);
        
		var windowHeight = 400;
		if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
			windowHeight = Ext.getBody().getHeight(true) * 0.7;
		}

        var w = new Ext.Window({
            title: this.title,
            modal: true,
            width: 375,
            height: windowHeight,
            minWidth: 375,
            minHeight: windowHeight,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',
            buttonAlign: 'center',
            items: groupsGridPanel
        });
                                
        w.show();
    }
});

Ext.namespace('Tine.Admin.Model');
Tine.Admin.Model.Group = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
    // @todo add accounts array to group model?
]);
