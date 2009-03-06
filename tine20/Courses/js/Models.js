/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Models.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
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
Tine.Courses.Model.Course = Tine.Tinebase.Record.create(Tine.Courses.Model.CourseArray, {
    appName: 'Courses',
    modelName: 'Course',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Course', 'Courses', n);
    recordName: 'Course',
    recordsName: 'Courses',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

Tine.Courses.Model.Course.getDefaultData = function() { 
    return {
    	/*
        is_open: 1,
        is_billable: true
        */
    }
};
