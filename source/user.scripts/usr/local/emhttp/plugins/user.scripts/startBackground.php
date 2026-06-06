#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

$rc = 0;

/* Build command from argv. */
$command		= trim(str_replace($argv[0], "", implode(" ", $argv)));
$orig_command	= $command;
$orig_log_file	= dirname($command)."/log.txt";
$script_vars	= getScriptVariables($orig_command);
$script_name	= basename(dirname($orig_command));
$running_file	= "/tmp/user.scripts/running/".$script_name;
$finished_file	= "/tmp/user.scripts/finished/".$script_name;

/* Clear log if requested. */
if ($script_vars['clearLog'] ?? false) {
	@unlink($orig_log_file);
}

file_put_contents($orig_log_file, "Script Starting ".date("M d, Y H:i.s")."\n\n", FILE_APPEND);

/* Put wrapper into its own session/process group. */
if (function_exists('posix_setsid')) {
	@posix_setsid();
}

/* Store PGID for abort handling. */
$pgid = function_exists('posix_getpgid') ? @posix_getpgid(getmypid()) : getmypid();
if ((int)$pgid > 0) {
	file_put_contents($running_file, (string)(int)$pgid);
}

file_put_contents($orig_log_file, "Full logs for this script are available at ".$orig_log_file."\n\n", FILE_APPEND);

/* Build command safely. */
$default_args	= trim((string)($script_vars['argumentDefault'] ?? ""));
$exec_cmd		= escapeshellarg($orig_command);

if ($default_args !== "") {
	$exec_cmd .= " ".$default_args;
}

$exec_cmd .= " >> ".escapeshellarg($orig_log_file)." 2>&1";

/* Execute user script. */
exec($exec_cmd, $out, $return);
$rc = (int)$return;

file_put_contents($orig_log_file, "Script Finished ".date("M d, Y H:i.s")."\n\n", FILE_APPEND);

/* Cleanup tracking files. */
@unlink($running_file);
file_put_contents($finished_file, "finished");
file_put_contents($orig_log_file, "Full logs for this script are available at ".$orig_log_file."\n\n", FILE_APPEND);

exit($rc);
?>
