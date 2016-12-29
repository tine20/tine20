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

    // try jsb2 file
    try {
        var jsb2File = baseName + '/' + baseName + '.jsb2';
        if (fs.statSync(baseDir + '/' + jsb2File).isFile()) {
            entryFile = baseDir + '/' + jsb2File;
        }

        if (baseName == 'Tinebase') {
            entryFile = __dirname + '/Tinebase.js';
        }
    } catch (e) {}


    //// try generic app js instead
    //try {
    //    var entry = baseName + '/js/' + baseName + '.js';
    //    if (fs.statSync(baseDir + jsb2File).isFile()) {
    //        console.log(entry);
    //    }
    //} catch (e) {}
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
        watchOptions: {
            aggregateTimeout: 300,
            poll: 1000
        },
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
