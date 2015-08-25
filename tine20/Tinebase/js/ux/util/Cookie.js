/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux.util');

/**
 * @namespace   Ext.ux.util
 * @class       Ext.ux.util.Cookie
 */
Ext.ux.util.Cookie = function(config) {
    this.path = "/";
    this.expires = new Date(new Date().getTime()+(1000*60*60*24*7)); //7 days
    this.domain = null;
    this.secure = false;
    this.encode = false;
    Ext.apply(this, config);


};

Ext.override(Ext.ux.util.Cookie, {

    get: function(name) {
        var c = document.cookie + ";";
        var re = /\s?(.*?)=(.*?);/g;
        var matches;
        while((matches = re.exec(c)) != null){
            if (name == matches[1]) {
                return this.decodeValue(matches[2]);
            }
        }

        return null;
    },

    set: function(name, value){
        document.cookie = name + "=" + this.encodeValue(value) +
        ((this.expires == null) ? "" : ("; expires=" + this.expires.toGMTString())) +
        ((this.path == null) ? "" : ("; path=" + this.path)) +
        ((this.domain == null) ? "" : ("; domain=" + this.domain)) +
        ((this.secure == true) ? "; secure" : "");
    },

    clear: function(name){
        document.cookie = name + "=null; expires=Thu, 01-Jan-70 00:00:01 GMT" +
        ((this.path == null) ? "" : ("; path=" + this.path)) +
        ((this.domain == null) ? "" : ("; domain=" + this.domain)) +
        ((this.secure == true) ? "; secure" : "");
    },

    encodeValue: function(value) {
        return this.encode ? Ext.encode(value) : value;
    },

    decodeValue: function(raw) {
        return this.encode ? Ext.decode(raw) : raw;
    }

});