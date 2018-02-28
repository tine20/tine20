/**
 * entry file for index.php?method=Tinebase.getPostalXWindow
 */
var lodash = require('lodash');
var jquery = require('jquery');
var postal = require('postal');
require('postal.federation');
require('postal.request-response');
require('postal.xwindow');

module.exports = {
    _: lodash,
    $: jquery,
    postal: postal
};