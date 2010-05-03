/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * Container select ComboBox widget
 * 
 * @namespace Tine.widgets.container
 * @class       Tine.widgets.container.selectionComboBox
 * @extends     Ext.form.ComboBox
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {Boolean} allowNodeSelect
     */
    allowNodeSelect: false,
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
    selectedContainer: null,
    /**
     * @cfg {Number} list width
     */    
    listWidth: 400,
    /**
     * @cfg {string} containerName
     * name of container (singular)
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {Boolean} hideTrigger2
     */
    hideTrigger2: true,
    /**
     * @cfg {String} startPath a container path to start with
     * possible values are: '/', '/personal/someAccountId', '/shared' (defaults to '/')
     * 
     * NOTE: container paths could not express tree paths .../otherUsers!
     */
    startPath: '/',
    /**
     * @cfg {Tine.data.Record} recordClass
     */
    recordClass: null,
    /**
     * @cfg {String} requiredGrant
     * grant which is required to select leaf node(s)
     */
    requiredGrant: 'readGrant',
    
    trigger2width: 100,
    
    // private
    allowBlank: false,
    triggerAction: 'all',
    forceAll: true,
    lazyInit: true,
    editable: false,
    clearFilterOnReset: false,
    
    stateful: true,
    stateEvents: ['select'],
    
    mode: 'local',
    valueField: 'id',
    displayField: 'name',
    
    /**
     * @private
     */
    initComponent: function() {
        // init state
        if (this.stateful && !this.stateId) {
            if (! this.recordClass) {
                this.stateful = false;
            } else {
                this.stateId = this.recordClass.getMeta('appName') + '-tinebase-widgets-container-selectcombo-' + this.recordClass.getMeta('modelName');
            }
        }
        // no state saving for startPath != /
        this.on('beforestatesave', function() {return this.startPath === '/';}, this);
        
        this.initStore();
        
        this.otherRecord = new Tine.Tinebase.Model.Container({id: 'other', name: String.format(_('choose other {0}...'), this.containerName)}, 'other');
        this.store.add(this.otherRecord);
        
        this.emptyText = String.format(_('Select a {0}'), this.containerName);
        
        // init triggers (needs to be done before standard initTrigger template fn)
        if (! this.hideTrigger2) {
            if (this.triggerClass == 'x-form-arrow-trigger') {
                this.triggerClass = 'x-form-arrow-trigger-rectangle';
            }
            
            this.triggerConfig = {
                tag:'span', cls:'x-form-twin-triggers', cn:[
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger " + this.triggerClass},
                {tag:'span', cls:'tw-containerselect-trigger2', cn:[
                    {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger tw-containerselect-trigger2-bg"},
                    {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger tw-containerselect-trigger2"},
                    {tag: "div", style: {position: 'absolute', top: 0, left: '5px'}}
                ]}
            ]};
            
        }
        
        //this.title = String.format(_('Recently used {0}:'), this.containersName);
        
        Tine.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        
        if (this.defaultContainer) {
            this.selectedContainer = this.defaultContainer;
            this.value = this.defaultContainer.name;
        }
        
        this.on('beforequery', this.onBeforeQuery, this);
    },
    
    /**
     * @private
     */
    initStore: function() {
        var state = this.stateful ? Ext.state.Manager.get(this.stateId) : null;
        var recentsData = state && state.recentsData || [];
        
        this.store = new Ext.data.JsonStore({
            id: 'id',
            data: recentsData,
            remoteSort: false,
            fields: Tine.Tinebase.Model.Container,
            listeners: {beforeload: this.onBeforeLoad.createDelegate(this)}
        });
        this.setStoreFilter();
    },
    
    initTrigger : function(){
        if (! this.hideTrigger2) {
            var t1 = this.trigger.first();
            var t2 = this.trigger.last();
            this.trigger2 = t2;
            
            t1.on("click", this.onTriggerClick, this, {preventDefault:true});
            t2.on("click", this.onTrigger2Click, this, {preventDefault:true});
            
            t1.addClassOnOver('x-form-trigger-over');
            t1.addClassOnClick('x-form-trigger-click');
            
            t2.addClassOnOver('x-form-trigger-over');
            t2.addClassOnClick('x-form-trigger-click');
            
            
        } else {
            Tine.widgets.container.selectionComboBox.superclass.initTrigger.call(this);
        }
    },
    
    manageRecents: function(recent) {
        recent.set('dtselect', new Date().getTime());
        
        this.store.remove(this.otherRecord);
        this.store.clearFilter();
        this.store.sort('dtselect', 'DESC');
        
        // keep 10 containers and the nodes in betwwen
        var containerKeepCount = 0;
        this.store.each(function(record) {
            if (containerKeepCount < 10) {
                if (! record.get('is_container_node')) {
                    containerKeepCount += 1;
                }
                return;
            }
            this.store.remove(record);
        }, this);
        this.setStoreFilter();
        this.store.add(this.otherRecord);
    },
    
    /**
     * prepare params for remote search (this.startPath == /personal/*
     * 
     * @param {} store
     * @param {} options
     */
    onBeforeLoad: function(store, options) {
        options.params = {
            method: 'Tinebase_Container.getContainer',
            application: this.appName,
            containerType: Tine.Tinebase.container.TYPE_PERSONAL,
            owner: Tine.Tinebase.container.pathIsPersonalNode(this.startPath)
        };
    },
    
    /**
     * if this.startPath correspondes to a personal node, 
     * directly do a remote search w.o. container tree dialog
     * 
     * @private
     * @param {} queryEvent
     */
    onBeforeQuery: function(queryEvent) {
        queryEvent.query = new Date().getTime();
        this.mode = Tine.Tinebase.container.pathIsPersonalNode(this.startPath) ? 'remote' : 'local' ;
        
        // skip combobox nativ filtering to preserv our filters
        if (this.mode == 'local') {
            this.onLoad();
            return false;
        }
    },
    
    /**
     * filter store according to this.startPath and this.allowNodeSelect
     * 
     * NOTE: If this.startPath != '/' resents are not used as we force remote loads,
     *       thus we don't need to filter in this case!
     */
    setStoreFilter: function() {
        this.store.clearFilter();
        this.store.sort('dtselect', 'DESC');
        
        var skipBoundary = this.store.getAt(Math.max(this.store.getCount(), 10) -1);
        var dtselectMin = skipBoundary ? skipBoundary.get('dtselect') : -1;
        
        this.store.filterBy(function(record) {
            var keep = (this.allowNodeSelect || !record.get('is_container_node')) && record.get('dtselect') > dtselectMin;
            return keep;
        }, this);
    },
    
    setTrigger2Text: function(text) {
        var trigger2 = this.trigger.last().last().update(text);
    },
    
    setTrigger2Disabled: function(bool) {
        if (bool) {
            this.trigger2.setOpacity(0.5);
            this.trigger2.un("click", this.onTrigger2Click, this, {preventDefault:true});
        } else {
            this.trigger2.setOpacity(1);
            this.trigger2.on("click", this.onTrigger2Click, this, {preventDefault:true});
        }
    },
    
    getTrigger2: function() {
        return this.trigger2;
    },
    
    onTrigger2Click: Ext.emptyFn,
    
    // private: only blur if dialog is closed
    onBlur: function() {
        if (!this.dlg) {
            return Tine.widgets.container.selectionComboBox.superclass.onBlur.apply(this, arguments);
        }
    },
    
    onSelect: function(record, index) {
        if (record == this.otherRecord) {
            this.onChoseOther();
        } else {
            this.manageRecents(record);
            Tine.widgets.container.selectionComboBox.superclass.onSelect.apply(this, arguments);
        }
    },
    
    /**
     * @private
     */
    onChoseOther: function() {
        this.collapse();
        this.dlg = new Tine.widgets.container.selectionDialog({
            allowNodeSelect: this.allowNodeSelect,
            containerName: this.containerName,
            containersName: this.containersName,
            requiredGrant: this.requiredGrant,
            TriggerField: this
        });
    },
    
    /**
     * @private
     */
    getValue: function(){
        return (this.selectedContainer !== null) ? this.selectedContainer.id : '';
    },
    
    /**
     * @private
     * @todo // records might be in from state, but value is conainerData only
     */
    setValue: function(container) {
        if (typeof container.get === 'function') {
            // container is a record -> already in store -> nothing to do
        } else if (this.store.getById(container)) {
            // store already has a record of this container
            container = this.store.getById(container);
            
        } else if (container.path && this.store.findExact('path', container.path) >= 0) {
            // store already has a record of this container
            container = this.store.getAt(this.store.findExact('path', container.path));
            
        }else if (container.path || container.id) {
            // ignore server name for node 'My containers'
            if (container.path && container.path === Tine.Tinebase.container.getMyNodePath()) {
                container.name = null;
            }
            container.name = container.name || Tine.Tinebase.container.path2name(container.path, this.containerName, this.containersName);
            container.id = container.id ||container.path;
            
            container = new Tine.Tinebase.Model.Container(container, container.id);
            
            this.store.add(container);
        } else {
            // reject container
            return this;
        }
        
        container.set('is_container_node', !!!Tine.Tinebase.container.pathIsContainer(container.get('path')));
        this.selectedContainer = container.data;
        
        // make shure other is _last_ entry in list
        this.store.remove(this.otherRecord);
        this.store.add(this.otherRecord);
        
        Tine.widgets.container.selectionComboBox.superclass.setValue.call(this, container.id);
        
        if (container.account_grants) {
            this.setDisabled(! container.account_grants.deleteGrant);
        }
        
        if(this.qtip) {
            this.qtip.remove();
        }
        
        return container;
    },
    
    /**
     * Resets the current field value to the originally loaded value and clears any validation messages.
     * See {@link Ext.form.BasicForm}.{@link Ext.form.BasicForm#trackResetOnLoad trackResetOnLoad}
     */
    reset : function() {
        this.supr().setValue(this.originalValue);
        this.supr().reset.call(this);
    },
    
    applyState: Ext.emptyFn,
    
    /**
     * @private
     */
    getState: function() {
        var recentsData = [];
        this.store.clearFilter();
        this.store.remove(this.otherRecord);
        
        this.store.each(function(container) {
            var data = Ext.copyTo({}, container.data, Tine.Tinebase.Model.Container.getFieldNames());
            recentsData.push(data);
        }, this);
        
        this.setStoreFilter();
        this.store.add(this.otherRecord);
        
        return {
            recentsData : recentsData
        };
    }
});
Ext.reg('tinewidgetscontainerselectcombo', Tine.widgets.container.selectionComboBox);

/**
 * @namespace Tine.widgets.container
 * @class Tine.widgets.container.selectionDialog
 * @extends Ext.Component
 * 
 * This widget shows a modal container selection dialog
 */
Tine.widgets.container.selectionDialog = Ext.extend(Ext.Component, {
	/**
     * @cfg {Boolean} allowNodeSelect
     */
    allowNodeSelect: false,
    /**
     * @cfg {string} containerName
     * name of container (singular)
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * name of container (plural)
     */
    containersName: 'containers',
    /**
	 * @cfg {string}
	 * title of dialog
	 */
    title: null,
    /**
     * @cfg {Number}
     */
    windowHeight: 400,
    /**
     * @property {Ext.Window}
     */
    win: null,
    /**
     * @property {Ext.tree.TreePanel}
     */
    tree: null,
    /**
     * @cfg {String} requiredGrant
     * grant which is required to select leaf node(s)
     */
    requiredGrant: 'readGrant',
    
    /**
     * @private
     */
    initComponent: function(){
        Tine.widgets.container.selectionDialog.superclass.initComponent.call(this);
        
        this.title = this.title ? this.title : String.format(_('please select a {0}'), this.containerName);
        
        this.cancleAction = new Ext.Action({
            text: _('Cancel'),
            iconCls: 'action_cancel',
            minWidth: 70,
            handler: this.onCancel,
            scope: this
        });
        
        this.okAction = new Ext.Action({
            disabled: true,
            text: _('Ok'),
            iconCls: 'action_saveAndClose',
            minWidth: 70,
            handler: this.onOk,
            scope: this
        });
        
        // adjust window height
		if (Ext.getBody().getHeight(true) * 0.7 < this.windowHeight) {
			this.windowHeight = Ext.getBody().getHeight(true) * 0.7;
		}

        this.tree = new Tine.widgets.container.TreePanel({
        	allowMultiSelection: false,
            containerName: this.TriggerField.containerName,
            containersName: this.TriggerField.containersName,
            appName: this.TriggerField.appName,
            defaultContainer: this.TriggerField.defaultContainer,
            requiredGrant: this.requiredGrant
        });
        
        this.tree.on('click', this.onTreeNodeClick, this);
        this.tree.on('dblclick', this.onTreeNoceDblClick, this);
		
        this.win = Tine.WindowFactory.getWindow({
            title: this.title,
            closeAction: 'close',
            modal: true,
            width: 375,
            height: this.windowHeight,
            minWidth: 375,
            minHeight: this.windowHeight,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            
            buttons: [
                this.cancleAction,
                this.okAction
            ],
            
            items: [ this.tree ]
        });
    },
    
    /**
     * @private
     */
    onTreeNodeClick: function(node) {
        this.okAction.setDisabled(! (node.leaf ||this.allowNodeSelect));
        
        if (! node.leaf ) {
            node.expand();
        }
    },
    
    /**
     * @private
     */
    onTreeNoceDblClick: function(node) {
        if (! this.okAction.isDisabled()) {
            this.onOk();
        }
    },
    
    /**
     * @private
     */
    onCancel: function() {
        this.onClose();
    },
    
    /**
     * @private
     */
    onClose: function() {
        this.win.close();
    },
    
    /**
     * @private
     */
    onOk: function() {
        var  node = this.tree.getSelectionModel().getSelectedNode();
        if (node) {
            var container = this.TriggerField.setValue(node.attributes.container);
            this.TriggerField.manageRecents(container);
            this.TriggerField.fireEvent('select', this.TriggerField, node.attributes.container);
            
            if (this.TriggerField.blurOnSelect) {
                this.TriggerField.fireEvent('blur', this.TriggerField);
            }
            this.onClose();
        }
    }
});
