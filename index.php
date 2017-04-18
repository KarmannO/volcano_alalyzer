<?php
include_once ("ArchiveGetter.php");

use \app\ArchiveGetter;

$t = new ArchiveGetter();
$t->getLinksWithInterval(10);

?>