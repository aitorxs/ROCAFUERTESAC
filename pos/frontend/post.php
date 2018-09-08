<?php
/* Copyright (C) 2013-2017 Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory
session_start();
if(isset($_SESSION['dol_login'])){
    require_once (DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
    $text = GETPOST('text','alpha');

    //$file_temp = dol_buildpath("/pos/frontend/chat.html", 0);
    $file_temp = dirname(dol_buildpath("/pos/frontend/post.php"))."/chat.html";
    $fp = fopen($file_temp, 'a');
    fwrite($fp, "<div class='msgln'>(".date("j-n G:i").") <b>".$_SESSION['dol_login']."</b>: ".stripslashes(htmlspecialchars($text))."<br></div>");
    fclose($fp);

    //return 1;
}
?>