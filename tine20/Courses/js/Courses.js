/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Courses');

Tine.Courses.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Course',
    contentTypes: [
        {model: 'Course',  requiredRight: null, singularContainerMode: true}
    ]
});

Tine.Courses.CourseFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Courses.CourseFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Courses.CourseFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Courses_Model_CourseFilter'}]
});


/**
 * default backend
 */
Tine.Courses.coursesBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'Course',
    recordClass: Tine.Courses.Model.Course,

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

/**
 * default backend
 */
Tine.Courses.courseTypeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'CourseType',
    recordClass: Tine.Courses.Model.CourseType
});
