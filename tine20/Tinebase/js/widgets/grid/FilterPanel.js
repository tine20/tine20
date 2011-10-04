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
    
    // @TODO find quickfilter plungin an pick quickFilterField and criteriaIgnores from it
    
    // the plugins woun't work there
    delete this.filterToolbarConfig.plugins;
    
    // route filterPlugin stuff
    // NOTE: their is no spechial filterPanel, each filterpanel could be closed  at any time
    //       ?? what does this mean for quickfilterplugin???
    //       -> we cant mirror fileds or need to mirror the field from the active tbar
    //       -> mhh, better deactivate as soon as we have more than one tbar
    //       -> don't sync, but fetch with this wrapper!
    
    // become filterPlugin
    Ext.applyIf(this, new Tine.widgets.grid.FilterPlugin());
//    this.filterToolbarConfig.filterPluginCmp = this;
    
    this.filterPanels = [];
    
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
    
    cls: 'tw-ftb-filterpanel',
    layout: 'border',
    border: false,

//    height: 100,
    
    initComponent: function() {
        var filterPanel = this.addFilterPanel();
        this.activeFilterPanel = filterPanel;
        
        this.initQuickFilterField();
        
        this.items = [{
            region: 'east',
            width: 200,
            border: false,
            layout: 'fit',
            items: [new Tine.widgets.grid.FilterStructureTreePanel({filterPanel: this})]
        }, {
            region: 'center',
            border: false,
            layout: 'card',
            activeItem: 0,
            items: [filterPanel],
            autoScroll: true,
            listeners: {
                scope: this,
                afterlayout: this.manageHeight
            }
        }];
        
        Tine.widgets.grid.FilterPanel.superclass.initComponent.call(this);
        
    },
    
    manageHeight: function() {
        if (this.rendered) {
            var tbHeight = this.activeFilterPanel.getHeight(),
                northHeight = this.layout.north ? this.layout.north.panel.getHeight() : 0,
                eastHeight = this.layout.east && this.layout.east.panel.getEl().child('ul') ? (this.layout.east.panel.getEl().child('ul').getHeight() + 28) : 0,
                height = Math.min(Math.max(eastHeight, tbHeight + northHeight), 120);
            
            this.setHeight(height);
            
            // manage scrolling
            if (this.layout.center) {
                this.layout.center.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
            }
            if (this.layout.east) {
                this.layout.east.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
            }
            this.ownerCt.layout.layout();
        }
    },
    
    onAddFilterPanel: function() {
        var filterPanel = this.addFilterPanel();
        this.setActiveFilterPanel(filterPanel);
    },
    
    addFilterPanel: function() {
        var filterPanel = new Tine.widgets.grid.FilterToolbar(this.filterToolbarConfig);
        filterPanel.onFilterChange = this.onFilterChange.createDelegate(this);
        
        this.filterPanels[filterPanel.id] = filterPanel;
        
        return filterPanel;
    },
    
    setActiveFilterPanel: function(filterPanel) {
        filterPanel = Ext.isString(filterPanel) ? this.filterPanels[filterPanel] : filterPanel;
        this.activeFilterPanel = filterPanel;
        
        this.layout.center.panel.add(filterPanel);
        this.layout.center.panel.layout.setActiveItem(filterPanel.id);
        
        filterPanel.doLayout();
        this.manageHeight.defer(100, this);
    },
    
    // NOTE: their is no spechial filterPanel, each filterpanel could be closed  at any time
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
        
        this.criteriaText = new Ext.Panel({
            border: 0,
//            hidden: true,
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
            text: _('show details'),
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
     * called when the details toggle button gets toggled
     * 
     * @param {Ext.Button} btn
     */
    onDetailsToggle: function(btn) {
        this[btn.pressed ? 'show' : 'hide']();
        this.quickFilter.setDisabled(btn.pressed);
        try {
//        this.manageCriteriaText();
        } catch (e) {
            console.log(e);
            console.log(e.stack);
            
        }
        this.activeFilterPanel.doLayout();
        this.manageHeight();
    },
    
    /**
     * manages the criteria text
     */
    manageCriteriaText: function() {
        var moreCriterias = false,
            filterPanelCount = 0,
            criterias = [];
            
        // count filterPanels
        for (var id in this.filterPanels) {filterPanelCount++;}
        
        if (! filterPanelCount > 1) {
            moreCriterias = true;
            
        } else {
            // not more filters only if we hove one filterPanel & only one queryFilter in it (or implicit filters)
            this.activeFilterPanel.filterStore.each(function(f) {
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
        
        this.criteriaText.update(moreCriterias ? _(this.moreFiltersActiveText) : '');
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
    
    getValue: function() {
        
        var filters = [];
        for (var id in this.filterPanels) {
            if (this.filterPanels.hasOwnProperty(id) && this.filterPanels[id].isActive) {
                filters.push({'condition': 'AND', 'filters': this.filterPanels[id].getValue(), 'id': id})
            }
        }
        
        return filters.length == 1 ? filters[0].filters : [{'condition': 'OR', 'filters': filters}];
    },
    
    setValue: function(value) {
        if (! value.condition || value.condition == 'AND') {
            return this.activeFilterPanel.setValue(value);
        } else {
            // @TODO
        }
    }
});