/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.form');

/**
 * A combination of datefield and timefield
 */
Ext.ux.form.DateTimeField = Ext.extend(Ext.form.DateField, {
    onRender: function(ct, position) {
        Ext.ux.form.DateTimeField.superclass.onRender.call(this, ct, position);
        
        //console.log(ct);
    }
});
Ext.reg('datetimefield', Ext.ux.form.DateTimeField);