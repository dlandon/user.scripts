#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

/* Build command from argv */
$command = trim(str_replace($argv[0], "", implode(" ", $argv)));
$origCommand = $command;
$origLogFile = dirname($command) . "/log.txt";
$scriptVariables = getScriptVariables($origCommand);

/* Clear log if requested */
if ($scriptVariables['clearLog'] ?? false) {
	@unlink($origLogFile);
}

file_put_contents($origLogFile, "Script Starting " . date("M d, Y H:i.s") . "\n\n", FILE_APPEND);

/* Escape spaces */
$commandEscaped = str_replace(" ", "\ ", $command);
$logFile = str_replace(" ", "\ ", $origLogFile);
$scriptName = basename(dirname($origCommand));

/* Append default arguments and redirect output */
$commandEscaped .= " " . ($scriptVariables['argumentDefault'] ?? "") . " >> $logFile 2>&1";

/* Put process into its own session (new PGID) */
if (function_exists('posix_setsid')) {
	@posix_setsid();
}

/* Store PGID for abort handling */
$pgid = function_exists('posix_getpgid') ? @posix_getpgid(getmypid()) : getmypid();
if ((int)$pgid > 0) {
	file_put_contents("/tmp/user.scripts/running/$scriptName", (int)$pgid);
}

file_put_contents($origLogFile, "Full logs for this script are available at $origLogFile\n\n", FILE_APPEND);

/* Execute user script */
exec($commandEscaped);

file_put_contents($origLogFile, "Script Finished " . date("M d, Y H:i.s") . "\n\n", FILE_APPEND);

/* Cleanup tracking files */
@unlink("/tmp/user.scripts/running/$scriptName");
file_put_contents("/tmp/user.scripts/finished/$scriptName", "finished");
file_put_contents($origLogFile, "Full logs for this script are available at $origLogFile\n\n", FILE_APPEND);

exit(0);
?>
