<?php
/**
 * Tinebase Helper class for Zend_Config
 *
 * @package     Tinebase
 * @subpackage  Helper
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Helper class for Zend_Config
 *
 * @package     Tinebase
 * @subpackage    Helper
 *
 */
class Tinebase_Helper_ZendConfig
{
    /**
     * @param Zend_Config|string|null $_config
     * @param string $_name
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getChildrenStrings($_config, $_name)
    {
        if (!$_config instanceof Zend_Config) {
            return [];
        }
        $data = $_config->{$_name};
        if (null === $data) {
            return [];
        } elseif (is_string($data)) {
            return [$data];
        } else {
            $result = $data->toArray();
            array_walk($result, function($val, $key) {
                if (!is_string($val) || !is_int($key)) {
                    throw new Tinebase_Exception_InvalidArgument('bad configuration, expected string value');
                }
            });
            return $result;
        }
    }

    /**
     * @param Zend_Config|string|null $_config
     * @param string $_name
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getChildrenConfigs($_config, $_name)
    {
        if (!$_config instanceof Zend_Config) {
            return [];
        }
        $data = $_config->{$_name};
        if (null === $data) {
            return [];
        } elseif (!$data instanceof Zend_Config) {
            throw new Tinebase_Exception_InvalidArgument('bad configuration, expected Zend_Config value');
        } elseif (is_int($data->key())) {
            $result = [];
            do {
                $result[] = $child = $data->current();
                if (!$child instanceof Zend_Config) {
                    throw new Tinebase_Exception_InvalidArgument('bad configuration, expected Zend_Config value');
                }
            } while ($data->next() || $data->valid());
            return $result;
        } else {
            return [$data];
        }
    }
}