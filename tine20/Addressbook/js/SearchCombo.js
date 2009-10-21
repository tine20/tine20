/*
 * Tine 2.0
 * contacts combo box and store
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Addressbook');

/**
 * contact selection combo box
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.SearchCombo
 * @extends     Tine.Tinebase.widgets.form.SearchCombo
 * 
 * <p>Contact Search Combobox</p>
 * <p><pre></pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.SearchCombo
 */
Tine.Addressbook.SearchCombo = Ext.extend(Tine.Tinebase.widgets.form.SearchCombo, {

    /**
     * combobox cfg
     * @private
     */
    id: 'contactSearchCombo',
    
    /**
     * @cfg {Boolean} internalContactsOnly
     */
    internalContactsOnly: false,
    
    /**
     * @private
     */
    initComponent: function() {
        
        // init some vars
        this.valueField = 'n_fn';
        this.recordFields = Tine.Addressbook.Model.ContactArray;
        this.searchMethod = 'Addressbook.searchContacts';
        
        // add container filter
        this.additionalFilters = (this.additionalFilters !== null) ? this.additionalFilters : [];
        if (this.internalContactsOnly) {
            this.additionalFilters.push({field: 'container_id', operator: 'specialNode', value: 'internal' });
        } else {
            this.additionalFilters.push({field: 'container_id', operator: 'specialNode', value: 'all' });
        }
        
        Tine.Addressbook.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        // Custom rendering Template
        // TODO move style def to css ?
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td width="30%"><b>{[this.encode(values.n_fileas)]}</b><br/>{[this.encode(values.org_name)]}</td>',
                            '<td width="25%">{[this.encode(values.adr_one_street)]}<br/>',
                                '{[this.encode(values.adr_one_postalcode)]} {[this.encode(values.adr_one_locality)]}</td>',
                            '<td width="25%">{[this.encode(values.tel_work)]}<br/>{[this.encode(values.tel_cell)]}</td>',
                            '<td width="20%">',
                                '<img width="45px" height="39px" src="{jpegphoto}" />',
                            '</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(value) {
                         if (value) {
                            return Ext.util.Format.htmlEncode(value);
                        } else {
                            return '';
                        }
                    }
                }
            );
        }
    }
});
