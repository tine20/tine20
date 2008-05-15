<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_Image
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Image extends Tinebase_Record_Abstract 
{

    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        // image identifiers
        'id'          => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'application' => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'location'    => array('default' => '', 'allowEmpty' => true, 'Alnum', ),
        
        // image properties
        'width'       => array('allowEmpty' => true, 'Int' ),
        'height'      => array('allowEmpty' => true, 'Int' ),
        'bits'        => array('allowEmpty' => true, 'Int' ),
        'channels'    => array('allowEmpty' => true, 'Int' ),
        'mime'        => array('allowEmpty' => true, 'InArray' => array('image/png', 'image/jpeg', 'image/gif')),
    
        // binary data
        'blob'        => array('allowEmpty' => true)
    );
    
    /**
     * returns image from given path
     * 
     * @param  string $_path
     * @return Tinebase_Model_Image
     */
    public static function getImageFromPath($_path)
    {
        if (!file_exists($_path)) {
            throw new Exception('image file not found');
        }
        $imgBlob = file_get_contents($_path);
        return new Tinebase_Model_Image(Tinebase_ImageHelper::getImageInfoFromBlob($imgBlob) + array(
            'blob' => $imgBlob
        ), true);
    }
    
    /**
     * returns image extension from mime type
     * 
     * @return string extension
     */
    public function getImageExtension()    
    {
        $extension = '';
        
        switch ( $this->mime ) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
                $extension = '.jpg';
                break;
            case 'image/gif':
                $extension = '.gif';
                break;
        }
        
        return $extension;
    }
} // end of Tinebase_Model_Image
