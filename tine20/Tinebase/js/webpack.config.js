var fs = require('fs');
var path = require('path');
var webpack = require('webpack');
var UnminifiedWebpackPlugin = require('unminified-webpack-plugin');

var baseDir  = path.resolve(__dirname , '../../'),
    entry = {};

// find all apps to include
// https://www.reddit.com/r/reactjs/comments/50s2uu/how_to_make_webpackdevserver_work_with_webpack/
// @TODO add some sort of filter, so we can exclude apps from build
fs.readdirSync(baseDir).forEach(function(baseName) {
    var entryFile = '';

    try {
        // try npm package.json
        var pkgDef = JSON.parse(fs.readFileSync(baseDir + '/' + baseName + '/js/package.json').toString());
        entryFile = baseDir + '/' + baseName + '/js/' + (pkgDef.main ? pkgDef.main : 'index.js');

    } catch (e) {
        // fallback to legacy jsb2 file
        var jsb2File = baseDir + '/' + baseName + '/' + baseName + '.jsb2';
        if (! entryFile) {
            try {
                if (fs.statSync(jsb2File).isFile()) {
                    ;
                    entryFile = jsb2File;
                }
            } catch (e) {}
        }
    }

    if (entryFile /* && (baseName == 'Admin')*/) {
        entry[baseName + '/js/' + baseName] = entryFile;
    }

});

module.exports = {
    entry: entry,
    //devtool: 'source-map', // use -d option if you need sourcemaps
    output: {
        path: baseDir + '/',
        publicPath: '/',
        filename: '[name]-FAT.js',
        chunkFilename: "[name]-FAT.js",
        libraryTarget: "umd",
    },
    devServer: {
        //host: '0.0.0.0',
        //inline: true,
        port: 10443,
        proxy: [
            {
                context: ['/', '/index.php'],
                target: 'http://localhost/',
                secure: false
            }
        ],
    },
    plugins: [
        new UnminifiedWebpackPlugin({
            postfix : 'debug'
        })
    ],
    module: {
        preLoaders: [
            // use script loader for old library classes as some of them the need to be included in window context
            {test: /\.js$/, include: [baseDir + '/library'], loader: "script!uglify!"},
        ],
        loaders: [
            {test: /\.jsb2$/, loader: "./jsb2-loader"},
            {test: /\.css$/, loader: "style-loader!css-loader"},
            {test: /\.png/, loader: "url-loader?limit=100000&minetype=image/png"},
            {test: /\.gif/, loader: "url-loader?limit=100000&minetype=image/gif"},
        ]
    },
    resolveLoader: {fallback: __dirname + "/node_modules"}
};
