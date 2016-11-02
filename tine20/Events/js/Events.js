/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Events');

// register grants for events containers
Tine.widgets.container.GrantsManager.register('Events_Model_Event', ['read', 'add', 'edit', 'delete']);
