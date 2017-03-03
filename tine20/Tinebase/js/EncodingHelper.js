/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

/**
 * Encapsuple string escaping and subsituting methods
 *
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.EncodingHelper
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 * @return {string}
 */
Tine.Tinebase.EncodingHelper = {
    /**
     * Encode string
     *
     * @param value
     */
    encode: function(value) {
        if (!value) {
            return '';
        }

        value = Ext.util.Format.htmlEncode(value);
        return Ext.util.Format.nl2br(value);
    }
};


