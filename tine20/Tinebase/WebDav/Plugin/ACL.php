<?php

class Tinebase_WebDav_Plugin_ACL extends \Sabre\DAVACL\Plugin
{
    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * Either a uri or a \Sabre\DAV\INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|\Sabre\DAV\INode $node
     * @return array
     */
    public function getCurrentUserPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = $this->getACL($node);

        if (is_null($acl)) return null;

        try {
            $oldValue = Tinebase_WebDav_PrincipalBackend::showHiddenGroups(true);
            $principals = $this->getCurrentUserPrincipals();
        } finally {
            Tinebase_WebDav_PrincipalBackend::showHiddenGroups($oldValue);
        }

        $collected = array();

        foreach($acl as $ace) {

            $principal = $ace['principal'];

            switch($principal) {

                case '{DAV:}owner' :
                    $owner = $node->getOwner();
                    if ($owner && in_array($owner, $principals)) {
                        $collected[] = $ace;
                    }
                    break;


                // 'all' matches for every user
                case '{DAV:}all' :

                    // 'authenticated' matched for every user that's logged in.
                    // Since it's not possible to use ACL while not being logged
                    // in, this is also always true.
                case '{DAV:}authenticated' :
                    $collected[] = $ace;
                    break;

                // 'unauthenticated' can never occur either, so we simply
                // ignore these.
                case '{DAV:}unauthenticated' :
                    break;

                default :
                    if (in_array($ace['principal'], $principals)) {
                        $collected[] = $ace;
                    }
                    break;

            }


        }

        // Now we deduct all aggregated privileges.
        $flat = $this->getFlatPrivilegeSet($node);

        $collected2 = array();
        while(count($collected)) {

            $current = array_pop($collected);
            $collected2[] = $current['privilege'];

            foreach($flat[$current['privilege']]['aggregates'] as $subPriv) {
                $collected2[] = $subPriv;
                $collected[] = $flat[$subPriv];
            }

        }

        return array_values(array_unique($collected2));
    }


    /**
     * Returns all the principal groups the specified principal is a member of.
     *
     * @param string $principal
     * @return array
     */
    public function getPrincipalMembership($mainPrincipal)
    {
        $cacheId = $mainPrincipal .'::' . Tinebase_WebDav_PrincipalBackend::showHiddenGroups();
        // First check our cache
        if (isset($this->principalMembershipCache[$cacheId])) {
            return $this->principalMembershipCache[$cacheId];
        }

        $check = array($mainPrincipal);
        $principals = array();

        while(count($check)) {

            $principal = array_shift($check);

            try {
                $node = $this->server->tree->getNodeForPath($principal);
            } catch (\Sabre\DAV\Exception\NotFound $e) {
                continue;
            }
            if ($node instanceof \Sabre\DAVACL\IPrincipal) {
                foreach($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

        // Store the result in the cache
        $this->principalMembershipCache[$cacheId] = $principals;

        return $principals;

    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * @param string $uri
     * @return array
     */
    public function getHTTPMethods($uri) {

        return ['ACL'];
    }
}