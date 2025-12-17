<?php
// Temporary debug script for Railway: shows which PDO drivers are available.
header('Content-Type: text/plain');
echo "PDO drivers:\n";
var_dump(PDO::getAvailableDrivers());

