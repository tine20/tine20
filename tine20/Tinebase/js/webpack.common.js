var fs = require('fs');
var _ = require('lodash');
var path = require('path');
var webpack = require('webpack');
var UglifyJSPlugin = require('uglifyjs-webpack-plugin');
var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    path: 'Tinebase/js',
    fullPath: false,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});

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

        _.each(_.get(pkgDef, 'tine20.entryPoints', []), function(entryPoint) {
            entry[baseName + '/js/' + entryPoint] = baseDir + '/' + baseName + '/js/' + entryPoint;
        });

    } catch (e) {
        // fallback to legacy jsb2 file
        var jsb2File = baseDir + '/' + baseName + '/' + baseName + '.jsb2';
        if (! entryFile) {
            try {
                if (fs.statSync(jsb2File).isFile()) {
                    entryFile = jsb2File;
                }
            } catch (e) {}
        }
    }

    if (entryFile /* && (baseName == 'Calendar') */) {
        entry[baseName + '/js/' + baseName] = entryFile;
    }
});

// additional 'real' entry points
entry['Tinebase/js/postal.xwindow.js'] = baseDir + '/Tinebase/js/postal.xwindow.js';

module.exports = {
    entry: entry,
    //devtool: 'source-map', // use -d option if you need sourcemaps
    output: {
        path: baseDir + '/',
        publicPath: '/',
        filename: '[name]-[hash]-FAT.js',
        chunkFilename: "[name]-[chunkhash]-FAT.js",
        libraryTarget: "umd"
    },
    plugins: [
        assetsPluginInstance
    ],
    module: {
        rules: [
            {
                test: /\.(es6\.js|vue)$/,
                loader: 'eslint-loader',
                enforce: "pre",
                exclude: /node_modules/,
                options: {
                    formatter: require('eslint-friendly-formatter')
                }
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.es6\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/,
                // include: /\/(src|test)\//,
                options: {
                    presets: [
                        ["@babel/env"]
                    ]
                }
            },
            {
                test: /\.js$/,
                include: [
                    require.resolve("bootstrap-vue"), // white-list bootstrap-vue
                ],
                loader: "babel-loader"
            },

            // use script loader for old library classes as some of them the need to be included in window context
            {test: /\.js$/, include: [baseDir + '/library'], enforce: "pre", use: [{loader: "script-loader"}]},
            {test: /\.jsb2$/, use: [{loader: "./jsb2-loader"}]},
            {test: /\.css$/, use: [{loader: "style-loader"}, {loader: "css-loader"}]},
            {test: /\.png/, use: [{loader: "url-loader", options: {limit: 100000, minetype:"image/png"}}]},
            {test: /\.gif/, use: [{loader: "url-loader", options: {limit: 100000, minetype:"image/gif"}}]},
            {test: /\.svg/, use: [{loader: "url-loader", options: {limit: 100000, minetype:"image/svg"}}]},
            {
                test: /\.(woff2?|eot|ttf|otf)(\?.*)?$/,
                use: [{loader: "url-loader", options: {limit: 100000}}]},
        ]
    },
    resolveLoader: {
        modules: [path.resolve(__dirname, "node_modules")]
    },
    resolve: {
        modules: [path.resolve(__dirname , 'node_modules')],

        // add browserify which is used by some libs (e.g. director)
        mainFields: ["browser", "browserify", "module", "main"],
        // we need an absolut path here so that apps can resolve modules too
        modules: [path.resolve(__dirname, "node_modules")],
    }
};
