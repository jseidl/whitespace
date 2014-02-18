<?php

$payload = '';

while (false !== ($line = fgets(STDIN))) {
    $payload .= $line;
}//end :: while

print "= WCC = Command output ==========================\n\n";
print gzinflate($payload);
print "\n\n";
print "= WCC = EOF =====================================\n\n";

?>
