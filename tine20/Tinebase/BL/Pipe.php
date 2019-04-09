<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase BusinessLogic Pipe:
 *
 * * this class gets constructed with a configuration of the pipe and its elements
 * * on each execution the pipes initializes/resets itself (BL Element objects do NOT get recycled, they get recreated)
 * * the input / output data is held in a Tinebase_BL_DataInterface class
 * * the pipe provides the context for each of its logical elements of type Tinebase_BL_ElementInterface
 *   it plays the roll of a container service and event broker (to be implemented)
 *
 * @package     Tinebase
 * @subpackage  BL
 */
class Tinebase_BL_Pipe implements Tinebase_BL_PipeContext
{
    const CONF_CLASSES = 'confClasses';
    const CONF_CLASS = 'class';
    const CONF_CLASS_OPTIONS = 'classOptions';

    /**
     * @var Tinebase_Record_RecordSet $_config of type Tinebase_Model_BLConfig
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_pipe = null;

    /**
     * @var int
     */
    protected $_currentExecutionOffset = null;

    /**
     * Tinebase_BL_Pipe constructor.
     * @param Tinebase_Record_RecordSet $_config of type Tinebase_Model_BLConfig
     */
    public function __construct(Tinebase_Record_RecordSet $_config)
    {
        $this->_config = $_config->getClone()->sort(
            function(Tinebase_Model_BLConfig $val1, Tinebase_Model_BLConfig $val2) {
                return $val1->{Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD}
                    ->cmp($val2->{Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD});
            });
    }

    public function execute(Tinebase_BL_DataInterface $_data)
    {
        $this->_init();

        /** @var Tinebase_BL_ElementInterface $element */
        foreach ($this->_pipe as $key => $element) {
            $this->_currentExecutionOffset = $key;
            $element->execute($this, $_data);
        }
    }

    protected function _init()
    {
        $this->_pipe = [];

        /** @var Tinebase_Model_BLConfig $config */
        foreach ($this->_config as $config) {
            if (! is_subclass_of($config->configRecord, Tinebase_BL_ElementConfigInterface::class)) {
                throw new Tinebase_Exception_NotImplemented(get_class($config->configRecord) . ' does not implement ' .
                    Tinebase_BL_ElementConfigInterface::class);
            }
            // it is mandatory to recreate the elements here as they are supposed to support state
            // maybe we could introduce stateful / stateless interfaces and distinguish by interface whether to recycle
            // or recreate the elements ... probably not really worth the effort, lets just recreate and be done
            $this->_pipe[] = $config->configRecord->getNewBLElement();
        }
    }

    /**
     * @return int
     */
    public function getCurrentExecutionOffset()
    {
        return $this->_currentExecutionOffset;
    }

    /**
     * @param string $_class
     * @param int $_before
     * @return null|Tinebase_BL_ElementInterface
     */
    public function getLastElementOfClassBefore($_class, $_before)
    {
        while (--$_before >= 0) {
            if ($this->_pipe[$_before] instanceof $_class) {
                return $this->_pipe[$_before];
            }
        }
        return null;
    }

    public function hasInstanceOf($_class)
    {
        if (empty($this->_pipe)) $this->_init();

        foreach($this->_pipe as $pipeElement) {
            if ($pipeElement instanceof $_class) return true;
        }

        return false;
    }
}