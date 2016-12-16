module.exports = function(source) {
    this.cacheable();

    var jsb2        = JSON.parse(source),
        requires    = '';

    jsb2.pkgs.forEach(function(pkg) {
        //if (! pkg.file.match(/\.js$/)) return;
        //var loaders = pkg.file.match(/\.js$/) ? 'script!' : '';

        pkg.fileIncludes.forEach(function(includeFile) {
            // use script loader for old library classes as some of them the need to be included in window context
            var loaders = includeFile.text.match(/\.js$/) && includeFile.path.match(/library/) ? 'script!uglify!' : '';
            var file = './' + includeFile.path + '/' + includeFile.text;
            requires += 'require("' + loaders + file + '");\n';
        }, this);
    }, this);

    return requires;
};