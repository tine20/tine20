/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.container');

/**
 * @class Tine.widgets.container.selectionComboBox
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.form.ComboBox
 * 
 * Container select ComboBox widget
 */
Tine.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox, {
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
        Tine.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        if (this.defaultContainer) {
            this.container = this.defaultContainer;
            this.value = this.defaultContainer.name;
        }
        this.onTriggerClick = function(e) {
            if (!this.disabled) {
                var w = new Tine.widgets.container.selectionDialog({
                    TriggerField: this
                });
            }
        };
    },
    //private
    getValue: function(){
        return this.container.id;
    },
    //private
    setValue: function(container){
        if (container.account_grants) {
            this.setDisabled(! container.account_grants.deleteGrant);
        }
    	this.container = container;
    	this.setRawValue(container.name);
    }
});
Ext.reg('tinewidgetscontainerselectcombo', Tine.widgets.container.selectionComboBox);

/**
 * This widget shows a modal container selection dialog
 * @class Tine.widgets.container.selectionDialog
 * @extends Ext.Component
 * @package Tinebase
 * @subpackage Widgets
 */
Tine.widgets.container.selectionDialog = Ext.extend(Ext.Component, {
	/**
	 * @cfg {string}
	 * title of dialog
	 */
    title: _('please select a container'),

    // private
    initComponent: function(){
        Tine.widgets.container.selectionDialog.superclass.initComponent.call(this);
        
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
        
        var tree = new Tine.widgets.container.TreePanel({
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
