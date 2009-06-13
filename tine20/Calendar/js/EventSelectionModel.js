/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.EventSelectionModel
 * @extends Ext.tree.MultiSelectionModel
 * Selection model for a calendar views.
 */
Tine.Calendar.EventSelectionModel = Ext.extend(Ext.tree.MultiSelectionModel, {
    init: function(view) {
        view.getTreeEl = function() {
            return view.el;
        }
        Tine.Calendar.EventSelectionModel.superclass.init.call(this, view);
    },
    
    /**
     * Returns an array of the selected events
     * @return {Array}
     */
    getSelectedEvents: function() {
        return this.getSelectedNodes();
    }
});

