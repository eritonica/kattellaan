<?php
/*
 * Environment configuration.
 *
 * Production paths are kept as defaults when they exist; otherwise paths
 * relative to the repository are used so the site can run locally.
 * Every value can be overridden with an environment variable.
 */

function kattellaan_env($name, $production, $local) {
	$value = getenv($name);
	if ($value !== false && $value !== "") return $value;
	return file_exists($production) ? $production : $local;
}

$kattellaan_root = dirname(__DIR__);

// File containing base64("dbuser:dbpassword"), see passwd-gen.php.
define('KATTELLAAN_PASSWD_FILE', kattellaan_env(
	'KATTELLAAN_PASSWD_FILE',
	'/home/temek/kattellaan/.passwd',
	$kattellaan_root . '/.passwd'
));

// Directory where uploaded pictures are stored (served as /uploads/).
define('KATTELLAAN_UPLOAD_DIR', rtrim(kattellaan_env(
	'KATTELLAAN_UPLOAD_DIR',
	'/home/temek/kattellaan-live/uploads',
	$kattellaan_root . '/uploads'
), '/\\') . '/');

// Running outside the production server (no /home/temek/kattellaan).
define('KATTELLAAN_LOCAL', getenv('KATTELLAAN_LOCAL') !== false
	? (bool)getenv('KATTELLAAN_LOCAL')
	: !file_exists('/home/temek/kattellaan'));

// Where outgoing mail is written when running locally.
define('KATTELLAAN_MAIL_LOG', getenv('KATTELLAAN_MAIL_LOG') ?: $kattellaan_root . '/.mail.log');

// mail() replacement: sends normally in production, logs to a file locally.
function kattellaan_mail($to, $subject, $message, $headers = "") {
	if (!KATTELLAAN_LOCAL) {
		return mail($to, $subject, $message, $headers);
	}
	$entry = "==== " . date("c") . "\r\nTo: " . $to . "\r\nSubject: " . $subject . "\r\n" .
		$headers . "\r\n\r\n" . $message . "\r\n\r\n";
	return file_put_contents(KATTELLAAN_MAIL_LOG, $entry, FILE_APPEND) !== false;
}

define('KATTELLAAN_DB_HOST', getenv('KATTELLAAN_DB_HOST') ?: '127.0.0.1');
define('KATTELLAAN_DB_NAME', getenv('KATTELLAAN_DB_NAME') ?: 'kattellaan');

function kattellaan_db_credentials() {
	$contents = @file_get_contents(KATTELLAAN_PASSWD_FILE);
	if ($contents === false) {
		throw new Exception("Database password file not found: " . KATTELLAAN_PASSWD_FILE .
			" (create it with: php passwd-gen.php <user> <password>)");
	}
	return explode(":", base64_decode(trim($contents)), 2);
}

?>
