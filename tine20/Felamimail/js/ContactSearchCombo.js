/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ContactSearchCombo
 * @extends     Tine.Addressbook.SearchCombo
 * 
 * <p>Email Search ComboBox</p>
 * <p></p>
 * <pre></pre>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.ContactSearchCombo
 */
Tine.Felamimail.ContactSearchCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    /**
     * @cfg {Boolean} forceSelection
     */
    forceSelection: false,
    
    /**
     * @private
     */
    initComponent: function() {
        // add additional filter to show only contacts with email addresses
        this.additionalFilters = [{field: 'email_query', operator: 'contains', value: '@' }];
        
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '{[this.encode(values.n_fileas)]}',
                ' (<b>{[this.encode(values.email, values.email_home)]}</b>)',
                /*
                '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                    '<tr>',
                        //'<td width="50%"><b>{[this.encode(values.n_fileas)]}</b></td>',
                        //'<td width="50%"><b>{[this.encode(values.email)]}</b></td>',
                        '<td width="40%"><b>{[this.encode(values.n_fileas)]}</b><br/>{[this.encode(values.org_name)]}</td>',
                        '<td width="40%">{[this.encode(values.email)]}<br/>',
                            '{[this.encode(values.email_home)]}</td>',
                        '<td width="20%">',
                            '<img width="45px" height="39px" src="{jpegphoto}" />',
                        '</td>',
                    '</tr>',
                '</table>',
                */
            '</div></tpl>',
            {
                encode: function(email, email_home) {
                    if (email) {
                        return Ext.util.Format.htmlEncode(email);
                    } else if (email_home) {
                        return Ext.util.Format.htmlEncode(email_home);
                    } else {
                        return '';
                    }
                }
            }
        );
        
        Tine.Felamimail.ContactSearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * override default onSelect
     * - set email/name as value
     * 
     * @param {} record
     * @private
     * 
     * TODO add name
     * TODO make it possible to choose between office/home email addresses
     */
    onSelect: function(record) {
        if (record.get('email') != '') {
            this.setValue(record.get('email'));
        } else {
            this.setValue(record.get('email_home'));
        } 
        this.collapse();
        this.fireEvent('blur', this);
    }    
});
Ext.reg('felamimailcontactcombo', Tine.Felamimail.ContactSearchCombo);
