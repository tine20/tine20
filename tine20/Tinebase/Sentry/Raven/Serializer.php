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
 * Raven Serializer implementation
 * allows to send more of the data to sentry, does not clip strings after 1024 bytes, but 10240
 *
 * @package     Tinebase
 * @subpackage  Sentry
 */

class Tinebase_Sentry_Raven_Serializer extends Raven_Serializer
{

    protected function serializeString($value)
    {
        $value = (string) $value;
        if (function_exists('mb_detect_encoding')
            && function_exists('mb_convert_encoding')
        ) {
            // we always guarantee this is coerced, even if we can't detect encoding
            if ($currentEncoding = mb_detect_encoding($value, $this->mb_detect_order)) {
                $value = mb_convert_encoding($value, 'UTF-8', $currentEncoding);
            } else {
                $value = mb_convert_encoding($value, 'UTF-8');
            }
        }

        if (strlen($value) > 10240) {
            $value = substr($value, 0, 10230) . ' {clipped}';
        }

        return $value;
    }
}