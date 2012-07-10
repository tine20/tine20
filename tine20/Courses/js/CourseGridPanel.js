/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Courses');

/**
 * Course grid panel
 */
Tine.Courses.CourseGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Courses.Model.Course,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'name'
    },
    
    /**
     * init Tine.Courses.CourseGridPanel
     */
    initComponent: function() {
        this.recordProxy = Tine.Courses.coursesBackend;
        
        this.gridConfig.columns = this.getColumns();
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Courses.CourseGridPanel.superclass.initComponent.call(this);
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
        }
        ];
    },
    
    /**
     * course type renderer
     * 
     * @param {} value
     * @return {}
     */
    courseTypeRenderer: function(value) {
        return (value.name);
    },
    
    /**
     * update access of course(s)
     * 
     * @param {Ext.Action} button
     * @param {} event
     */
    updateAccessHandler: function(button, event) {
        
        var courses = this.grid.getSelectionModel().getSelections();
        var toUpdateIds = [];
        for (var i = 0; i < courses.length; ++i) {
            toUpdateIds.push(courses[i].data.id);
        }
        
        Ext.Ajax.request({
            params: {
                method: 'Courses.updateAccess',
                ids: toUpdateIds,
                type: button.type,
                access: button.access
            },
            success: function(_result, _request) {
                this.store.load();
            },
            failure: function(result, request){
                Ext.MessageBox.alert(
                    this.app.i18n._('Failed'), 
                    this.app.i18n._('Some error occured while trying to update the courses.')
                );
            },
            scope: this
        });
    }
});
