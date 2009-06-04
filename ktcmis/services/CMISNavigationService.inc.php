<?php
/**
 * CMIS Repository Navigation API class for KnowledgeTree.
 *
 * KnowledgeTree Community Edition
 * Document Management Made Simple
 * Copyright (C) 2008,2009 KnowledgeTree Inc.
 * Portions copyright The Jam Warehouse Software (Pty) Limited
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 3 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact KnowledgeTree Inc., PO Box 7775 #87847, San Francisco,
 * California 94120-7775, or email info@knowledgetree.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * KnowledgeTree" logo and retain the original copyright notice. If the display of the
 * logo is not reasonably feasible for technical reasons, the Appropriate Legal Notices
 * must display the words "Powered by KnowledgeTree" and retain the original
 * copyright notice.
 *
 * @copyright 2008-2009, KnowledgeTree Inc.
 * @license GNU General Public License version 3
 * @author KnowledgeTree Team
 * @package KTCMIS
 * @version Version 0.1
 */

// really wanted to keep KT code out of here but I don't see how
require_once(KT_DIR . '/ktapi/ktapi.inc.php');
require_once(CMIS_DIR . '/util/CMISUtil.inc.php');

class CMISNavigationService {

    protected $ktapi;

    function CMISNavigationService(&$ktapi)
    {
        $this->ktapi = $ktapi;
    }

    /**
     * Get descendents of the specified folder, up to the depth indicated
     *
     * @param string $repositoryId
     * @param string $folderId
     * @param bool $includeAllowableActions
     * @param bool $includeRelationships
     * @param string $typeId
     * @param int $depth
     * @param string $filter
     * @return array $descendants
     */

    // NOTE This method does NOT support paging as defined in the paging section
    // NOTE If the Repository supports the optional “VersionSpecificFiling” capability,
    //      then the repository SHALL return the document versions filed in the specified folder or its descendant folders.
    //      Otherwise, the latest version of the documents SHALL be returned.

    // TODO FilterNotValidException: The Repository SHALL throw this exception if this property filter input parameter is not valid
    function getDescendants($repositoryId, $folderId, $includeAllowableActions, $includeRelationships,
                            $depth = 1, $typeId = 'Any', $filter = '')
    {
        // TODO optional parameters
        $descendants = array();
        $repository = new CMISRepository($repositoryId);

        // if this is not a folder, cannot get descendants
        $type = CMISUtil::decodeObjectId($folderId);
        
        if ($type != 'Folder')
        {
            return $descendants;
        }

        $folder = $this->ktapi->get_folder_by_id($folderId);
        $descendants = $folder->get_listing($depth);

        // parse ktapi descendants result into a list of CMIS objects
        $descendants = CMISUtil::createChildObjectHierarchy($descendants, $repository->getRepositoryURI, $this->ktapi);

        return $descendants;
    }

    /**
     * Get direct children of the specified folder
     *
     * @param string $repositoryId
     * @param string $folderId
     * @param bool $includeAllowableActions
     * @param bool $includeRelationships
     * @param string $typeId
     * @param string $filter
     * @param int $maxItems
     * @param int $skipCount
     * @return array $descendants
     */
    // NOTE If the Repository supports the optional “VersionSpecificFiling” capability,
    //      then the repository SHALL return the document versions filed in the specified folder or its descendant folders.
    //      Otherwise, the latest version of the documents SHALL be returned.
    // TODO FilterNotValidException: The Repository SHALL throw this exception if this property filter input parameter is not valid
    function getChildren($repositoryId, $folderId, $includeAllowableActions, $includeRelationships,
                         $typeId = 'Any', $filter = '', $maxItems = 0, $skipCount = 0)
    {
        // TODO paging
        // TODO optional parameters
        $children = array();
        $repository = new CMISRepository($repositoryId);

        // if this is not a folder, cannot get children
        $type = CMISUtil::decodeObjectId($folderId);
        // NOTE this will quite possibly break the webservices
        if ($type != 'Folder')
        {
            return $children;
        }

        $folder = $this->ktapi->get_folder_by_id($folderId);
        $children = $folder->get_listing();

        $children = CMISUtil::createChildObjectHierarchy($children, $repository->getRepositoryURI, $this->ktapi);

        return $children;
    }

    /**
     * Fetches the folder parent and optional ancestors
     *
     * @param string $repositoryId
     * @param string $folderId
     * @param bool $includeAllowableActions
     * @param bool $includeRelationships
     * @param bool $returnToRoot If TRUE, then the repository SHALL return all folder objects
     *                           that are ancestors of the specified folder.
     *                           If FALSE, the repository SHALL return only the parent folder of the specified folder.
     * @param string $filter
     * @return array $ancestry
     */
    // TODO FilterNotValidException: The Repository SHALL throw this exception if this property filter input parameter is not valid
    // NOTE SHOULD always include the “ObjectId” and “ParentId” properties for all objects returned
    // NOTE If this service method is invoked on the root folder of the Repository, then the Repository shall return an empty result set.
    function getFolderParent($repositoryId, $folderId, $includeAllowableActions, $includeRelationships, $returnToRoot, $filter = '')
    {
        $ancestry = array();
        $repository = new CMISRepository($repositoryId);

        // if this is not a folder, cannot get folder parent :)
        $type = CMISUtil::decodeObjectId($folderId);
        // NOTE this will quite possibly break the webservices
        if ($type != 'Folder')
        {
            return $ancestry;
        }

        $ktapiFolder = $this->ktapi->get_folder_by_id($folderId);

        // TODO return full ancestry on $returnToRoot == true
        if ($returnToRoot)
        {
            $folder = $ktapiFolder->get_folder();
            $parents = $folder->generateFolderIDs($folderId);
            // remove the id of the requesting folder and convert to array
            $ancestry = explode(',', str_replace(','.$folderId, '', $parents));
            // reverse to get bottom up listing?  don't think so with the current implementation
            // specifying that objectTypes may have children but do not have parents listed.
//            $ancestry = array_reverse($ancestry);
        }
        else
        {
            $parent = $ktapiFolder->get_parent_folder_id();
            $ancestry[] = $parent;
        }

        // need some info about the parent(s) in order to correctly create the hierarchy
        $tmpArray = array();
        foreach ($ancestry as $key => $ancestor)
        {
            $tmpArray[$key] = $this->ktapi->get_folder_by_id($ancestor);
        }
        $ancestry = $tmpArray;
        unset($tmpArray);
        
        $ancestry = CMISUtil::createParentObjectHierarchy($ancestry, $repository->getRepositoryURI, $this->ktapi);
        
        return $ancestry;
    }

    /**
     *
     * @param string $repositoryId
     * @param string $objectId
     * @param bool $includeAllowableActions
     * @param bool $includeRelationships
     * @param string $filter
     * @return array $parents
     */
    // TODO ConstraintViolationException: The Repository SHALL throw this exception if this method is invoked
    //      on an object who Object-Type Defintion specifies that it is not fileable.
    //      FilterNotValidException: The Repository SHALL throw this exception if this property filter input parameter is not valid.
    function getObjectParents($repositoryId, $objectId, $includeAllowableActions, $includeRelationships, $filter = '')
    {
        $ancestry = array();

        $typeId = CMISUtil::decodeObjectId($objectId);

        // TODO - what about other types?  only implementing folders and documents at the moment so ignore for now
        switch($typeId)
        {
            case 'Document':
                $document = $this->ktapi->get_document_by_id($objectId);
                $parent = $document->ktapi_folder;
                $ancestry[] = $parent;
                break;
            case 'Folder':
                $folder = $this->ktapi->get_folder_by_id($objectId);
                $parent = $this->ktapi->get_folder_by_id($folder->get_parent_folder_id());
                $ancestry[] = $parent;
                break;
        }

//        echo 'PARENT<BR><pre>'.print_r($parent, true).'</pre>';
//
//echo '<pre>'.print_r($ancestry, true).'</pre>';
//        // need some info about the parent(s) in order to correctly create the hierarchy
//        $tmpArray = array();
//        foreach ($ancestry as $key => $ancestor)
//        {
//            echo '<pre>'.print_r($ancestor, true).'</pre>';
//            $tmpArray[$key] = $this->ktapi->get_folder_by_id($ancestor);
//        }
//        $ancestry = $tmpArray;
//        unset($tmpArray);

        $ancestry = CMISUtil::createParentObjectHierarchy($ancestry, $repository->getRepositoryURI, $this->ktapi);

        return $ancestry;
    }

}

?>