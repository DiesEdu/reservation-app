<?php
/**
 * Simple SSE test endpoint - infinite loop
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

while (ob_get_level()) {
    ob_end_clean();
}

ob_flush();
flush();

echo "event: connected\n";
echo "data: {\"status\": \"ok\", \"message\": \"SSE connected\"}\n\n";

ob_flush();
flush();

$counter = 0;
while (true) {
    if (connection_aborted()) {
        break;
    }
    
    echo "data: {\"count\": $counter, \"time\": " . time() . "}\n\n";
    
    ob_flush();
    flush();
    
    $counter++;
    sleep(2);
}