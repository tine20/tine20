/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Courses.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Courses');

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
     * 
     * ???
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        //{field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        //{field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        //{field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

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
    recordClass: Tine.Courses.Model.CourseType
});
