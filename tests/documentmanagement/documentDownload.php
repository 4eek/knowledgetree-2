<?php

require_once("../../config/dmsDefaults.php");

/**
* Contains code to test the dowloading of a document from the server
* found in /lib/documentmanagement/PhysicalDocumentManager.php
*
* @author Rob Cherry, Jam Warehouse (Pty) Ltd, South Africa
* @date 17 January 2003
* @package tests.documentmanagement
*/

if (checkSession()) {
	require_once("$default->owl_fs_root/lib/documentmanagement/PhysicalDocumentManager.inc");
	require_once("$default->owl_fs_root/lib/documentmanagement/Document.inc");
	require_once("$default->owl_fs_root/lib/folderManagement/Folder.inc");
	require_once("$default->owl_fs_root/lib/folderManagement/FolderManager.inc");	
	
	echo "<html><head></head><body>\n";
	echo "<form method=\"POST\"  action=\"documentDownload2.php\">\n";
	echo "<input type=\"text\" name=\"documentID\">\n";
	echo "<input type=\"submit\" value=\"Submit\">\n";
	echo "</body></html>";

}

?>
