/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         use another namespace?
 * TODO         add search combobox?
 */
Ext.ns('Tine.widgets.grid');

/**
 * Model of a Department filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.DepartmentFilterModel
 * @extends     Ext.Component
 * @constructor
 */
Tine.widgets.grid.DepartmentFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    isForeignFilter: true,
    foreignField: 'name',
    ownField: 'type',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.DepartmentFilterModel.superclass.initComponent.call(this);
        
        this.subFilterModels = [];
        
        this.label = _("Department - Name");
        this.operators = ['contains'];
    },
    
    getSubFilters: function() {
        return this.subFilterModels;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.department'] = Tine.widgets.grid.DepartmentFilterModel;
