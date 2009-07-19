<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Weave Basic Object Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#   Luca Tettamanti
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****

	require_once 'weave_storage.php';
	require_once 'weave_authentication.php';
	require_once 'weave_basic_object.php';
	require_once 'weave_constants.php';

	function report_problem($message, $code = 503)
	{
		$headers = array('400' => '400 Bad Request',
					'401' => '401 Unauthorized',
					'404' => '404 Not Found',
					'412' => '412 Precondition Failed',
					'503' => '503 Service Unavailable');
		header('HTTP/1.1 ' . $headers{$code},true,$code);
		
		if ($code == 401)
		{
			header('WWW-Authenticate: Basic realm="Weave"');
		}
		
		exit(json_encode($message));
	}
	
	
	header("Content-type: application/json");
	
	
	#get the http auth user data
	
	$auth_user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;
	$auth_pw = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : null;

	if (is_null($auth_user) || is_null($auth_pw)) 
	{
		/* CGI/FCGI auth workarounds */
		$auth_str = null;
		if (array_key_exists('Authorization', $_SERVER))
			/* Standard fastcgi configuration */
			$auth_str = $_SERVER['Authorization'];
		else if (array_key_exists('AUTHORIZATION', $_SERVER))
			/* Alternate fastcgi configuration */
			$auth_str = $_SERVER['AUTHORIZATION'];
		else if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER))
			/* IIS/ISAPI and newer (yet to be released) fastcgi */
			$auth_str = $_SERVER['HTTP_AUTHORIZATION'];
		else if (array_key_exists('REDIRECT_HTTP_AUTHORIZATION', $_SERVER))
			/* mod_rewrite - per-directory internal redirect */
			$auth_str = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
		if (!is_null($auth_str)) 
		{
			/* Basic base64 auth string */
			if (preg_match('/Basic\s+(.*)$/', $auth_str)) 
			{
				$auth_str = substr($auth_str, 6);
				$auth_str = base64_decode($auth_str, true);
				if ($auth_str != FALSE) {
					$tmp = explode(':', $auth_str);
					if (count($tmp) == 2) 
					{
						$auth_user = $tmp[0];
						$auth_pw = $tmp[1];
					}
				}
			}
		}
	}

	#Basic path extraction and validation. No point in going on if these are missing
	$path = '/';
	if (!empty($_SERVER['PATH_INFO'])) {
		$path = $_SERVER['PATH_INFO'];
	}
	else if (!empty($_SERVER['ORIG_PATH_INFO'])) {
		$path = $_SERVER['ORIG_PATH_INFO'];
	}
	$path = substr($path, 1); #chop the lead slash
	list($username, $collection, $id) = explode('/', $path.'//');

	# Lowercase username before checking path
	$username = strtolower($username);
	$auth_user = strtolower($auth_user);
	
	if (!$username)
		report_problem('3', 400);

	if ($auth_user != $username)
		report_problem("5", 401);
	
	#only a get has meaning without a collection (GET returns a collection list)
	if (!$collection && $_SERVER['REQUEST_METHOD'] != 'GET')
		report_problem("1", 400);
	
	#Auth the user
	try 
	{
		$authdb = get_auth_object();
		if (!$authdb->authenticate_user(strtolower($auth_user), $auth_pw))
			report_problem('Authentication failed', '401');
	}
	catch(Exception $e)
	{
		report_problem($e->getMessage(), $e->getCode());
	}

	#set an X-Weave-Alert header if the user needs to know something
	if ($alert = $authdb->get_user_alert())
		header("X-Weave-Alert: $alert", false);
	
	#user passes, onto actually getting the data
	if ($_SERVER['REQUEST_METHOD'] == 'GET')
	{
		try
		{
			$db = get_storage_read_object($username, WEAVE_SHARE_DBH ? $authdb->get_connection() : null);	
		}
		catch(Exception $e)
		{
			report_problem($e->getMessage(), $e->getCode());
		}
		
		#if there's no collection provided, it's the root. Return a list of collections
		if (!$collection)
			exit(json_encode($db->get_collection_list()));
		
		if ($id) #retrieve a single record
		{
			try
			{
				$wbo = $db->retrieve_objects($collection, $id, 1); #get the full contents of one record
			}
			catch(Exception $e)
			{
				report_problem($e->getMessage(), $e->getCode());
			}
			
			if (count($wbo) > 0)
				echo $wbo[0]->json();
			else
				report_problem("record not found", 404);
		}
		else #retrieve a batch of records. Sadly, due to potential record sizes, have the storage object stream the output...
		{
			$full = array_key_exists('full', $_GET) ? $_GET['full'] : null;
			$outputter = new WBOJsonOutput($full);
			try 
			{
				$ids = $db->retrieve_objects($collection, null, $full, $outputter,
							array_key_exists('parentid', $_GET) ? $_GET['parentid'] : null, 
							array_key_exists('newer', $_GET) ? $_GET['newer'] : null, 
							array_key_exists('older', $_GET) ? $_GET['older'] : null, 
							array_key_exists('sort', $_GET) ? $_GET['sort'] : null, 
							array_key_exists('limit', $_GET) ? $_GET['limit'] : null, 
							array_key_exists('offset', $_GET) ? $_GET['offset'] : null);
			}
			catch(Exception $e)
			{
				report_problem($e->getMessage(), $e->getCode());
			}		
		}
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'PUT') #add a single record to the server
	{
		$putdata = fopen("php://input", "r");
		$json = '';
		while ($data = fread($putdata,2048)) {$json .= $data;};
		
		$wbo = new wbo();
		if (!$wbo->extract_json($json))
			report_problem("6", 400);
		
		#all server-side tests pass. now need the db connection
		try
		{
			$db = get_storage_write_object($username, WEAVE_SHARE_DBH ? $authdb->get_connection() : null);	
		}
		catch(Exception $e)
		{
			report_problem($e->getMessage(), $e->getCode());
		}

		if (array_key_exists('HTTP_X_IF_UNMODIFIED_SINCE', $_SERVER) 
				&& $db->get_max_timestamp($collection) > round((float)$_SERVER['HTTP_X_IF_UNMODIFIED_SINCE'], 2))
			report_problem("4", 412);	
		
		#use the url if the json object doesn't have an id
		if (!$wbo->id() && $id) { $wbo->id($id); }
		
		$wbo->collection($collection);
		$wbo->modified(microtime(1)); #current microtime

		if ($wbo->validate())
		{
			try
			{
				#if there's no payload (as opposed to blank), then update the metadata
				if ($wbo->payload_exists())
					$db->store_object($wbo);
				else
					$db->update_object($wbo);
			}
			catch (Exception $e)
			{
				report_problem($e->getMessage(), $e->getCode());
			}
			echo json_encode($wbo->modified());
		}
		else
		{
			report_problem("8", 400);
		}
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		#stupid php being helpful with input data...
		$putdata = fopen("php://input", "r");
		$jsonstring = '';
		while ($data = fread($putdata,2048)) {$jsonstring .= $data;}
		$json = json_decode($jsonstring, true);

		if (!$json)
			report_problem("6", 400);

		#now need the db connection
		try
		{
			$db = get_storage_write_object($username, WEAVE_SHARE_DBH ? $authdb->get_connection() : null);	
		}
		catch(Exception $e)
		{
			report_problem($e->getMessage(), $e->getCode());
		}

		if (array_key_exists('HTTP_X_IF_UNMODIFIED_SINCE', $_SERVER) 
				&& $db->get_max_timestamp($collection) > round((float)$_SERVER['HTTP_X_IF_UNMODIFIED_SINCE'], 2))
			report_problem("4", 412);	
		
		
		$success_ids = array();
		$failed_ids = array();
		
		$modified = microtime(1);
		
		try
		{
			$db->begin_transaction();
		}
		catch(Exception $e)
		{
			report_problem($e->getMessage(), $e->getCode());
		}

		foreach ($json as $wbo_data)
		{
			$wbo = new wbo();
			if (!$wbo->extract_json($wbo_data))
			{
				$failed_ids[$wbo->id()] = $wbo->get_error();
				continue;
			}
			
			$wbo->collection($collection);
			$wbo->modified($modified);
			

			if ($wbo->validate())
			{
				try
				{
					#if there's no payload (as opposed to blank), then update the metadata
					if ($wbo->payload_exists())
					{
						$db->store_object($wbo);
					}
					else
					{
						$db->update_object($wbo);
					}
				}
				catch (Exception $e)
				{
					$failed_ids[$wbo->id()] = $e->getMessage();
					continue;
				}
				$success_ids[] = $wbo->id();
			}
			else
			{
				$failed_ids[$wbo->id()] = $wbo->get_error();
			}
		}
		$db->commit_transaction();
		
		echo json_encode(array('modified' => round($modified, 2), 'success' => $success_ids, 'failed' => $failed_ids));
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'DELETE')
	{
		try
		{
			$db = get_storage_write_object($username, WEAVE_SHARE_DBH ? $authdb->get_connection() : null);	
		}
		catch(Exception $e)
		{
			report_problem($e->getMessage(), $e->getCode());
		}

		if (array_key_exists('HTTP_X_IF_UNMODIFIED_SINCE', $_SERVER) 
				&& $db->get_max_timestamp($collection) > round((float)$_SERVER['HTTP_X_IF_UNMODIFIED_SINCE'], 2))
			report_problem("4", 412);	

		$timestamp = round(microtime(1), 2);
		if ($id)
		{
			try
			{
				$db->delete_object($collection, $id);
			}
			catch(Exception $e)
			{
				report_problem($e->getMessage(), $e->getCode());
			}
			echo json_encode($timestamp);
		}
		else
		{
			try
			{
				$db->delete_objects($collection, null,  
							array_key_exists('parentid', $_GET) ? $_GET['parentid'] : null, 
							array_key_exists('newer', $_GET) ? $_GET['newer'] : null, 
							array_key_exists('older', $_GET) ? $_GET['older'] : null, 
							array_key_exists('sort', $_GET) ? $_GET['sort'] : null, 
							array_key_exists('limit', $_GET) ? $_GET['limit'] : null, 
							array_key_exists('offset', $_GET) ? $_GET['offset'] : null);			
			}
			catch(Exception $e)
			{
				report_problem($e->getMessage(), $e->getCode());
			}
			echo json_encode($timestamp);
		}
	}
	else
	{
		#bad protocol. There are protocols left? HEAD, I guess.
		report_problem("1", 400);
	}
		
#The datasets we might be dealing with here are too large for sticking it all into an array, so
#we need to define a direct-output method for the storage class to use. If we start producing multiples
#(unlikely), we can put them in their own class.

class WBOJsonOutput  
{
	private $_full = null;
	private $_comma_flag = 0;

	function __construct ($full = null)
	{
		$this->_full = $full;
	}

	function output($sth)
	{
		echo '[';
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($this->_comma_flag) { echo ','; } else { $this->_comma_flag = 1; }
			if ($this->_full)
			{
				$wbo = new wbo();
				$wbo->populate($result{'id'}, $result{'collection'}, $result{'parentid'}, $result{'modified'}, $result{'depth'}, $result{'sortindex'}, $result{'payload'});
				echo $wbo->json();
			}
			else
				echo json_encode($result{'id'});
		}

		echo ']';
		return 1;
	}
}
?>
