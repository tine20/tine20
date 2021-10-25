/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './AdminPanel'

// sso app entry point for tine20 fat-client
Ext.ns('Tine.SSO');

Tine.SSO.Application = Ext.extend(Tine.Tinebase.Application, {
    hasMainScreen: false
});
