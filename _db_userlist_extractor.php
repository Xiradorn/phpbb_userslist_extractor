<?php
/**
*
* Simple userslist extractor for mass mailing system
* ONLY for the FOUNDER!!!!
* other user cannot launch the script
* PHP >= 5.3.0
*
* @package userslist Extractor
* @author Xiradorn <http://xiradorn.it>
* @version 2.0.0
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
 * file_type : valori possibili txt, gz, bz2
 */

$cfg = array(
	'file_type'						=> 'txt',
	'num_righe_per_query'			=> 100,
	'exclude_usernames'				=> array(),
	'exclude_usernames_id'			=> array()
);

####################################################################
# DO NOT EDIT THE FOLLOWING CODE
####################################################################
// global varible
$file_path = __DIR__ . '/__userlist.txt';
$phar_path = __DIR__ . '/__userlist.tar';
$trigger_msg = '';
$ok_token = false;

$mode = utf8_normalize_nfc(request_var('mode', '', true));
// before recall function try to recall mode

if (isset($mode) && $mode === 'Scarica') {
	$file_check = false;
	if (file_exists($file_path)) {
		$file_dwn = $phpbb_root_path . basename($file_path);
		$file_check = true;
	} elseif (file_exists($phar_path.".gz")) {
		$file_dwn = $phpbb_root_path . basename($phar_path.".gz");
		$file_check = true;
	} elseif (file_exists($phar_path.".bz2")) {
		$file_dwn = $phpbb_root_path . basename($phar_path.".bz2");
		$file_check = true;
	}

	if ($file_check) {
		$trigger_msg = "Link File: ";
		$trigger_msg = "<a target=\"_blank\" href=\"$file_dwn\">" . basename($file_dwn) . "</a>";
		$ok_token = true;
	} else {
		$trigger_msg = "File Non Presente. Ripeti il processo";
	}

} elseif (isset($mode) && $mode === 'Elimina') {
	__file_delete(array(
		$file_path,
		$phar_path,
		$phar_path.".gz",
		$phar_path.".bz2"
	));

	$trigger_msg = "Grazie per aver cancellato i file";
	$ok_token = true;
} else {
	// recall function
	phpbb_userlist_extractor($trigger_msg, $ok_token);
	$ok_token_form = $ok_token;
}

#######################################################
#
# * TECNICAL PART FOR GENERATION FILE AND STREAM
# * DON'T TOUCH IS REALLY DANGEROUS IF YOU DON'T HAVE
# * A BIT OF PHP SKILLS.
#
#######################################################

function phpbb_userlist_extractor(&$trigger_msg, &$ok_token) {
	global $db, $tracking_topics, $user, $config;
	global $auth, $request, $phpbb_container;
	global $cfg, $file_path, $phar_path;

	// first check only for registered users
	if ($user->data['is_registered']) {
		// second check is only for ADMIN acl perm
		if ($auth->acl_get('a_')) {
			if ($user->data['user_type'] == USER_FOUNDER) {
				$cfg = (object) $cfg;
				$trigger_msg = "Accesso Concesso. Benvenuto a bordo Capitano : " . $user->data['username'];
				$ok_token = true; // used into template

				_db_user_extractor($cfg->direct_download, $cfg->file_type);
			} else {
				$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Devi essere un FOUNDER !!!";
				exit();
			}
		} else {
			$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Solo gli Admin possono passare alla prossima verifica";
			exit();
		}
	} else {
		$trigger_msg = "Permesso Negato. Autorizzazione non Concessa !!! Loggati per il Check di Controllo";
		exit();
	}
}


function _db_user_extractor($direct_download = true, $type = "text") {
	global $db, $tracking_topics, $user, $config;
	global $auth, $request, $phpbb_container;
	global $cfg, $file_path, $phar_path;

	// file reset on the server
	__file_delete(array(
		$file_path,
		$phar_path,
		$phar_path.".gz",
		$phar_path.".bz2"
	));

	// user query extracting but first we determine the number of field
	$sql = "SELECT COUNT(username) as counter
	FROM " . USERS_TABLE;

	$result = $db->sql_query($sql);

	while ($cnt = $db->sql_fetchrow($result)) {
		$row_num = $cnt['counter'];
	}

	$db->sql_freeresult($result);

	for ($i = 0; $i < $row_num; $i+=$cfg->num_righe_per_query) {
		$sql  = "SELECT username, user_type";
		$sql .= " FROM " . USERS_TABLE;

		// check if the ary exceprion is empty or not
		// IMPORTANT ary of username id are prioritary on the username
		if (!empty($cfg->exclude_usernames_id)) {
			$ary_user = implode(', ', array_map('__quote_val_for_ary', $cfg->exclude_usernames_id));
			$sql .= " WHERE user_id NOT IN ($ary_user)";
		} elseif (!empty($cfg->exclude_usernames)) {
			$ary_user = implode(', ', array_map('__quote_val_for_ary', $cfg->exclude_usernames));
			$sql .= " WHERE username NOT IN ($ary_user)";
		}

		$sql .= " ORDER BY username ASC";
		$sql .= " LIMIT {$i}, $cfg->num_righe_per_query";

		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result)) {
			// thanks to gioweb to indirect suggestion
			// dublicate voice insert FIX
			if (intval($row['user_type']) === 0 || intval($row['user_type']) === 3) {
				__file_manager($row['username'], 1);
			}
		}
		$db->sql_freeresult($result);
	}

	// compress file if you wish
	if ($cfg->file_type === 'gz' or $cfg->file_type === 'bz2' or $cfg->file_type === 'tar') {
		__file_manager('', 2, $cfg->file_type);
	}
}

function __file_manager($content = '', $mode = 1, $ext = '') {
	global $file_path, $phar_path;
	$file_name = basename($file_path);
	$ext_allowed = ['gz', 'bz2'];

	switch ($mode) {
		// 1: build mode a base file_path
		// used in i cycle as for or while
		case 1:
			// first we check if content is not empty
			if (empty($content) or $content === '') {
				return false;
			} else {
				// start output buffering
				ob_start();
				echo $content . "\r\n";
				$content_file = @ob_get_contents();
				@ob_end_clean();

				// file opening and put the curson at the end of file res
				$fh = @fopen($file_path, 'a');
				// now we put content into it appended to it
				// at the end
				@fwrite($fh, $content_file);
				@fclose($fh);

				return true;
			}

			break;

		// 2: compress mode
		// for a single intire file
		case 2:
			try {
				$phar = new PharData($phar_path);
				$phar->addFile(basename($file_path));

				if (in_array($ext, $ext_allowed)) {
					($ext === 'gz') ? $phar->compress(Phar::GZ) : $phar->compress(Phar::BZ2);
				}
				unset($phar);

				// delete file tar and txt
				__file_delete(array(
					$file_path, $phar_path
				));

			} catch (Exception $e) {
				$e->getMessage();
			}
			break;

		default:
			echo "Nessuna funzionalità particolare";
			break;
	}
}

function __file_delete($file_path_ary = array()) {
	foreach ($file_path_ary as $file_path) {
		if (file_exists($file_path)) {
			if (function_exists('delete')) {
				delete($file_path);
			} elseif (function_exists('del')) {
				del($file_path);
			} elseif (function_exists('unlink')) {
				unlink($file_path);
			}
		}
	}
}

function __quote_val_for_ary($val) {
	return "'" . $val . "'";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<title>phpBB UserList Extractor - Sir Xiradorn - 2016</title>

	<style>
	@import url(https://fonts.googleapis.com/css?family=Roboto:400,300,500,700);

	::-moz-selection {
		color: white;
		background-color: #FF3535;
	}

	::selection {
		color: white;
		background-color: #FF3535;
	}

	html, body {
		background-color: #DBDBDB;
		font-family: "Roboto";
		margin: 0;
		padding: 0;
		width: 100%;
		position: relative;
		font-size: 1em;
	}
	.form_container {
		border: 1px solid #D1D1D1;
		box-shadow: 0px 0px 3px #D1D1D1;
		margin: 10px auto 0;
		max-width: 700px;
		padding: 10px;
		text-align: center;
		background-color: #fff;
	}

	label, .btns {
		display: block;
		margin-bottom: 10px;
	}

	.btns {
		margin-top: 25px;
	}

	input {
		background-color: #666666;
		border: 1px solid white;
		padding: 4px 12px;
	 	color: white;
		text-transform: uppercase;
		margin-bottom: 10px;
		cursor: pointer;
	}

	input:hover {
		background-color: #444444;
	}

	.check {
		display: block;
		padding: 20px;
		margin-bottom: 10px;
		background-color: #FFA8A8;
		border: 1px solid #FF3535;
	}

	.success {
		background-color: #90F9B7;
		border: 1px solid #49F488;
	}

	a, a:link, a:visited {
		color: crimson;
		text-decoration: none;
	}
	a:hover {
		color: firebrick;
		text-decoration: underline;
	}

	.copyright {
		border-top: 1px solid #D1D1D1;
		padding-top: 10px;
		font-size: .8em;
	}

	</style>
</head>
<body>
	<div class="form_container">
		<h1>phpBB UserList Extractor</h1>
		<div class="check<?php if ($ok_token): ?> success<?php endif; ?>">
			<?php echo $trigger_msg; ?>
		</div>
		<?php if ($ok_token_form): ?>
		<form class="" method="get">
			<label for="download">

				Cliccando sul tasto Scarica potrai scaricare il file generato.<br>
				Cliccando sul tasto Elimina potrai, invece, cancellare i file generati.<br>
				Se non vuoi usare il download in rete potrai scaricare il tutto tramite FTP Client
			</label>
			<div class="btns">
				<input type="submit" name="mode" value="Scarica">
				<input type="submit" name="mode" value="Elimina">
			</div>
		</form>
		<?php endif; ?>

		<?php if ($mode === 'Scarica'): ?>
			<form class="" method="get">
				<label for="download">
					Cliccando sul tasto Elimina potrai cancellare i file generati e appena scaricati.<br>
				</label>
				<div class="btns">
					<input type="submit" name="mode" value="Elimina">
				</div>
			</form>
		<?php endif; ?>
		<p class="copyright">Sir Xiradorn &copy; 2016 - <a target="_blank" href="http://xiradorn.it" alt="Xiradorn Lab">Xiradorn Lab</a></p>
	</div>
</body>
</html>
