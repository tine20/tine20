<?php
class Tinebase_Import_CalDav_GroupMemberSet
{
    protected $principals = array();
    
    public function __construct(array $principals)
    {
        $this->principals = $principals;
    }
    
    public function getPrincipals()
    {
        return $this->principals;
    }
    
    public static function unserialize(\DOMElement $dom)
    {
        $principals = array();
        $xhrefs = $dom->getElementsByTagNameNS('urn:DAV','href');
        for($ii=0; $ii < $xhrefs->length; $ii++) {
            $principals[] = $xhrefs->item($ii)->textContent;
        }
        return new self($principals);
    }
}