<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * AreaLocked exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_AreaLocked extends Tinebase_Exception_SystemGeneric
{
    /**
     * @var string _('Area is locked')
     */
    protected $_title = 'Area is locked';

    /**
     * the locked area
     *
     * @var string
     */
    protected $_area = null;

    protected $_mfaUserConfigs = null;

    /**
     * Tinebase_Exception_AreaLocked constructor.
     * @param null $_message
     * @param int $_code
     */
    public function __construct($_message, $_code = 630)
    {
        parent::__construct($_message, $_code);
    }

    /**
     * @param $area
     */
    public function setArea($area)
    {
        $this->_area = $area;
    }

    public function setMFAUserConfigs(Tinebase_Record_RecordSet $config): void
    {
        $this->_mfaUserConfigs = $config;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->_area;
    }

    /**
     * returns existing nodes info as array
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['area'] = $this->getArea();
        if (!$this->_mfaUserConfigs && ($user = Tinebase_Core::getUser()) && $user->mfa_configs) {
            $this->_mfaUserConfigs = $user->mfa_configs;
        }
        if ($this->_mfaUserConfigs) {
            $result['mfaUserConfigs'] = $this->_mfaUserConfigs->toFEArray();
        }
        return $result;
    }
}
