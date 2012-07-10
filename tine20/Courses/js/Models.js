/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Courses', 'Tine.Courses.Model');

/**
 * @type {Array}
 * Course model fields
 */
Tine.Courses.Model.CourseArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'group_id' },
    { name: 'name' },
    { name: 'type' },
    { name: 'internet' },
    { name: 'fileserver' },
    // group fields
    { name: 'description' },
    { name: 'members' },
    // tine 2.0 notes + tags
    { name: 'notes' },
    { name: 'tags' }
]);

/**
 * @type {Tine.Tinebase.Course}
 * record definition
 */
Tine.Courses.Model.Course = Tine.Tinebase.data.Record.create(Tine.Courses.Model.CourseArray, {
    appName: 'Courses',
    modelName: 'Course',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Course', 'Courses', n);
    recordName: 'Course',
    recordsName: 'Courses',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists'
});

Tine.Courses.Model.Course.getDefaultData = function() {
    return {
        type: Tine.Courses.registry.get('defaultType')
    };
};

/**
 * get filtermodel of course model
 * 
 * @namespace Tine.Courses.Model
 * @static
 * @return {Array} filterModel definition
 * 
 * TODO only add internet access filter if internet_group config is available
 */ 
Tine.Courses.Model.Course.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Courses');
    
    return [
        {label: _('Quick search'),    field: 'query',       operators: ['contains']},
        {label: app.i18n._('Name'),   field: 'name'},
        {
            label: app.i18n._('Internet Access'),
            field: 'internet',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'internetAccess', 
            defaultOperator: 'in'
        },
        {filtertype: 'foreignrecord', 
            app: app,
            foreignRecordClass: Tine.Tinebase.Model.Department,
            ownField: 'type',
            operators: ['equals']
        }
    ];
};


/**
 * @type {Array}
 * Coursetype model fields
 */
Tine.Courses.Model.CourseTypeArray = [
    { name: 'id' },
    { name: 'name' }
];

/**
 * @type {Tine.Tinebase.CourseType}
 * record definition
 */
Tine.Courses.Model.CourseType = Tine.Tinebase.data.Record.create(Tine.Courses.Model.CourseTypeArray, {
    appName: 'Courses',
    modelName: 'CourseType',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Course Type', 'Course Types', n);
    recordName: 'Course Type',
    recordsName: 'Courses Types'
});
