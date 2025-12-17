<?php
// Bootstrap file to ensure clean output buffering
// Include this at the very start of files that need headers
if (!ob_get_level()) {
    ob_start();
}

