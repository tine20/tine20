<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Sentry
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */


/**
 * Raven Client implementation
 * allows to send more of the request data to sentry
 *
 * @package     Tinebase
 * @subpackage  Sentry
 */

class Tinebase_Sentry_Raven_Client extends Raven_Client
{
    /**
     * @param array $data
     */
    public function sanitize(&$data)
    {
        // Serializes the session items as a json string
        foreach ($data['request']['data'] as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $data['request']['data'][$k][$kk] = json_encode($vv, JSON_PRETTY_PRINT, 7);
                }
            }
        }

        // use parent sanitizer
        parent::sanitize($data);
    }

}