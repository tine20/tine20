/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * webpack entry
 */
require.ensure(['Tinebase.js'], function () {
    var  libs = require('Tinebase.js');

    libs.lodash.assign(window, libs);
    require('tineInit');
}, 'Tinebase/js/Tinebase');

