/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Courses');
Tine.Courses.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Course',
    westPanelXType: 'tine.courses.treepanel'
});

Tine.Courses.TreePanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    
    filter: [{field: 'model', operator: 'equals', value: 'Courses_Model_CourseFilter'}],

    // quick hack to get filter saving grid working
    //recordClass: Tine.Courses.Model.Course,
    
    initComponent: function() {
        this.filterMountId = 'Course';
        
        this.root = {
            id: 'root',
            leaf: false,
            expanded: true,
            children: [{
                text: this.app.i18n._('Courses'),
                id: 'Course',
                iconCls: 'CoursesCourse',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Courses'),
                    id: 'allrecords',
                    leaf: true
                }]
            }]
        };
        
    	Tine.Courses.TreePanel.superclass.initComponent.call(this);
        
    	/*
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter != true) {
                var contentType = node.getPath().split('/')[2];
                
                this.app.getMainScreen().activeContentType = contentType;
                this.app.getMainScreen().show();
            }
        }, this);
        */
	},
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Courses.TreePanel.superclass.afterRender.call(this);
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/' + type + '/allrecords');
        this.selectPath('/root/' + type + '/allrecords');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({});
        }
        
        return this.filterPlugin;
    },
    
    getFavoritesPanel: function() {
        return this;
    }
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * ???
     */
//    getFilterPlugin: function() {
//        if (!this.filterPlugin) {
//            var scope = this;
//            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
//                getValue: function() {
//                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
//                    return [
//                        //{field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
//                        //{field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
//                        //{field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
//                    ];
//                }
//            });
//        }
//        
//        return this.filterPlugin;
//    }
});

Ext.reg('tine.courses.treepanel', Tine.Courses.TreePanel);

//Tine.Courses.FilterPanel = Tine.widgets.persistentfilter.PickerPanel

/**
 * default backend
 */
Tine.Courses.coursesBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'Course',
    recordClass: Tine.Courses.Model.Course
});

/**
 * default backend
 */
Tine.Courses.courseTypeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'CourseType',
    recordClass: Tine.Courses.Model.CourseType,
    
    /**
     * deletes multiple records identified by their ids
     * 
     * @param   {Array} records Array of records or ids
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecords: function(records, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 's';
        options.params.ids = this.getRecordIds(records);
        
        // increase timeout to 20 minutes
        options.timeout = 1200000;
        
        return this.doXHTTPRequest(options);
    }
});
