/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountGridPanel.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Courses');

/**
 * Course grid panel
 */
Tine.Courses.CourseGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Courses.Model.Course,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'name'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Courses.coursesBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Courses.CourseGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n.ngettext('Course', 'Courses', 1),    field: 'query',       operators: ['contains']}
                //new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'query',
             filters: []
        });
    },
    
    /**
     * returns cm
     */
    getColumns: function(){
        return [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 200,
            sortable: true,
            dataIndex: 'name'
        },{
            id: 'type',
            header: this.app.i18n._("Type"),
            width: 150,
            sortable: true,
            dataIndex: 'type',
            renderer: this.courseTypeRenderer
        },{
            id: 'internet',
            header: this.app.i18n._("Internet Access"),
            width: 150,
            sortable: true,
            dataIndex: 'internet',
            renderer: Tine.Tinebase.common.booleanRenderer
        },{
            id: 'fileserver',
            header: this.app.i18n._("Fileserver Access"),
            width: 150,
            sortable: true,
            dataIndex: 'fileserver',
            renderer: Tine.Tinebase.common.booleanRenderer
        }];
    },
    
    courseTypeRenderer: function(value) {
        return (value.name);
    }
});
