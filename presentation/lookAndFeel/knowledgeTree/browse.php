<?php

/**
 * $Id$
 *
 * Main dashboard page -- This page is presented to the user after login.
 * It contains a high level overview of the users subscriptions, checked out 
 * document, pending approval routing documents, etc. 
 *
 * Copyright (c) 2003 Jam Warehouse http://www.jamwarehouse.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Revision$
 * @author Michael Joseph <michael@jamwarehouse.com>, Jam Warehouse (Pty) Ltd, South Africa
 */

// main library routines and defaults
require_once("../../../config/dmsDefaults.php");
require_once(KT_LIB_DIR . "/templating/templating.inc.php");
require_once(KT_LIB_DIR . "/templating/kt3template.inc.php");
require_once(KT_LIB_DIR . "/dispatcher.inc.php");
require_once(KT_LIB_DIR . "/util/ktutil.inc");
require_once(KT_LIB_DIR . "/browse/DocumentCollection.inc.php");
require_once(KT_LIB_DIR . "/browse/BrowseColumns.inc.php");
require_once(KT_LIB_DIR . "/browse/PartialQuery.inc.php");

require_once(KT_LIB_DIR . "/foldermanagement/Folder.inc");

require_once(KT_LIB_DIR . "/widgets/portlet.inc.php");
require_once(KT_LIB_DIR . '/actions/folderaction.inc.php');
require_once(KT_DIR . '/plugins/ktcore/KTFolderActions.php');


$sectionName = "browse";

class BrowseDispatcher extends KTStandardDispatcher {

    
    // Breadcrumbs base - added to in methods
    var $aBreadcrumbs = array(
        array('action' => 'browse', 'name' => 'Browse'),
    );
	var $sSection = "browse";
	var $browseType;
	
	

    function do_main() {
		$collection = new DocumentCollection;
		$this->browseType = "Folder"; 
		
		$collection->addColumn(new SelectionColumn("Browse Selection","selection"));
		$collection->addColumn(new TitleColumn("Test 1 (title)","title"));
		$collection->addColumn(new DateColumn("Created","created", "getCreatedDateTime"));
		$collection->addColumn(new DateColumn("Last Modified","modified", "getLastModifiedDate"));
		$collection->addColumn(new BrowseColumn("Test 3","test3"));
		$collection->addColumn(new BrowseColumn("Test 4","test4"));
		
		// which folder.
		$in_folder_id = KTUtil::arrayGet($_REQUEST, "fFolderId", 1);
        $folder_id = (int) $in_folder_id; // conveniently, will be 0 if not possible.
		if ($folder_id == 0) { $folder_id = 1; }
		
		// here we need the folder object to do the breadcrumbs.
		$oFolder =& Folder::get($folder_id);
		if (PEAR::isError($oFolder)) {
		   $this->oPage->addError("invalid folder");
		   $folder_id = 1;
		   $oFolder =& Folder::get($folder_id);
		}
		
		// do the breadcrumbs.

		// skip root.
		$folder_path_names = array_slice($oFolder->getPathArray(), 1);
		$folder_path_ids = array_slice(explode(',', $oFolder->getParentFolderIds()), 1);
		
		$parents = count($folder_path_ids);
		
		if ($parents != 0) {
		    foreach (range(0,$parents) as $index) {
		        $this->aBreadcrumbs[] = array("url" => "?fFolderId=" . $folder_path_ids[$index], "name" => $folder_path_names[$index]);
            }
        }
		
		// now add this folder, _if we aren't in 1_.
		if ($folder_id != 1) {
		    $this->aBreadcrumbs[] = array("name" => $oFolder->getName());
		}
		
		// setup the folderside add actions
		// FIXME do we want to use folder actions?
		$portlet = new KTActionPortlet("Folder Actions");
		
		
		// FIXME make a FolderActionUtil ... is it necessary?
		
		$aActions = KTFolderActionUtil::getFolderActionsForFolder($oFolder, $$this->oUser);
		
		$portlet->setActions($aActions,null);
		
		$this->oPage->addPortlet($portlet);
		
		$batchPage = (int) KTUtil::arrayGet($_REQUEST, "page", 0);
		$batchSize = 20;
		
		$resultURL = "?fFolderId=" . $folder_id;
		$collection->setBatching($resultURL, $batchPage, $batchSize); 
		
		
		// ordering. (direction and column)
		$displayOrder = KTUtil::arrayGet($_REQUEST, 'sort_order', "asc");		
		if ($displayOrder !== "asc") { $displayOrder = "desc"; }
		$displayControl = KTUtil::arrayGet($_REQUEST, 'sort_on', "title");		
		
		
		$collection->setSorting($displayControl, $displayOrder);
		
		// add in the query object.
		$qObj = new BrowseQuery($folder_id);
		$collection->setQueryObject($qObj);
		
		// breadcrumbs
		// FIXME handle breadcrumbs
		$collection->getResults();
		
		$oTemplating = new KTTemplating;
		$oTemplate = $oTemplating->loadTemplate("kt3/browse");
		$aTemplateData = array(
              "context" => $this,
			  "collection" => $collection,
		);
		return $oTemplate->render($aTemplateData);
	}   
}

$oDispatcher = new BrowseDispatcher();
$oDispatcher->dispatch();

?>

