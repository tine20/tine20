/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

Tine.widgets.grid.FilterPanel = function(config) {
    this.filterToolbarConfig = config;
    Ext.copyTo(this, config, 'useQuickFilter,quickFilterConfig');

    // the plugins won't work there
    delete this.filterToolbarConfig.plugins;
    
    // apply some filterPanel configs
    Ext.each(['onFilterChange', 'getAllFilterData'], function(p) {
        if (config.hasOwnProperty(p)) {
            this[p] = config[p];
        }
    }, this);
    
    // become filterPlugin
    Ext.applyIf(this, new Tine.widgets.grid.FilterPlugin());
    
    this.filterPanels = [];
    
    this.addEvents(
        /**
         * @event filterpaneladded
         * Fires when a filterPanel is added
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel added
         */
        'filterpaneladded',
        
        /**
         * @event filterpanelremoved
         * Fires when a filterPanel is removed
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel removed
         */
        'filterpanelremoved',
        
        /**
         * @event filterpanelactivate
         * Fires when a filterPanel is activated
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel activated
         */
        'filterpanelactivate'
    );
    Tine.widgets.grid.FilterPanel.superclass.constructor.call(this, {});
};

Ext.extend(Tine.widgets.grid.FilterPanel, Ext.Panel, {

    /**
     * @property activeFilterPanel
     * @type Tine.widgets.grid.FilterToolbar
     */
    activeFilterPanel: null,
    
    /**
     * @property filterPanels map filterPanelId => filterPanel
     * @type Object
     */
    filterPanels: null,
    
    /**
     * @property criteriaCount
     * @type Number
     */
    criteriaCount: 0,

    useQuickFilter: true,

    cls: 'tw-ftb-filterpanel',
    layout: 'border',
    border: false,

    /**
     * We expect the filter panel to be layouted
     */
    forceLayout: true,
    
    initComponent: function() {
        
        var filterPanel = this.addFilterPanel();
        this.filterModelMap = filterPanel.filterModelMap;
        this.activeFilterPanel = filterPanel;
        
        // this.initQuickFilterField();

        this.advancedSearchEnabled = Tine.Tinebase.featureEnabled('featureShowAdvancedSearch') &&
            this.filterToolbarConfig.app.enableAdvancedSearch;

        this.recordClass = this.filterToolbarConfig.recordClass;

        this.items = [{
            region: 'east',
            width: 200,
            border: false,
            layout: 'fit',
            split: true,
            items: [new Tine.widgets.grid.FilterStructureTreePanel({filterPanel: this})]
        }, {
            region: 'center',
            border: false,
            layout: 'card',
            activeItem: 0,
            items: [filterPanel],
            autoScroll: false,
            listeners: {
                scope: this,
                afterlayout: this.manageHeight
            }
        }];
        
        Tine.widgets.grid.FilterPanel.superclass.initComponent.call(this);

        if (this.useQuickFilter) {
            this.quickFilterPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin(Ext.apply({
                syncFields: false,
            }, this.quickFilterConfig));
            this.quickFilterPlugin.init(this);
        }
    },
    
    /**
     * is persiting this filterPanel is allowed
     * 
     * @return {Boolean}
     */
    isSaveAllowed: function() {
        return this.activeFilterPanel.allowSaving;
    },

    getAllFilterData: Tine.widgets.grid.FilterToolbar.prototype.getAllFilterData,
    storeOnBeforeload: Tine.widgets.grid.FilterToolbar.prototype.storeOnBeforeload,

    onFilterRowsChange: function() {
        this.activeFilterPanel.doLayout();
        this.manageHeight();
    },

    onFiltertrigger: function() {
        this.activeFilterPanel.onFiltertrigger();
    },

    manageHeight: function() {
        if (this.rendered && this.activeFilterPanel.rendered) {
            var tbHeight = this.activeFilterPanel.getHeight(),
                northHeight = this.layout.north ? this.layout.north.panel.getHeight() + 1 : 0,
                eastHeight = this.layout.east && this.layout.east.panel.getEl().child('ul') ? ((this.layout.east.panel.getEl().child('ul').getHeight()) + 29) : 0,
                height = Math.min(Math.max(eastHeight, tbHeight + northHeight), 120);
            
            this.setHeight(height);

            // manage scrolling
            if (this.layout.center && tbHeight > 120) {
                this.layout.center.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
                this.layout.center.panel.el.child('div[class^="x-panel-body"]', false).applyStyles('overflow-y: auto');
            }
            if (this.layout.east && eastHeight > 120) {
                this.layout.east.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
            }
            this.ownerCt.layout.layout();
        }
    },
    
    onAddFilterPanel: function() {
        var filterPanel = this.addFilterPanel();
        this.setActiveFilterPanel(filterPanel);
    },
    
    addFilterPanel: function(config) {
        config = config || {};
        
        var filterPanel = new Tine.widgets.grid.FilterToolbar(Ext.apply({}, this.filterToolbarConfig, config));
        filterPanel.onFilterChange = this.onFilterChange.createDelegate(this);
        
        this.filterPanels[filterPanel.id] = filterPanel;
        this.criteriaCount++;
        
        if (this.criteriaCount > 1 && filterPanel.title == filterPanel.generateTitle()) {
            filterPanel.setTitle(filterPanel.title + ' ' + this.criteriaCount);
        }
        this.fireEvent('filterpaneladded', this, filterPanel);
        return filterPanel;
    },
    
    /**
     * remove filter panel
     * 
     * @param {mixed} filterPanel
     */
    removeFilterPanel: function(filterPanel) {
        filterPanel = Ext.isString(filterPanel) ? this.filterPanels[filterPanel] : filterPanel;
        
        if (! this.filterPanels[filterPanel.id].destroying) {
            this.filterPanels[filterPanel.id].destroy();
        }
        
        delete this.filterPanels[filterPanel.id];
        this.criteriaCount--;
        
        this.fireEvent('filterpanelremoved', this, filterPanel);
        
        for (var id in this.filterPanels) {
            if (this.filterPanels.hasOwnProperty(id)) {
                return this.setActiveFilterPanel(this.filterPanels[id]);
            }
        }
    },
    
    setActiveFilterPanel: function(filterPanel) {
        filterPanel = Ext.isString(filterPanel) ? this.filterPanels[filterPanel] : filterPanel;
        this.activeFilterPanel = filterPanel;

        if (this.layout.center) {
            this.layout.center.panel.add(filterPanel);
            this.layout.center.panel.layout.setActiveItem(filterPanel.id);
        }
        
        filterPanel.doLayout();
        if (filterPanel.activeSheet) {
         // solve layout problems (#6332)
            filterPanel.setActiveSheet(filterPanel.activeSheet);
        }
        this.manageHeight.defer(100, this);
        
        this.fireEvent('filterpanelactivate', this, filterPanel);
    },

    getQuickFilterField: function() {
        return this.quickFilterPlugin.getQuickFilterField();
    },

    getQuickFilterPlugin: function() {
        return this.quickFilterPlugin;
    },

    getValue: function() {
        var filters = [];
        

        for (var id in this.filterPanels) {
            if (this.filterPanels.hasOwnProperty(id) && this.filterPanels[id].isActive) {
                filters.push({'condition': 'AND', 'filters': this.filterPanels[id].getValue(), 'id': id, label: Ext.util.Format.htmlDecode(this.filterPanels[id].title)});
            }
        }
        
        // NOTE: always trigger a OR condition, otherwise we sould loose inactive FilterPanles
        return [{'condition': 'OR', 'filters': filters, id: 'FilterPanel'}];
    },

    setValue: function(value) {
        // save last filter ?
        var prefs;
        if ((prefs = this.filterToolbarConfig.app.getRegistry().get('preferences')) && prefs.get('defaultpersistentfilter') == '_lastusedfilter_') {
            var lastFilterStateName = this.filterToolbarConfig.recordClass.getMeta('appName') + '-' + this.filterToolbarConfig.recordClass.getMeta('recordName') + '-lastusedfilter';
            
            if (Ext.encode(Ext.state.Manager.get(lastFilterStateName)) != Ext.encode(value)) {
                Tine.log.debug('Tine.widgets.grid.FilterPanel::setValue save last used filter');
                Ext.state.Manager.set(lastFilterStateName, value);
            }
        }
        
        // NOTE: value is always an array representing a filterGroup with condition AND (server limitation)!
        //       so we need to route "alternate criterias" (OR on root level) through this filterGroup for transport
        //       and scrape them out here -> this also means we whipe all other root level filters (could only be implicit once)
        var alternateCriterias = false;
        Ext.each(value, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                value = filterData.filters;
                alternateCriterias = true;
                return false;
            }
        }, this);
        
        
        if (! alternateCriterias) {
            // reset criterias
//            this.criteriaCount = 0;
//            this.activeFilterPanel.setTitle(String.format(i18n._('Criteria {0}'), ++this.criteriaCount));
            this.activeFilterPanel.setTitle(this.activeFilterPanel.generateTitle());
            for (var id in this.filterPanels) {
                if (this.filterPanels.hasOwnProperty(id)) {
                    if (this.filterPanels[id] != this.activeFilterPanel) {
                        this.removeFilterPanel(this.filterPanels[id]);
                    }
                }
            }
            
            this.activeFilterPanel.setValue(value);

        } 
        
        // OR condition on root level
        else {
            var keepFilterPanels = [],
                activeFilterPanel = this.activeFilterPanel;
            
            Ext.each(value, function(filterData) {
                var filterPanel;
                
                // refresh existing filter panel
                if (filterData.id && this.filterPanels.hasOwnProperty(filterData.id)) {
                    filterPanel = this.filterPanels[filterData.id];
                }
                
                // create new filterPanel
                else {
                    // NOTE: don't use filterData.id here, it's a ext-comp-* which comes from a different session
                    // and might be a totally different element yet.
                    filterPanel = this.addFilterPanel();
                    this.setActiveFilterPanel(filterPanel);
                }
                
                filterPanel.setValue(filterData.filters);
                keepFilterPanels.push(filterPanel.id);
                
                if (filterData.label) {
                    filterPanel.setTitle(Ext.util.Format.htmlEncode(filterData.label));
                }
                
            }, this);
            
            // (re)activate filterPanel
            this.setActiveFilterPanel(keepFilterPanels.indexOf(activeFilterPanel.id) > 0 ? activeFilterPanel : keepFilterPanels[0]);
            
            
            // remove unused panels
            for (var id in this.filterPanels) {
                if (this.filterPanels.hasOwnProperty(id) && keepFilterPanels.indexOf(id) < 0 && this.filterPanels[id].isActive == true) {
                    this.removeFilterPanel(id);
                }
            }
            
        }
    }
});