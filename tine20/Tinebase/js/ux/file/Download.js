/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux.file');

Ext.ux.file.Download = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Ext.ux.file.Download.superclass.constructor.call(this);
    
    this.addEvents({
        'success': true,
        'fail': true,
        'abort': true
    });
};

Ext.extend(Ext.ux.file.Download, Ext.util.Observable, {    
    url: null,
    method: 'POST',
    params: null,
    
    /**
     * @private 
     */
    form: null,
    transactionId: null,
    
    /**
     * start download
     */
    start: function() {
        this.form = Ext.getBody().createChild({
            tag:'form',
            method: this.method,
            cls:'x-hidden'
        });

        this.transactionId = Ext.Ajax.request({
            isUpload: true,
            form: this.form,
            params: this.params,
            scope: this,
            success: this.onSuccess,
            failure: this.onFailure
        });
    },
    
    /**
     * abort download
     */
    abort: function() {
        console.log('abort');
        Ext.Ajax.abort(this.transactionId);
        this.form.remove();
        this.fireEvent('abort', this);
    },
    
    /**
     * @private
     * 
     */
    onSuccess: function() {
        this.form.remove();
        this.fireEvent('success', this);
    },
    
    /**
     * @private
     * 
     */
    onFailure: function() {
        this.form.remove();
        this.fireEvent('fail', this);
    }
    
});
