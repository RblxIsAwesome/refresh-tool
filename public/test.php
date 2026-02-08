<?php
echo "TEST FILE WORKS!";
echo "<br>PHP Version: " . phpversion();
echo "<br>Current Directory: " . __DIR__;
echo "<br>Files in public/: ";
print_r(scandir(__DIR__));
?>
