/*
 * egroupware 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Egw.widgets', 'Egw.widgets.container');

/**
 * @class Egw.widgets.container.selectionComboBox
 * @package Egwbase
 * @subpackage Widgets
 * @extends Ext.form.ComboBox
 * 
 * Container select ComboBox widget
 */
Egw.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {array}
     * default container
     */
    defaultContainer: false,
    
    allowBlank: false,
    readOnly:true,
    container: null,
    
    // private
    initComponent: function(){
        Egw.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        if (this.defaultContainer) {
            this.container = this.defaultContainer;
            this.value = this.defaultContainer.container_name;
        }
        this.onTriggerClick = function(e) {
            var w = new Egw.widgets.container.selectionDialog({
                TriggerField: this
            });
        };
    },
    //private
    getValue: function(){
        return this.container.container_id;
    },
    //private
    setValue: function(container){
    	this.container = container;
    	this.setRawValue(container.container_name);
    }
    
});

/**
 * This widget shows a modal container selection dialog
 * @class Egw.widgets.container.selectionDialog
 * @extends Ext.Component
 * @package Egwbase
 * @subpackage Widgets
 */
Egw.widgets.container.selectionDialog = Ext.extend(Ext.Component, {
	/**
	 * @cfg {string}
	 * title of dialog
	 */
    title: 'please select a container',

    // private
    initComponent: function(){
        Egw.widgets.container.selectionDialog.superclass.initComponent.call(this);
        
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
            buttonAlign: 'center'
        });
        
        var tree = new Egw.widgets.container.TreePanel({
            itemName: this.TriggerField.itemName,
            appName: this.TriggerField.appName,
            defaultContainer: this.TriggerField.defaultContainer
        });
        
        tree.on('click', function(_node) {
            if(_node.attributes.containerType == 'singleContainer') {
                this.TriggerField.setValue(_node.attributes.container);
                w.hide();
            }
        }, this);
            
        w.add(tree);
        w.show();
    }
});
