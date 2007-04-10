<?

/**
 *
 * This is the object model for the KnowledgeTree WebService.
 *
 * The contents of this file are subject to the KnowledgeTree Public
 * License Version 1.1 ("License"); You may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.knowledgetree.com/KPL
 * 
 * Software distributed under the License is distributed on an "AS IS"
 * basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 * 
 * The Original Code is: KnowledgeTree Open Source
 * 
 * The Initial Developer of the Original Code is The Jam Warehouse Software
 * (Pty) Ltd, trading as KnowledgeTree.
 * Portions created by The Jam Warehouse Software (Pty) Ltd are Copyright
 * (C) 2007 The Jam Warehouse Software (Pty) Ltd;
 * All Rights Reserved.
 *
 */

// TODO: comment
// TODO: php4 compatability

require_once('ktwsapi_cfg.inc.php');

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KT_PEAR_DIR);

require_once('SOAP/Client.php');

define('KTWSAPI_ERR_SESSION_IN_USE','There is a session already active.');
define('KTWSAPI_ERR_SESSION_NOT_STARTED','An active session has not been started.');

class KTWSAPI_FolderItem
{
	var $ktapi;
	
	function _upload_file($filename, $action, $document_id=null)
	{
		if (!extension_loaded('curl'))
		{
			return new PEAR_Error('CURL library not included.');
		}
		
		if (!is_file($filename) || !is_readable($filename))
		{
			return new PEAR_Error('Could not access file to upload.');			
		}
		
		if (is_null($document_id))
		{
			$uploadname="upload_document";
		}
		else 
		{
			$uploadname="upload_$document_id";
			
		}
		
		$session_id = $this->ktapi->session;
		
		$ch = curl_init();
		$fp = fopen ($filename, 'r');		 
		curl_setopt($ch, CURLOPT_URL, KTUploadURL);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$post_fields = array(
			'session_id'=>$session_id,
			'action'=>$action,
			'document_id'=>$document_id,
			$uploadname=>'@' . $filename
		
		);
		
		$str = serialize($post_fields);
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);		
		$response = curl_exec ($ch);
		curl_close ($ch);
		
		if ($response == '')
		{
			return new PEAR_Error('No response from server.');
		}
		
		$fields=explode('&',$response);
		$status_code=-1;
		$msg='*not set*';
		$upload_status='';
		

		foreach($fields as $field)
		{
			list($fieldname, $value) = explode('=', $field);
			
			$$fieldname = $value;
		}
		
		if ($status_code == 0)
		{
			$upload_status= unserialize(urldecode($upload_status));
			
			if ($upload_status[$uploadname]['size'] == filesize($filename))
			{
				return $upload_status[$uploadname]['tmp_name'];
			}
		}
		return new PEAR_Error('Could not upload file.');
		
	}

	function _download_file($url, $localpath, $filename)
	{
		$localfilename = $localpath . '/' . $filename;
		
		$fp = fopen($localfilename,'wb');
		if ($fp == null)
		{
			return new PEAR_Error('Could not create local file');
		}
		
	    $ch = curl_init();
           
    	curl_setopt($ch, CURLOPT_FILE, $fp);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_URL, $url); 
		
    	$response = curl_exec($ch);
    	curl_close($ch);
    	
    	fclose($fp);
    	
    	return $response;		
	}
	
}

class KTWSAPI_Folder extends KTWSAPI_FolderItem
{
	var $folder_name;
	var $parent_id;
	var $full_path;
	var $folderid;
	
	function KTWSAPI_Folder($ktapi, $kt_folder_detail)
	{
		$this->ktapi = $ktapi;
		$this->folderid = $kt_folder_detail->id+0;
		$this->folder_name = $kt_folder_detail->folder_name;
		$this->parent_id = $kt_folder_detail->parent_id+0;
		$this->full_path = $kt_folder_detail->full_path;		
	}
	
	function get($ktapi, $folderid)
	{
		assert(!is_null($ktapi));
		assert(is_a($ktapi, 'KTWSAPI'));
		assert(is_numeric($folderid));
		
		$folderid += 0;
		
		$kt_folder_detail = $ktapi->soapclient->get_folder_detail($ktapi->session, $folderid);
		if (SOAP_Client::isError($kt_folder_detail))
		{
			return $kt_folder_detail;
		}
		
		if ($kt_folder_detail->status_code != 0)
		{
			return new PEAR_Error($kt_folder_detail->message);
		}
		
		return new KTWSAPI_Folder($ktapi, $kt_folder_detail);		
	}

	function get_parent_folder_id()
	{
		return $this->parent_id;
	}
	
	function get_folder_name()
	{
		return $this->folder_name;
	}	
	
	function get_folderid()
	{
		return $this->folderid;
	}	

	/**
	 * 
	 *
	 * @param string $foldername
	 * @return KTWSAPI_Folder
	 */
	function get_folder_by_name($foldername)
	{
		$path = $this->full_path . '/' . $foldername;
		if (substr($path,0,13) == '/Root Folder/')
		{
			$path = substr($path,13);
		}
		if (substr($path,0,12) == 'Root Folder/')
		{
			$path = substr($path,12);
		}
		
		$kt_folder_detail = $this->ktapi->soapclient->get_folder_detail_by_name($this->ktapi->session, $path);
		
		if (SOAP_Client::isError($kt_folder_detail))
		{
			return $kt_folder_detail;
		}
		
		if ($kt_folder_detail->status_code != 0)
		{
			return new PEAR_Error($kt_folder_detail->message);
		}
		
		return new KTWSAPI_Folder($this->ktapi, $kt_folder_detail);			
	}
		
	function get_full_path()
	{
		return $this->full_path;
	}	
	
	function get_listing($depth=1, $what='DF')
	{
		$kt_folder_contents = $this->ktapi->soapclient->get_folder_contents($this->ktapi->session, $this->folderid, $depth+0, $what);
		if (SOAP_Client::isError($kt_folder_contents))
		{
			return $kt_folder_contents;
		}
		
		if ($kt_folder_contents->status_code != 0)
		{
			return new PEAR_Error($kt_folder_contents->message);
		}
		
		return $kt_folder_contents->items;
		
	}
	
	function get_document_by_name($title)
	{
		$path = $this->full_path . '/' . $title;		
		if (substr($path,0,13) == '/Root Folder/')
		{
			$path = substr($path,13);
		}
		if (substr($path,0,12) == 'Root Folder/')
		{
			$path = substr($path,12);
		}
				
		$kt_document_detail = $this->ktapi->soapclient->get_document_detail_by_name($this->ktapi->session, $path, 'T');
		
		if (SOAP_Client::isError($kt_document_detail))
		{
			return $kt_document_detail;
		}
		
		if ($kt_document_detail->status_code != 0)
		{
			return new PEAR_Error($kt_document_detail->message);
		}
		
		return new KTWSAPI_Document($this->ktapi, $kt_document_detail);
	}

	function get_document_by_filename($filename)
	{
		$path = $this->full_path . '/' . $filename;		
		if (substr($path,0,13) == '/Root Folder/')
		{
			$path = substr($path,13);
		}
		if (substr($path,0,12) == 'Root Folder/')
		{
			$path = substr($path,12);
		}
				
		$kt_document_detail = $this->ktapi->soapclient->get_document_detail_by_name($this->ktapi->session, $path, 'F');
		
		if (SOAP_Client::isError($kt_document_detail))
		{
			return $kt_document_detail;
		}
		
		if ($kt_document_detail->status_code != 0)
		{
			return new PEAR_Error($kt_document_detail->message);
		}
		
		return new KTWSAPI_Document($this->ktapi, $kt_document_detail);
	}
	
	
	/**
	 * 
	 *
	 * @param unknown_type $foldername
	 * @return KTWSAPI_Folder
	 */
	function add_folder($foldername)
	{
		$kt_folder_detail = $this->ktapi->soapclient->create_folder($this->ktapi->session, $this->folderid, $foldername);
		if (SOAP_Client::isError($kt_folder_detail))
		{
			return $kt_folder_detail;
		}
		
		if ($kt_folder_detail->status_code != 0)
		{
			return new PEAR_Error($kt_folder_detail->message);
		}
		
		return new KTWSAPI_Folder($this->ktapi, $kt_folder_detail);
	}
	
	function delete($reason)
	{
		// TODO: check why no transaction in folder_transactions
		$kt_response = $this->ktapi->soapclient->delete_folder($this->ktapi->session, $this->folderid, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}
		
		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	
	function rename($newname)
	{
		$kt_response = $this->ktapi->soapclient->rename_folder($this->ktapi->session, $this->folderid, $newname);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}
		
		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param KTWSAPI_Folder $ktwsapi_target_folder
	 * @param string $reason
	 */
	function move($ktwsapi_target_folder, $reason='')
	{
		assert(!is_null($ktwsapi_target_folder));
		assert(is_a($ktwsapi_target_folder,'KTWSAPI_Folder'));
		
		$kt_response = $this->ktapi->soapclient->move_folder($this->ktapi->session, $this->folderid,$ktwsapi_target_folder->get_folderid(), $newname);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}
		
		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}

	/**
	 * Enter description here...
	 *
	 * @param KTWSAPI_Folder $ktwsapi_target_folder
	 * @param string $reason
	 * @return unknown
	 */
	function copy($ktwsapi_target_folder, $reason='')
	{
		assert(!is_null($ktwsapi_target_folder));
		assert(is_a($ktwsapi_target_folder,'KTWSAPI_Folder'));
		
		$targetid=$ktwsapi_target_folder->get_folderid();
		
		$kt_response = $this->ktapi->soapclient->copy_folder($this->ktapi->session, $this->folderid,$targetid, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}
		
		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}	
	
	function add_document($filename, $title=null, $documenttype=null)
	{
		if (empty($title))
		{
			$title=basename($filename);	
		}		
		$basename=basename($filename);	
		
		if (empty($documenttype))
		{
			$documenttype='Default';
		}
		
		// First step - upload file
		$tempfilename = $this->_upload_file($filename,'A');
		if (PEAR::isError($tempfilename))
		{
			return new PEAR_Error($tempfilename->message);
		}

		// Second step - move file into KT
		$kt_document_detail = $this->ktapi->soapclient->add_document($this->ktapi->session, $this->folderid, $title, $basename, $documenttype, $tempfilename );
		if (SOAP_Client::isError($kt_document_detail))
		{
			return $kt_document_detail;
		}

		if ($kt_document_detail->status_code != 0)
		{
			return new PEAR_Error($kt_document_detail->message);
		}
		
		return true;    
	}
}

class KTWSAPI_Document extends KTWSAPI_FolderItem 
{
	var $document_id;
	var $title;
	var $document_type;
	var $version;
	var $filename;
	var $created_date;
	var $created_by;
	var $updated_date;
	var $updated_by;
	var $folder_id;
	var $workflow;
	var $workflow_state;
	var $checkout_by;
	var $full_path;
	
	function KTWSAPI_Document($ktapi, $kt_document_detail)
	{
		$this->ktapi=$ktapi;
		$this->document_id = $kt_document_detail->document_id;
		$this->title = $kt_document_detail->title;
		$this->document_type = $kt_document_detail->document_type;
		$this->version = $kt_document_detail->version;
		$this->filename = $kt_document_detail->filename;
		$this->created_date = $kt_document_detail->created_date;
		$this->created_by = $kt_document_detail->created_by;
		$this->updated_date = $kt_document_detail->updated_date;
		$this->updated_by = $kt_document_detail->updated_by;
		$this->folder_id = $kt_document_detail->folder_id;
		$this->workflow = $kt_document_detail->workflow;
		$this->workflow_state = $kt_document_detail->workflow_state;
		$this->checkout_by = $kt_document_detail->checkout_by;
		$this->full_path = $kt_document_detail->full_path;
	}
	
	function get($ktapi, $documentid, $loadinfo=true)	
	{
		assert(!is_null($ktapi));
		assert(is_a($ktapi, 'KTWSAPI'));
		assert(is_numeric($documentid));
		
		if ($loadinfo)
		{
			$kt_document_detail = $ktapi->soapclient->get_document_detail($ktapi->session, $documentid);
			if (SOAP_Client::isError($kt_document_detail))
			{
				return $kt_document_detail;
			}

			if ($kt_document_detail->status_code != 0)
			{
				return new PEAR_Error($kt_document_detail->message);
			}			
		}
		else 
		{
			$kt_document_detail = array(
				'document_id'=>$documentid,			
			);
		}
		
		return new KTWSAPI_Document($ktapi, $kt_document_detail);		
	}
	

	
	function checkin($filename, $reason, $major_update )
	{
		$basename=basename($filename);		 
		
		$tempfilename = $this->_upload_file($filename,'C', $this->document_id);
		if (PEAR::isError($tempfilename))
		{
			return new PEAR_Error($tempfilename->message);
		}
		
		$kt_response = $this->ktapi->soapclient->checkin_document($this->ktapi->session, $this->document_id, $basename, $reason, $tempfilename, $major_update );
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;    
	}
	
	function checkout($reason, $localpath=null)
	{		
		if (is_null($localpath))
		{
			$localpath = $this->ktapi->get_download_path();
		}
		
		if (!is_dir($localpath))
		{
			return new PEAR_Error('local path does not exist');
		}
		if (!is_writable($localpath))
		{
			return new PEAR_Error('local path is not writable');
		}
		
		$kt_response = $this->ktapi->soapclient->checkout_document($this->ktapi->session, $this->document_id, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		$url = $kt_response->message;
		
		$response = $this->_download_file($url, $localpath, $this->filename);
		if (PEAR::isError($response))
		{
			return new PEAR_Error($kt_response->message);
		}    	
		
		return true;  
	}
	
	function undo_checkout($reason)
	{
		$kt_response = $this->ktapi->soapclient->undo_document_checkout($this->ktapi->session, $this->document_id, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}

	function download($version=null, $localpath=null)
	{		
		if (is_null($localpath))
		{
			$localpath = $this->ktapi->get_download_path();
		}
		
		if (!is_dir($localpath))
		{
			return new PEAR_Error('local path does not exist');
		}
		if (!is_writable($localpath))
		{
			return new PEAR_Error('local path is not writable');
		}
		
		$kt_response = $this->ktapi->soapclient->download_document($this->ktapi->session, $this->document_id);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		$url = $kt_response->message;
		
		$response = $this->_download_file($url, $localpath, $this->filename);
		if (PEAR::isError($response))
		{
			return new PEAR_Error($kt_response->message);
		}    	
		
		return true;  
	}
	
	function delete($reason)
	{
		$kt_response = $this->ktapi->soapclient->delete_document($this->ktapi->session, $this->document_id, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}	
	
	function change_owner($username, $reason)
	{
		$kt_response = $this->ktapi->soapclient->change_document_owner($this->ktapi->session, $this->document_id, $username, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param KTWSAPI_Folder $folder 
	 * @param unknown_type $reason
	 * @param unknown_type $newtitle
	 * @param unknown_type $newfilename
	 */
	
	function copy($folder,$reason,$newtitle='',$newfilename='')
	{		
		$folder_id = $folder->folderid;
		$kt_response = $this->ktapi->soapclient->copy_document($this->ktapi->session, $this->document_id, $folder_id, $reason, $newtitle, $newfilename);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	function move($folder,$reason,$newtitle='',$newfilename='')
	{		
		$folder_id = $folder->folderid;
		$kt_response = $this->ktapi->soapclient->move_document($this->ktapi->session, $this->document_id, $folder_id, $reason, $newtitle, $newfilename);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}

	function change_document_type($documenttype)
	{
		$kt_response = $this->ktapi->soapclient->change_document_type($this->ktapi->session, $this->document_id, $documenttype);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	
	function rename_title( $newtitle)
	{
		$kt_response = $this->ktapi->soapclient->rename_document_title($this->ktapi->session, $this->document_id, $newtitle);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	function rename_filename( $newfilename)
	{
		$kt_response = $this->ktapi->soapclient->rename_document_filename($this->ktapi->session, $this->document_id, $newfilename);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
	}
	
	function start_workflow($workflow)
    {
		$kt_response = $this->ktapi->soapclient->start_document_workflow($this->ktapi->session, $this->document_id, $workflow);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
    }

    function delete_document_workflow()
    {
		$kt_response = $this->ktapi->soapclient->delete_document_workflow($this->ktapi->session, $this->document_id);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
    }
    
    function perform_document_workflow_transition($session_id,$document_id,$transition,$reason)
    {
		$kt_response = $this->ktapi->soapclient->delete_document_workflow($this->ktapi->session, $this->document_id, $transition, $reason);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return true;
    }
    
    function get_metadata()
    {
		$kt_metadata_response = $this->ktapi->soapclient->get_document_metadata($this->ktapi->session, $this->document_id );
		if (SOAP_Client::isError($kt_metadata_response))
		{
			return $kt_metadata_response;
		}

		if ($kt_metadata_response->status_code != 0)
		{
			return new PEAR_Error($kt_metadata_response->message);
		}
		
		return $kt_metadata_response;
    }

    function update_metadata($metadata)
    {
		$kt_response = $this->ktapi->soapclient->update_document_metadata($this->ktapi->session, $this->document_id, $metadata);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}

		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		
		return $kt_response;
    }    
    
    function get_transaction_history()
	{
		$kt_document_transaction_history_response = $this->ktapi->soapclient->get_document_transaction_history($this->ktapi->session, $this->document_id);
		if (SOAP_Client::isError($kt_document_transaction_history_response))
		{
			return $kt_document_transaction_history_response;
		}

		if ($kt_document_transaction_history_response->status_code != 0)
		{
			return new PEAR_Error($kt_document_transaction_history_response->message);
		}
		
		return $kt_document_transaction_history_response->history;
	}
	
    function get_version_history()
	{
		$kt_document_version_history_response = $this->ktapi->soapclient->get_document_version_history($this->ktapi->session, $this->document_id);
		if (SOAP_Client::isError($kt_document_version_history_response))
		{
			return $kt_document_version_history_response;
		}

		if ($kt_document_version_history_response->status_code != 0)
		{
			return new PEAR_Error($kt_document_version_history_response->message);
		}
		
		return $kt_document_version_history_response->history;
	}    
}

class KTWSAPI
{
	var $wsdl;
	/**
	 * Enter description here...
	 *
	 * @var SOAP_Client
	 */
	var $soapclient;
	var $timeout;
	var $session;
	var $download_path;
	
	function KTWSAPI($wsdl, $timeout=30)
	{
		$this->wsdl = new SOAP_WSDL($wsdl);
		$this->timeout = $timeout;
		$this->soapclient = $this->wsdl->getProxy();
		$this->soapclient->setOpt('timeout', $this->timeout);
		$this->download_path =  'c:/temp';		
	}
	
	function get_download_path()
	{
		return $this->download_path;
	}
	
	function set_download_path($download_path)
	{
		if (!is_dir($download_path))
		{
			return new PEAR_Error('local path is not writable');
		}
		
		if (!is_writable($download_path))
		{
			return new PEAR_Error('local path is not writable');
		}		
		$this->download_path = $download_path;
	}
	
	function start_anonymous_session($ip=null)
	{
		return $this->start_session('anonymous','',$ip);
	}
	
	function start_session($username, $password, $ip=null)
	{
		if (!is_null($this->session))
		{
			return new PEAR_Error(KTWSAPI_ERR_SESSION_IN_USE);
		}
		$kt_response = $this->soapclient->login($username, $password, $ip);
		if (SOAP_Client::isError($kt_response))
		{
			return $kt_response;
		}
		
		if ($kt_response->status_code == 0)
		{
			$this->session = $kt_response->message;
		}
		else 
		{
			return new PEAR_Error($kt_response->message);
		}	
		
		return $this->session;
	}
	
	function active_session($session, $ip=null)
	{
		if (!is_null($this->session))
		{
			return new PEAR_Error(KTWSAPI_ERR_SESSION_IN_USE);
		}		
		$this->session = $session;
		
		return $session;
	}
	
	function logout()
	{
		if (is_null($this->session))
		{
			return new PEAR_Error(KTWSAPI_ERR_SESSION_NOT_STARTED);
		}		
		
		$kt_response = $this->soapclient->logout($this->session);
		
		if ($kt_response->status_code != 0)
		{
			return new PEAR_Error($kt_response->message);
		}
		$this->session = null;

		return true;
	}
	
	/**
	 * 
	 *
	 * @return KTWSAPI_Folder
	 */
	function get_root_folder()
	{
		return $this->get_folder_by_id(1);
	}
	
	/**
	 * 
	 *
	 * @return KTWSAPI_Folder
	 */	
	function get_folder_by_id($folderid)
	{
		if (is_null($this->session))
		{
			return new PEAR_Error('A session is not active');			
		}
		
		$folder = KTWSAPI_Folder::get($this, $folderid);
				
		return $folder;
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param int $documentid
	 * @return KTWSAPI_Document
	 */
	function get_document_by_id($documentid)
	{
		if (is_null($this->session))
		{
			return new PEAR_Error('A session is not active');			
		}
		
		$document = KTWSAPI_Document::get($this, $documentid);
				
		return $document;
	}	
}

?>