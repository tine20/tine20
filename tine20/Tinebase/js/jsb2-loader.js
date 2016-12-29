module.exports = function(source) {
    this.cacheable();

    var jsb2        = JSON.parse(source),
        requires    = '';

    jsb2.pkgs.forEach(function(pkg) {
        pkg.fileIncludes.forEach(function(includeFile) {
            var file = './' + includeFile.path + '/' + includeFile.text;
            requires += 'require("' + file + '");\n';
        }, this);
    }, this);

    return requires;
};