/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

module.exports = function(source) {
    this.cacheable();

    var jsb2        = JSON.parse(source),
        requires    = '';

    jsb2.pkgs.forEach(function(pkg) {
        requires += '/* pkg: ' + pkg.name + ' (' + pkg.file + ')*/\n';
        pkg.fileIncludes.forEach(function(includeFile) {
            var file = './' + includeFile.path + '/' + includeFile.text;
            requires += 'require("' + file + '");\n';
        }, this);
    }, this);

    return requires;
};