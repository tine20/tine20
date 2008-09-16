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
Tine.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox /*Ext.form.TriggerField*/, {
    /**
     * @cfg {array}
     * default container
     */
    defaultContainer: false,
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    /**
     * @property {Object} currently displayed container
     */
    container: null,
    /**
     * @cfg {Number} list width
     */    
    listWidth: 400,
    
    // private
    allowBlank: false,
    triggerAction: 'all',
    lazyInit: false,
    readOnly:true,
    stateful: true,
    mode: 'local',
    valueField: 'id',
    displayField: 'name',
    
    /**
     * @private
     */
    initComponent: function(){
        this.store = new Ext.data.SimpleStore({
            id: id,
            fields: Tine.Tinebase.Model.Container
        });
        
        this.title = sprintf(_('Recently used %s:'), this.itemName);
        
        Tine.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        
        if (this.defaultContainer) {
            this.container = this.defaultContainer;
            this.value = this.defaultContainer.name;
        }
    },

    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.widgets.container.selectionComboBox.superclass.onRender.call(this, ct, position);
        
        var cls = 'x-combo-list';
        this.footer = this.list.createChild({cls:cls+'-ft'});
        this.button = new Ext.Button({
            text: sprintf(_('choose other %s...'), this.itemName),
            scope: this,
            handler: this.onChoseOther,
            renderTo: this.footer
        });
        this.assetHeight += this.footer.getHeight();
        
        this.getEl().on('mouseover', function(e, el) {
            this.qtip = new Ext.QuickTip({
                target: el,
                targetXY : e.getXY(),
                html: Ext.util.Format.htmlEncode(this.container.name) + 
                    '<i> (' + (this.container.type == Tine.Tinebase.container.TYPE_PERSONAL ?  _('personal') : _('shared')) + ')</i>'
            }).show();
        }, this);
    },
    
    /**
     * @private
     */
    onChoseOther: function() {
        this.collapse();
        var w = new Tine.widgets.container.selectionDialog({
            TriggerField: this
        });
    },
    
    /**
     * @private
     */
    getValue: function(){
        return this.container.id;
    },
    
    /**
     * @private
     */
    setValue: function(container){
        
        // element which is allready in this.store 
        if (typeof(container) == 'string') {
            container = this.store.getById(container).data;
        }
        
        /* complicated
        // trim length of current container name
        if (this.container && this.container.name && this.fullContainerName) {
            this.container.name = this.fullContainerName;
        }
        this.fullContainerName = container.name;
        container.name = Ext.util.Format.htmlEncode(Ext.util.Format.ellipsis(container.name, this.displayLength));
        */
        
        // dynamically add current container to store if not exists
        if (! this.store.getById(container.id)) {
            // we don't push arround container records yet...
            this.store.add(new Tine.Tinebase.Model.Container(container, container.id));
        }
        
        Tine.widgets.container.selectionComboBox.superclass.setValue.call(this, container.id);
        
        if (container.account_grants) {
            this.setDisabled(! container.account_grants.deleteGrant);
        }
        
        if(this.qtip) {
            this.qtip.remove();
        }
    	this.container = container;
        
        this.saveState();
    },
    
    /**
     * @private
     * Recents are a bit more than a simple state...
     */
    getState: function() {
        var recents = [];
        this.store.each(function(container) {
            recents.push(container.data);
        });
        return recents;
    },
    
    /**
     * @private
     */
    setState: function(state) {
        //console.log(this.store);
        if (typeof state == 'array') {
            for (var i=0; i<state.length; i++) {
                console.log('hier');
                this.store.add(new Tine.Tinebase.Model.Container(state[i], state[i].id));
            }
        }
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
    title: null,

    // private
    initComponent: function(){
        Tine.widgets.container.selectionDialog.superclass.initComponent.call(this);
        
        this.title = this.title ? this.title : _('please select a container');
        
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
