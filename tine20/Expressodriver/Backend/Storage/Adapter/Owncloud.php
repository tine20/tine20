<?php

/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 *
 */

/**
 * Owncloud adapter class
 */
class Expressodriver_Backend_Storage_Adapter_Owncloud extends Expressodriver_Backend_Storage_Adapter_Webdav
{

    /**
     * @var string url suffix
     */
    const URL_SUFFIX = 'remote.php/webdav';

    /**
     * the constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['host']) && isset($options['user']) && isset($options['password'])) {
            $secure = false;
            $host = $options['host'];
            if (substr($host, 0, 8) == "https://") {
                $host = substr($host, 8);
                $secure = true;
            } else if (substr($host, 0, 7) == "http://") {
                $host = substr($host, 7);
            }
            $contextPath = '';
            $hostSlashPos = strpos($host, '/');
            if ($hostSlashPos !== false) {
                $contextPath = substr($host, $hostSlashPos);
                $host = substr($host, 0, $hostSlashPos);
            }

            if (substr($contextPath, 1) !== '/') {
                $contextPath .= '/';
            }

            if (isset($options['root'])) {
                $root = $options['root'];
                if (substr($root, 1) !== '/') {
                    $root = '/' . $root;
                }
            } else {
                $root = '/';
            }

            $options['host'] = $host;
            $options['root'] = $contextPath . self::URL_SUFFIX . $root;
            $options['secure'] = $secure;
            parent::__construct($options);
        } else {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . 'Owncloud config error. Please check your Expressodriver settings');
            throw new Exception('Owncloud config error. Please check your Expressodriver settings');
        }
    }

}
