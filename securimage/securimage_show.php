<?php

include 'securimage.php';
//print_r($_SESSION);
$img = new Securimage();
//echo $img->getCode();

$img->show(); // alternate use:  $img->show('/path/to/background.jpg');

?>
