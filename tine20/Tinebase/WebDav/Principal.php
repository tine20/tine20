<?php
/**
 * Principals Collection
 *
 * This collection represents a list of users.
 * The users are instances of Sabre\DAVACL\Principal
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Tinebase_WebDav_Principal extends \Sabre\DAVACL\Principal implements \Sabre\DAV\ICollection
{
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ICollection::createFile()
     */
    public function createFile($name, $data = null) 
    {
        throw new \Sabre\DAV\Exception\Forbidden('Permission denied to create file (filename ' . $name . ')');
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ICollection::createDirectory()
     */
    public function createDirectory($name) 
    {
        throw new \Sabre\DAV\Exception\Forbidden('Permission denied to create directory');
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ICollection::getChild()
     */
    public function getChild($name) 
    {
        switch ($name) {
            case 'calendar-proxy-read':
                return new \Sabre\CalDAV\Principal\ProxyRead($this->principalBackend, $this->principalProperties);
                
                break;
                
            case 'calendar-proxy-write':
                return new \Sabre\CalDAV\Principal\ProxyWrite($this->principalBackend, $this->principalProperties);
                
                break;
        }

        throw new \Sabre\DAV\Exception\NotFound('Node with name ' . $name . ' was not found');
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ICollection::getChildren()
     */
    public function getChildren() 
    {
        $children = array(
            new \Sabre\CalDAV\Principal\ProxyRead ($this->principalBackend, $this->principalProperties),
            new \Sabre\CalDAV\Principal\ProxyWrite($this->principalBackend, $this->principalProperties)
        );
        
        return $children;
    }

    /**
     * Returns whether or not the child node exists
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) 
    {
        try {
            $this->getChild($name);
            
            return true;
            
        } catch (\Sabre\DAV\Exception\NotFound $e) {
            return false;
        }
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL() 
    {
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->principalProperties['uri'] . '/calendar-proxy-read',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->principalProperties['uri'] . '/calendar-proxy-write',
                'protected' => true,
            )
        );
    }
}
