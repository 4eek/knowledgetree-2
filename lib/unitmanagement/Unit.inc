<?php
/**
 * $Id$
 *
 * KnowledgeTree Community Edition
 * Document Management Made Simple
 * Copyright (C) 2008, 2009, 2010 KnowledgeTree Inc.
 * 
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
 * Contributor( s): ______________________________________
 */

require_once(KT_LIB_DIR . "/util/sanitize.inc");

class Unit extends KTEntity {
    var $_bUsePearError = true;

    var $iId;
    var $sName;
	var $iFolderID;

    var $_aFieldToSelect = array(
        'iId' => 'id',
        'sName' => 'name',
        'iFolderId' => 'folder_id',
    );

    function _table () {
        return KTUtil::getTableName('units');
    }

    function getName() { return sanitizeForSQLtoHTML($this->sName); }
    function setName($sNewValue) { $this->sName = sanitizeForSQL($sNewValue); }
    function getFolderId() { return $this->iFolderId; }
    function setFolderId($iNewValue) { $this->iFolderId = $iNewValue; }

    function &get($iId) {
        return KTEntityUtil::get('Unit', $iId);
    }

    function getList($sWhereClause = null) {
        return KTEntityUtil::getList2('Unit', $sWhereClause);
    }

    function &createFromArray($aArray) {
        return KTEntityUtil::createFromArray('Unit', $aArray);
    }

    function &getByFolder($oFolder) {
        $iFolderId = KTUtil::getId($oFolder);
        return KTEntityUtil::getByDict('Unit', array(
            'folder_id' => $iFolderId,
        ), array(
            'noneok' => true,
        ));
    }

    function getFolder() {
        require_once(KT_LIB_DIR . '/foldermanagement/Folder.inc');
        return Folder::get($this->iFolderId);
    }

    function getUnitsForFolder($oFolder) {
        $oFolder =& KTUtil::getObject('Folder', $oFolder);
        $sUnitTable = KTUtil::getTableName('units');
        if (PEAR::isError($oFolder)) {  // can't be admin for a non-existant folder.
            return false;
        }
        $sFolderIds = $oFolder->getParentFolderIds();
        $aFolderIds = split(",", $sFolderIds);
        $aFolderIds[] = $oFolder->getId();

        $sParams = DBUtil::paramArray($aFolderIds);
        $aParams = $aFolderIds;
        $sQuery = "SELECT id FROM $sUnitTable WHERE folder_id IN ($sParams)";
        $aUnitIds = DBUtil::getResultArrayKey(array($sQuery, $aParams), 'id');

        $aRet = array();
        foreach ($aUnitIds as $iId) {
            $aRet[] = Unit::get($iId);
        }
        return $aRet;
    }
}

?>
