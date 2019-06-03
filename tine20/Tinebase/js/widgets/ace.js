/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * ace code editor entry point for dynamic imports.
 *
 * Usage:
 */
// import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace').then(function() {
//      me.ed = ace.edit(me.el.id, {
//          mode: 'ace/mode/json',
//          fontFamily: 'monospace',
//          fontSize: 12
//      });
//  }

import 'ace-builds'
import '../ace-loader!ace-builds/webpack-resolver'
