<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

/**
 * this class handles the rights for the Expressodriver application
 *
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 *
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 *
 * @package     Expressodriver
 * @subpackage  Acl
 */
class Expressodriver_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Expressodriver_Acl_Rights
     */
    private static $_instance = NULL;

    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Expressodriver_Acl_Rights
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressodriver_Acl_Rights;
        }

        return self::$_instance;
    }

    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {

        $allRights = parent::getAllApplicationRights();

        $addRights = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS
        );
        $allRights = array_merge($allRights, $addRights);

        return $allRights;
    }

    /**
     * get translated right descriptions
     *
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Expressodriver');

        $rightDescriptions = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS => array(
                'text'          => $translate->_('manage shared folders'),
                'description'   => $translate->_('Create new shared folders'),
            ),
        );

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

}
