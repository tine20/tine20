/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * Container select ComboBox widget
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.SelectionComboBox
 * @extends     Ext.form.ComboBox
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.widgets.container.SelectionComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    /**
     * @cfg {Boolean} allowNodeSelect
     */
    allowNodeSelect: false,

    allowToplevelNodeSelect: true,

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
     * @cfg {string} containersName
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
     * @cfg {Array} requiredGrants
     * grants which are required to select leaf node(s)
     */
    requiredGrants: null,
    
    /**
     * @cfg {String} requiredGrant (legacy - should not be used any more)
     * grants which is required to select leaf node(s)
     */
    requiredGrant: null,
    
    /**
     *  @cfg {Number} trigger2width
     */
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
    
    mode: 'remote',
    valueField: 'id',
    displayField: 'name',
    treePanelClass: Tine.widgets.container.TreePanel,
    
    /**
     * is set to true if subpanels (selectionDialog) are active
     * useful in QuickAddGrid
     * @type Boolean
     */
    hasFocusedSubPanels: null,

    /**
     * @private
     */
    initComponent: function() {
        // autoinit config
        this.appName = ! this.appName && this.recordClass ? this.recordClass.getAppName() : this.appName;
        this.containerName = this.containerName == 'container' && this.recordClass ? this.recordClass.getContainerName() : this.containerName;
        this.containersName = this.containersName == 'containers' && this.recordClass ? this.recordClass.getContainersName() : this.containersName;

        // init state
        if (this.stateful && !this.stateId) {
            if (! this.recordClass) {
                this.stateful = false;
            } else {
                this.stateId = this.recordClass.getMeta('appName') + '-tinebase-widgets-container-selectcombo-' + this.recordClass.getMeta('modelName');
            }
        }

        // legacy handling for requiredGrant
        if(this.requiredGrant && ! this.requiredGrants) {
            this.requiredGrants = [this.requiredGrant];
        } else if (! this.requiredGrant && ! this.requiredGrants) {
            // set default required Grants
            this.requiredGrants = ['readGrant'];
        }

        // no state saving for startPath != /
        this.on('beforestatesave', function() {return this.startPath === '/';}, this);
        
        this.initStore();
        
        this.otherRecord = new Tine.Tinebase.Model.Container({id: 'other', name: String.format(i18n._('choose other {0}...'), this.containerName)}, 'other');
        this.store.add(this.otherRecord);
        
        this.emptyText = String.format(i18n._('Select a {0}'), this.containerName);
        
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

        // autoconfig app
        if (! this.app && this.appName) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        if (! this.app && this.recordClass) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }
        if (this.app && ! this.appName) {
            this.appName = this.app.appName;
        }

        this.recents = {};

        Tine.widgets.container.SelectionComboBox.superclass.initComponent.call(this);
        
        if (this.defaultContainer) {
            this.selectedContainer = this.defaultContainer;
            this.value = this.defaultContainer.name;
        }
        this.on('blur', function() {
            if(this.hasFocusedSubPanels) {
                return false;
            }
        }, this);
    },
    
    /**
     * @private
     */
    initStore: function() {
        var state = this.stateful ? Ext.state.Manager.get(this.stateId) : null;
        var recentsData = state && state.recentsData || [];

        this.store = new Ext.data.JsonStore({
            id: 'id',
            data: {results: recentsData},
            remoteSort: false,
            fields: Tine.Tinebase.Model.Container,
            root: 'results',
            totalProperty: 'totalcount',
            listeners: {
                beforeload: this.onBeforeLoad.createDelegate(this),
                load: this.onStoreLoad.createDelegate(this)
            }
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
            Tine.widgets.container.SelectionComboBox.superclass.initTrigger.call(this);
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
            method: 'Tinebase_Container.searchContainers',
            filter: [
                {field: 'application_id', operator: 'equals', value: this.app.id },
            ]
        };
        if (this.recordClass) {
            options.params.filter.push(
                {field: 'model', operator: 'equals', value: this.recordClass.getMeta('phpClassName')}
            );
        }

        if (Tine.Tinebase.container.pathIsPersonalNode(this.startPath)) {
            // direct search
            options.params.filter.push(
                {field: 'type',     operator: 'equals', value: Tine.Tinebase.container.TYPE_PERSONAL},
                {field: 'owner_id', operator: 'equals', value: Tine.Tinebase.container.pathIsPersonalNode(this.startPath)}
            );
        } else {
            // validate recents
            this.recents = {};
            this.store.each(function(r) {
                if (r != this.otherRecord) {
                    this.recents[r.id] = r.get('dtselect');
                }
            }, this);

            options.params.filter.push(
                {field: 'id',       operator: 'in',     value: Object.keys(this.recents)}
            );
        }
    },

    onStoreLoad: function() {
        if (! this.store) return;

        if (! Tine.Tinebase.container.pathIsPersonalNode(this.startPath)) {
            this.store.each(function(r) {
                r.set('dtselect', this.recents[r.id]);
            }, this);
            this.store.sort('dtselect', 'DESC');
            this.store.add(this.otherRecord);
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
            return Tine.widgets.container.SelectionComboBox.superclass.onBlur.apply(this, arguments);
        }
    },
    
    onSelect: function(record, index) {
        this.hasFocusedSubPanels = true;
        if (record == this.otherRecord) {
            this.onChoseOther();
        } else {
            this.manageRecents(record);
            Tine.widgets.container.SelectionComboBox.superclass.onSelect.apply(this, arguments);
        }
    },
    
    /**
     * @private
     */
    onChoseOther: function() {
        this.collapse();
        this.dlg = new Tine.widgets.container.SelectionDialog({
            recordClass: this.recordClass,
            allowNodeSelect: this.allowNodeSelect,
            allowToplevelNodeSelect: this.allowToplevelNodeSelect,
            requiredGrants: this.requiredGrants,
            treePanelClass: this.treePanelClass
        });

        this.dlg.on('select', function(d, node) {
            this.hasFocusedSubPanels = false;
            var container = this.setValue(node.attributes.container);
            this.manageRecents(container);
            this.fireEvent('select', this, node.attributes.container);

            if (this.blurOnSelect) {
                this.fireEvent('blur', this);
            }
            this.validate();
        }, this);
    },
    
    /**
     * @private
     */
    getValue: function(){
        return (this.selectedContainer !== null) ? this.selectedContainer.id : '';
    },
    
    /**
     * @private
     */
    setValue: function(container) {
        
        if (! container) {
            return;
        }

        if (container.hasOwnProperty('get') && Ext.isFunction(container.get)) {
            // container is a record -> already in store -> nothing to do
        } else if (this.store.getById(container)) {
            // store already has a record of this container
            container = this.store.getById(container);
            
        } else if (container.path || container.id) {
            if (container.path && this.store.findExact('path', container.path) >= 0) {
                // store already has a record of this container -> refresh
                this.store.remove(this.store.getAt(this.store.findExact('path', container.path)));
            }

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

        if (Ext.isObject(container.get('name'))) {
            // sanitize name for display
            container.set('name', container.get('name').name);
        }

        container.set('is_container_node', !!!Tine.Tinebase.container.pathIsContainer(container.get('path')));
        this.selectedContainer = container.data;
        
        // make sure other is _last_ entry in list
        this.store.remove(this.otherRecord);
        this.store.add(this.otherRecord);

        Tine.widgets.container.SelectionComboBox.superclass.setValue.call(this, container.id);
        
        if (container.account_grants) {
            this.setDisabled(! container.account_grants.deleteGrant);
        }
        
        if (this.qtip) {
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
Ext.reg('tinewidgetscontainerselectcombo', Tine.widgets.container.SelectionComboBox);

Tine.widgets.form.RecordPickerManager.register('Tinebase', 'Container', Tine.widgets.container.SelectionComboBox);
