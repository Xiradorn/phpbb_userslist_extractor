<?php
/**
*
* Simple userslist extractor for mass mailing system
* ONLY for the FOUNDER!!!!
* other user cannot launch the script
* @package userslist Extractor
* @author Xiradorn <http://xiradorn.it>
* @version 1.2.0
*
*/


// phpbb start stuff
/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

#####################################################################

/**
 * Configura i valori
 * direct_download : puo essere vero o falso. Vero = download a volo; Falso = salvataggio su server
 * file_type : valori possibili text oppure gz
 */

$cfg = array(
	'direct_download' 				=> true,
	'file_type'						=> 'gz',
	'num_righe_per_query'			=> 100
);

####################################################################
# DO NOT EDIT THE FOLLOWING CODE
####################################################################

// first check only for registered users
if ($user->data['is_registered']) {
	// second check is only for ADMIN acl perm
	if ($auth->acl_get('a_')) {
		if ($user->data['user_type'] == USER_FOUNDER) {
			$cfg = (object) $cfg;
			$trigger_msg = "Accesso Concesso. Benvenuto a bordo Capitano : " . $user->data['username'];
			trigger_page($trigger_msg);

			_db_user_extractor($cfg->direct_download, $cfg->file_type);
		} else {
			$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Devi essere un FOUNDER !!!";
			trigger_page($trigger_msg);
			exit();
		}
	} else {
		$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Solo gli Admin possono passare alla prossima verifica";
		trigger_page($trigger_msg);
		exit();
	}
} else {
	$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Loggati per il Check di Controllo";
	trigger_page($trigger_msg);
	exit();
}

#######################################################
#
# * TECNICAL PART FOR GENERATION FILE AND STREAM
# * DON'T TOUCH IS REALLY DANGEROUS IF YOU DON'T HAVE
# * A BIT OF PHP SKILLS.
#
#######################################################


/**
 * Function for generate file
 * @param  boolean $direct_download enable local storage or direct download
 * @param  string  $type            enable text or zip extraction file
 */
function _db_user_extractor($direct_download = true, $type = "text") {
	global $db, $tracking_topics, $user, $config, $auth, $request, $phpbb_container;
	global $cfg;

	// user query extracting but first we determine the number of field
	$sql = "SELECT COUNT(username) as counter
			FROM " . USERS_TABLE;

	$result = $db->sql_query($sql);

	while ($cnt = $db->sql_fetchrow($result)) {
		$row_num = $cnt['counter'];
	}

	$db->sql_freeresult($result);

	$userstring = '';
	for ($i = 0; $i < $row_num; $i=$i+$cfg->num_righe_per_query) {
		$sql = "SELECT username, user_type
				FROM " . USERS_TABLE . "
				ORDER BY username ASC
				LIMIT {$i}, $cfg->num_righe_per_query";

		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result)) {
			// thanks to gioweb to indirect suggestion
			// dublicate voice insert FIX
			if (intval($row['user_type']) === 0 || intval($row['user_type']) === 3) {
				$userstring .= $row['username'] . "\r\n";
			}
		}
		$db->sql_freeresult($result);
	}

	// now check if the file is saved on local storage or directy downlodable
	// if direct is check on true the file generated for on local will be deleted
	// after serving from header content
	__file_down_gen($userstring, $direct_download, $type);
}

/**
 * File Generation
 * @param  string  $content_file    string from query
 * @param  boolean $direct_download direct download or local storage
 * @param  string  $type            text or gz file type
 */
function __file_down_gen($content_file = '', $direct_download = true, $type = 'text') {
	// just for clear file if are on the local storage
	__file_delete(true, true);

	$dir = __DIR__;
	$file_path = $dir . "/_user_export.txt";
	$file = basename($file_path);

	$fh = @fopen($file, 'w+');
	@fwrite($fh, $content_file);
	@fclose($fh);

	if ($type === 'gz') {
		$gzfile_path = $dir . "/_user_export.txt.gz";
		$gzfile = basename($gzfile_path);

		$gzdata = gzencode(file_get_contents($file_path, true), 9);

		$gzfh = @fopen($gzfile_path, 'w+');
		@fwrite($gzfh, $gzdata);
		@fclose($gzfh);

		__file_delete(true, false);

		/* Header Building Stuff */
		$content_type = "application/force-download";

		$file = $gzfile;
		$file_path = $gzfile_path;
	} else {
		$content_type = "text/plain";
	}

	if ($direct_download) {
		header("Content-Type: {$content_type}");
		header('Content-Disposition: attachment; filename='.$file);
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_path));
		readfile($file_path);

		// destroy file after sending
		__file_delete(true, false);

		exit();
	}
	return true;
}

/**
 *  * Service function for file erasing
 * @param  boolean $f   check true or false for delete file
 * @param  boolean $gzf check true or false for delete zgfile
 *
 */
function __file_delete($f = false, $gzf = false) {
	$return = false;
	$dir = __DIR__;
	$file_path = $dir . "/_user_export.txt";
	$gzfile_path = $dir . "/_user_export.txt.gz";

	if (file_exists($file_path) and $f === true) {
		if (function_exists('delete')) {
			delete($file_path);
			$return = true;
		} elseif (function_exists('del')) {
			del($file_path);
			$return = true;
		} elseif (function_exists('unlink')) {
			unlink($file_path);
			$return = true;
		}
	}

	if (file_exists($gzfile_path) and $gzf === true) {
		if (function_exists('delete')) {
			delete($gzfile_path);
			$return = true;
		} elseif (function_exists('del')) {
			del($gzfile_path);
			$return = true;
		} elseif (function_exists('unlink')) {
			unlink($gzfile_path);
			$return = true;
		}
	}
	return $return;
}

/**
 * Page error message trigger
 * @param  string $trigger_msg Messaggio in display
 */
function trigger_page($trigger_msg = '') {
	// build a service html page for post response coding
	$html = <<<HTML
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Xiradorn UsersExport phpbb - Trigger Page</title>
	</head>
	<body>
		{$trigger_msg}
	</body>
	</html>
HTML;

	echo $html;
}
