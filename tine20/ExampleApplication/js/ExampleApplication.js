/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.ExampleApplication');


/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.ExampleApplication.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Example Application');
    }
});

/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.ExampleApplication.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    
    activeContentType: 'ExampleRecord'
});

    
/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.ExampleApplication.TreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'ExampleApplication_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord
});

