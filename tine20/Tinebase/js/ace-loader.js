/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// keep ace code in builds
module.exports = function(source) {
    this.cacheable();

    source = source.replace(/file-loader/g, 'file-loader?{name: "Tinebase/js/ace-[name]-FAT.[ext]"}');

    return source;
};