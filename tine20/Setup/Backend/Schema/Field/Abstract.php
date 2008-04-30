<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Field.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


abstract class Setup_Backend_Schema_Field_Abstract
{
    /**
     * the name of the column / field
     *
     * @var string
     */
    public $name;
    
    /**
     * the data type (int/varchar/etc)
     *
     * @var string
     */    
    public $type;
    
    /**
     * mysql- feature
     *
     * @var boolean
     */
    public $autoincrement;

    /**
     * only positive values are allowed
     *
     * @var boolean
     */
    public $unsigned;

    /**
     * the data precision
     *
     * @var int
     */
    public $length;

    /**
     * if true, there have to be some values
     *
     * @var string
     */
    public $notnull;

    /**
     * value / decimal definition / enum values / default values
     *
     * @var mixed
     */
    public $value;
    /**
     * value / decimal definition / enum values / default values
     *
     * @var mixed
     */
    public $default;

    /**
     * field/ column comment
     *
     * @var string
     */
    public $comment;

    /**
     * is index (mysql specific setting)
     *
     * @var boolean
     */
    public $mul;

    /**
     * is primary key
     *
     * @var boolean
     */
    public $primary;

    /**
     * value has to be unique
     *
     * @var boolean
     */
    public $unique;
    
    
    //abstract protected function _setField($_declaration);
    
    /**
     * homogenize key definition from database and XML
     *
     * @param SimpleXMLElement $_declaration
     */
    public function fixFieldKey(array $_indices)
    {
        foreach($_indices as $index) {
            if($this->name == $index->name) {
                if ($index->primary == 'true') {
                    $this->primary = 'true';
                } elseif ($index->unique == 'true') {
                    $this->unique = 'true';
                } else {
                    $this->mul = 'true';
                }
            }
        }
    }    
    
        
    public function toArray()
    {
        return array('name'=> $this->name, 
                    'length' => (int) $this->length ,
				    //'type' => $this->type,
					
                    //'value' => $this->value,
                    'mul' => $this->mul,
                    'primary' => $this->primary,
                    );
    }
}
