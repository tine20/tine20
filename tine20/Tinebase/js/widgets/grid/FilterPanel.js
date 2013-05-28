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
    
    // @TODO find quickfilter plugin an pick quickFilterField and criteriaIgnores from it
    this.criteriaIgnores = config.criteriaIgnores || [
        {field: 'container_id', operator: 'equals', value: {path: '/'}},
        {field: 'query',        operator: 'contains',    value: ''}
    ];
    
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
     * @cfg {String} quickFilterField
     * 
     * name of quickfilter filed in filter definitions
     */
    quickFilterField: 'query',
    
    /**
     * @cfg {Array} criterias to ignore
     */
    criteriaIgnores: null,
    
    /**
     * @cfg {String} moreFiltersActiveText
     */
    moreFiltersActiveText: 'Attention: There are more filters active!', //_('Attention: There are more filters active!')
    
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
    
    cls: 'tw-ftb-filterpanel',
    layout: 'border',
    border: false,
    
    initComponent: function() {
        
        var filterPanel = this.addFilterPanel();
        this.activeFilterPanel = filterPanel;
        
        this.initQuickFilterField();
        
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
    
    manageHeight: function() {
        if (this.rendered) {
            var tbHeight = this.activeFilterPanel.getHeight(),
                northHeight = this.layout.north ? this.layout.north.panel.getHeight() : 0,
                eastHeight = this.layout.east && this.layout.east.panel.getEl().child('ul') ? (this.layout.east.panel.getEl().child('ul').getHeight() + 28) : 0,
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
        
        this.layout.center.panel.add(filterPanel);
        this.layout.center.panel.layout.setActiveItem(filterPanel.id);
        
        filterPanel.doLayout();
        if (filterPanel.activeSheet) {
         // solve layout problems (#6332)
            filterPanel.setActiveSheet(filterPanel.activeSheet);
        }
        this.manageHeight.defer(100, this);
        
        this.fireEvent('filterpanelactivate', this, filterPanel);
    },
    
    // NOTE: there is no special filterPanel, each filterpanel could be closed  at any time
    //       ?? what does this mean for quickfilterplugin???
    //       -> we cant mirror fileds or need to mirror the field from the active tbar
    //       -> mhh, better deactivate as soon as we have more than one tbar
    //       -> don't sync, but fetch with this wrapper!
    initQuickFilterField: function() {
        var stateful = !! this.filterToolbarConfig.recordClass;
        // autogenerate stateId
        if (stateful) {
            var stateId = this.filterToolbarConfig.recordClass.getMeta('appName') + '-' + this.filterToolbarConfig.recordClass.getMeta('recordName') + '-FilterToolbar-QuickfilterPlugin';
        }
        
        this.quickFilter = new Ext.ux.SearchField({
            width: 300,
            enableKeyEvents: true
        });
        
        this.quickFilter.onTrigger1Click = this.quickFilter.onTrigger1Click.createSequence(this.onQuickFilterClear, this);
        this.quickFilter.onTrigger2Click = this.quickFilter.onTrigger2Click.createSequence(this.onQuickFilterTrigger, this);
        
        this.criteriaText = new Ext.Panel({
            border: 0,
            html: '',
            bodyStyle: {
                border: 0,
                background: 'none', 
                'text-align': 'left', 
                'line-height': '11px'
            }
        });
        
        this.detailsToggleBtn = new Ext.Button({
            style: {'margin-top': '2px'},
            enableToggle: true,
            text: _('show details'), //'Advanced Search'
            tooltip: _('Always show advanced filters'),
            scope: this,
            handler: this.onDetailsToggle,
            stateful: stateful,
            stateId : stateful ? stateId : null,
            getState: function() {
                return {detailsButtonPressed: this.pressed};
            },
            applyState: function(state) {
                if (state.detailsButtonPressed) {
                    this.toggle(state.detailsButtonPressed);
                }
            },
            stateEvents: ['toggle'],
            listeners: {
                scope: this,
                render: function() {
                    // limit width of this.criteriaText
                    this.criteriaText.setWidth(this.quickFilterGroup.getWidth() - this.detailsToggleBtn.getWidth());
                    this.onDetailsToggle(this.detailsToggleBtn);
                }
            }
        });
    },
    
    /**
     * called when the (external) quick filter is cleared
     */
    onQuickFilterClear: function() {
        this.quickFilter.reset();
        this.quickFilter.setValue('');
        this.syncQuickFilterFields(true, '');
        this.activeFilterPanel.onFiltertrigger.call(this.activeFilterPanel);
    },
    
    /**
     * called when the (external) filter triggers filter action
     */
    onQuickFilterTrigger: function() {
        this.activeFilterPanel.onFiltertrigger.call(this.activeFilterPanel);
        this.activeFilterPanel.onFilterRowsChange.call(this.activeFilterPanel);
    },
    
    /**
     * called when the details toggle button gets toggled
     * 
     * @param {Ext.Button} btn
     */
    onDetailsToggle: function(btn) {
        this[btn.pressed ? 'show' : 'hide']();
        this.quickFilter.setDisabled(btn.pressed);
        this.manageCriteriaText();
        
        this.syncQuickFilterFields(btn.pressed);
        
        this.activeFilterPanel.doLayout();
        this.manageHeight();
    },
    
    /**
     * synchronizes the quickfilter field with the  coreesponding filter of the active filter toolbar
     * 
     * @param {Bool} fromQuickFilter
     * @param {String} value
     */
    syncQuickFilterFields: function(fromQuickFilter, value) {
        
        if (fromQuickFilter === undefined) {
            fromQuickFilter = true;
        }
        
        if (fromQuickFilter) {
            var val = (value !== undefined) ? value : this.quickFilter.getValue(),
                quickFilter;
                
            this.quickFilter.setValue('');
            // find quickfilterrow
            this.activeFilterPanel.filterStore.each(function(filter) {
                if (filter.get('field') == this.quickFilterField) {
                    quickFilter = filter;
                    quickFilter.set('value', val);
                    quickFilter.formFields.value.setValue(val);
                    return false;
                }
            }, this);
        
            if (! quickFilter && val) {
                quickFilter = this.activeFilterPanel.addFilter(new this.activeFilterPanel.record({field: this.quickFilterField, value: val}));
            }
        } else {
            this.activeFilterPanel.filterStore.each(function(filter) {
                if (filter.get('field') == this.quickFilterField) {
                    this.quickFilter.setValue(filter.formFields.value.getValue());
                    filter.set('value', '');
                    return false;
                }
            }, this);
        }
    },
    
    /**
     * manages the criteria text
     */
    manageCriteriaText: function() {
        var moreCriterias = false,
            filterPanelCount = 0,
            criterias = [];
            
        // count filterPanels
        for (var id in this.filterPanels) {if (this.filterPanels.hasOwnProperty(id)) {filterPanelCount++;}}
        
        if (! filterPanelCount > 1) {
            moreCriterias = true;
            
        } else {
            // not more filters only if we hove one filterPanel & only one queryFilter in it (or implicit filters)
            this.activeFilterPanel.filterStore.each(function(filter) {
                var f = this.activeFilterPanel.getFilterData(filter);
                
                for (var i=0, criteria, ignore; i<this.criteriaIgnores.length; i++) {
                    criteria = this.criteriaIgnores[i];
                    ignore = true;
                    
                    for (var p in criteria) {
                        if (criteria.hasOwnProperty(p)) {
                            if (Ext.isString(criteria[p]) || Ext.isEmpty(f[p]) ) {
                                ignore &= f.hasOwnProperty(p) && f[p] === criteria[p];
                            } else {
                                for (var pp in criteria[p]) {
                                    if (criteria[p].hasOwnProperty(pp)) {
                                        ignore &= f.hasOwnProperty(p) && typeof f[p].hasOwnProperty == 'function' && f[p].hasOwnProperty(pp) && f[p][pp] === criteria[p][pp];
                                    }
                                }
                            }
                        }
                    }
                    
                    if (ignore) {
                        // don't judge them as criterias
                        return;
                    }
                }
                
                if (this.activeFilterPanel.filterModelMap[f.field]) {
                    criterias.push(this.activeFilterPanel.filterModelMap[f.field].label);
                } else {
                    // no idea how to get the filterplugin for non ftb itmes
                    criterias.push(f.field);
                }
            }, this);
            moreCriterias = criterias.length > 0;
        }
        
        moreCriterias = this.hidden ? moreCriterias : false;
        
        if (this.criteriaText && this.criteriaText.rendered) {
            this.criteriaText.update(moreCriterias ? _(this.moreFiltersActiveText) : '');
        }
    },
    
    /**
     * gets the (extra) quick filter toolbar items
     * 
     * @return {Ext.ButtonGroup}
     */
    getQuickFilterField: function() {
        if (! this.quickFilterGroup) {
            this.quickFilterGroup = new Ext.ButtonGroup({
                columns: 1,
                items: [
                    this.quickFilter, {
                        xtype: 'toolbar',
                        style: {border: 0, background: 'none'},
                        items: [this.criteriaText, '->', this.detailsToggleBtn]
                    }
                ]
            });
        }
        
        return this.quickFilterGroup;
    },
    
    getQuickFilterPlugin: function() {
        return this;
    },
    
    getValue: function() {
        var quickFilterValue = this.quickFilter.getValue(),
            filters = [];
        
        if (quickFilterValue) {
            filters.push({field: this.quickFilterField, operator: 'contains', value: quickFilterValue, id: 'quickFilter'});
            
            // add implicit / ignored fields (important e.g. for container_id)
            Ext.each(this.criteriaIgnores, function(criteria) {
                if (criteria.field != this.quickFilterField) {
                    var filterIdx = this.activeFilterPanel.filterStore.find('field', criteria.field),
                        filter = filterIdx >= 0  ? this.activeFilterPanel.filterStore.getAt(filterIdx) : null,
                        filterModel = filter ? this.activeFilterPanel.getFilterModel(filter) : null;
                    
                    if (filter) {
                        filters.push(Ext.isFunction(filterModel.getFilterData) ? filterModel.getFilterData(filter) : this.activeFilterPanel.getFilterData(filter));
                    }
                }
                
            }, this);
            
            return filters;
        }
        
        for (var id in this.filterPanels) {
            if (this.filterPanels.hasOwnProperty(id) && this.filterPanels[id].isActive) {
                filters.push({'condition': 'AND', 'filters': this.filterPanels[id].getValue(), 'id': id, label: this.filterPanels[id].title});
            }
        }
        
        // NOTE: always trigger a OR condition, otherwise we sould loose inactive FilterPanles
        //return filters.length == 1 ? filters[0].filters : [{'condition': 'OR', 'filters': filters}];
        return [{'condition': 'OR', 'filters': filters}];
    },
    
    setValue: function(value) {
        
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
//            this.activeFilterPanel.setTitle(String.format(_('Criteria {0}'), ++this.criteriaCount));
            this.activeFilterPanel.setTitle(this.activeFilterPanel.generateTitle());
            for (var id in this.filterPanels) {
                if (this.filterPanels.hasOwnProperty(id)) {
                    if (this.filterPanels[id] != this.activeFilterPanel) {
                        this.removeFilterPanel(this.filterPanels[id]);
                    }
                }
            }
            
            this.activeFilterPanel.setValue(value);
            
            var quickFilterValue = this.activeFilterPanel.filterStore.getById('quickFilter');
            if (quickFilterValue) {
                this.quickFilter.setValue(quickFilterValue.get('value'));
                this.activeFilterPanel.supressEvents = true;
                this.activeFilterPanel.deleteFilter(quickFilterValue);
                this.activeFilterPanel.supressEvents = false;
            }
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
        
        this.manageCriteriaText();
    }
});