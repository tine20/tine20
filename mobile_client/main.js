/*
 * Tine 2.0
 * 
 * @package     mobileClient
 * @subpackage  Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.onReady(function() {
    var connection = new Tine.mobileClient.Connection({
        url: '/tine20/index.php'
    });
    
    connection.login('tine20admin', 'lars', function() {
        new Ext.Viewport({
            layout: 'fit',
            items: {
                xtype: 'panel',
                layout: 'fit',
                buttonAlign: 'center',
                tbar: [
                    {text: 'Settings', handler: function() {}},
                    '->',
                    {text: 'Help', handler: function() {}}
                ],
                buttons: [
                    {text: 'List', handler: function() {}, pressed: true },
                    {text: 'Day', handler: function() {}},
                    {text: 'Month', handler: function() {}}
                ],
                bbar: [
                    {text: 'foo', handler: function() {}},
                    {text: 'bar', handler: function() {}},
                ],
                items: new Tine.mobileClient.Tasks.MainGrid({})
            }
        });
    });
});

