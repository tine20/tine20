<?php
/**
 * DownloadLink controller for MailFiler application
 *
 * @package     MailFiler
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DownloadLink controller class for MailFiler application
 *
 * @package     MailFiler
 * @subpackage  Controller
 */
class MailFiler_Controller_DownloadLink extends Filemanager_Controller_DownloadLink
{
    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'MailFiler';
        $this->_modelName = 'MailFiler_Model_DownloadLink';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'filemanager_downloadlink',
        ));
    }
    
    /**
     * holds the instance of the singleton
     * @var MailFiler_Controller_DownloadLink
     */
    private static $_instance = NULL;
}
