<?php
/* Copyright (C) 2013 Andreu Bisquerra Gayà	<andreu@artadigital.com>
 * Released under the MIT license
 */

$res=@include("../../master.inc.php");
if (! $res) $res=@include("../../../master.inc.php");

$userid = GETPOST("u");
$hash = GETPOST("h");
$terminal = GETPOST("t");

if (is_numeric($userid)) $sql="SELECT rowid, entity from ".MAIN_DB_PREFIX."user where rowid=$userid and pass_crypted like '$hash%'";
else $sql="SELECT rowid, entity from ".MAIN_DB_PREFIX."user where login='$userid' and pass_crypted like '$hash%'";
$resql = $db->query($sql);
if($row = $db->fetch_array ($resql)){
$userid=$row[0];
if ($row[1]>0) $entity=$row[1]; else $entity=1;
}
else exit;
