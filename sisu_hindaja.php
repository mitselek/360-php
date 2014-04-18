<?
if( handshake( $SID ) === false ) die( "SMP: Forbidden" );
global $hid_ID_field;
$scale = 420;
/**/precho( $user_data );

$kysitlus = $user_data[kysitlus];
$hindaja = $user_data[num];
$SIDSTR = "./?SID=$SID";
$lang = $user_data[language];

if( $hindan == "uuesti" ) reset_relations( $hindaja );
$plokid_a = arrange_plokid_a( SQL_select_table( "plokk", "title", "blob_plokid", "kysitlus=$kysitlus order by plokk", false ) );
$kysimustik = compile_kysimused( $kysitlus, $plokid_a );
submit_vastus( $rel, $kys, $tekst, $pidev_x, $eioska_x );
salvesta_tekst( $rel, $kys, $tekst );
mark_valmis( $valmis );
//$vastus_count = vastus_count( $rel );
//if( check_form_completion( $plokid_a, $vastus_count );

$relations_a[ise] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='seest' order by num" );
$relations_a[ylemus] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='alt' order by num" );
$relations_a[kolleeg] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='k6rvalt' order by num" );
$relations_a[alluv] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='ylalt' order by num" );
$relations_a = arrange_relations_a( $relations_a );
$current_rel_a = get_current_rel();
if( !$current_rel_a ) { header( "Location: http://ww.ee/360/?SID=$SID&ty=thankyou" ); die(); }
$current_rel = $current_rel_a[0];
$current_rel_status = $current_rel_a[1];
$hinnatav_nimi = $current_rel_a[2];
$rel_table_str = get_relations_table( $relations_a, $current_rel );

$vastused_a = get_vastused_a( $current_rel );
//**/precho( $kysimustik );
//**/precho( $relations_a );
//**/precho( $current_rel_a );
//**/precho( $vastused_a );

/******************** output starts here *******************/
include( "head.html" );
echo( "<body>\n" );
include( "head_hindaja$lang$kysitlus.php" );

flush();
echo( "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\" width=\"725\">\n" );
echo_list_kysimused( $kysitlus, $kysimustik, $plokk );
if( $current_rel_status == "1/2" )
 echo( " <tr><td align=\"center\" colspan=\"2\"><a href=\"$SIDSTR&valmis=$current_rel\"><img src=\"./_img/valmis$lang.gif\" width=\"100\" height=\"18\" alt=\"valmis\" border=\"0\"></a></td></tr>\n" );
echo( "</table>\n" );

/******************* funcs *******************/
function reset_relations( $hindaja ) {
 SQL_update_row( "relations", "status='1/2'", "kes=$hindaja", false );
 return;
}

function salvesta_tekst( $rel, $kys, $tekst ) {
 if( !$rel ) return;
 global $kysitlus;
 $tekst_num = SQL_new_text( $tekst );
 if( !SQL_insert_row( "vastused", "kysitlus=$kysitlus, rel=$rel, kysimus=$kys, tekst_num=$tekst_num", false ) )
  SQL_update_row( "vastused", "tekst_num=$tekst_num", "rel=$rel AND kysimus=$kys", false );
 return;
}
function submit_vastus( $rel, $kys, $tekst, $pidev_x, $eioska ) {
 if( !$rel ) return;
 global $kysitlus;
 if( $eioska ) {
  $tekst_num = SQL_new_text( $tekst );
  if( !SQL_insert_row( "vastused", "kysitlus=$kysitlus, rel=$rel, kysimus=$kys, vastus=-2, tekst_num=$tekst_num", false ) )
   SQL_update_row( "vastused", "vastus=-2, tekst_num=$tekst_num", "rel=$rel AND kysimus=$kys", false );
  return;
 }
 $tekst_num = SQL_new_text( $tekst );
 if( !SQL_insert_row( "vastused", "kysitlus=$kysitlus, rel=$rel, kysimus=$kys, vastus=$pidev_x, tekst_num=$tekst_num", false ) )
  SQL_update_row( "vastused", "vastus=$pidev_x, tekst_num=$tekst_num", "rel=$rel AND kysimus=$kys", false );
}
function vastus_count( $rel ) {
 $row = SQL_select_row( "count(*) as count", "vastused", "rel=$rel", false );
 return $row[count];
}

function get_vastused_a( $current_rel ) {
 $table = SQL_select_table( "kysimus", "vastus, texts.text as tekst", "vastused left join texts on texts.num=tekst_num", "rel=$current_rel", false );
 return $table;
}

function echo_list_kysimused( $kysitlus, $kysimused_a, $plokk = false ) {
 if( !$plokk AND !$kysimused_a ) return;
 global $SIDSTR;
 global $lang;
 global $current_rel_status;
 global $current_rel;
 global $hinnatav_nimi;
 global $vastused_a;
 if( $plokk ) $scan_a[$plokk] = $kysimused_a[$plokk]; else $scan_a = $kysimused_a;
//**/ precho( $scan_a );
 foreach( $scan_a as $plokk_key => $plokk_kys_a ) {
  if( !$plokk ) $ret_str .= "<tr><td colspan=\"2\" class=\"blok\">$plokk_kys_a[plokk]</td></tr>\n";
//**/ precho( $scan_a );
  foreach( $plokk_kys_a as $kys_num => $row ) {
   if( $kys_num == "plokk" ) continue;
//**/   if( $current_rel_status == "1/2" )
//**/    $mark = "";
//**/   else
/**/    $mark = "<a name=\"#mark$kys_num\"></a>";
   $ret_str .= " <tr><td colspan=\"2\" class=\"plain\">$mark&nbsp;</td></tr>\n";
   $ret_str .= " <form method=\"post\" action=\"$SIDSTR#mark$kys_num\">\n";
   $ret_str .= " <input type=\"hidden\" name=\"rel\" value=\"$current_rel\">\n";
   $ret_str .= " <input type=\"hidden\" name=\"kys\" value=\"$kys_num\">\n";
   $ret_str .= " <tr>\n";
   if( isset( $vastused_a[$kys_num] ) ) $class = "kysimus-vastatud"; else $class = "kysimus-vastamata";
   $ret_str .= "  <td class=\"$class\" align=\"right\" width=\"25\">$kys_num.</td>\n";
   $ret_str .= "  <td class=\"$class\" width=\"700\">$hinnatav_nimi - $row[kysimus]</td>\n";
   $ret_str .= " </tr>\n";
   $ret_str .= " <tr><td colspan=\"2\"><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">\n";
   if( ( $vastused_a[$kys_num][vastus] >= 0 ) AND ( $vastused_a[$kys_num] ) )
    $loc = "&loc=" . $vastused_a[$kys_num][vastus];
   else
    $loc = "";
   $ret_str .= "    <tr><td colspan=\"2\"><img src=\"./_img/minus.gif\" width=\"18\" height=\"18\" alt=\"minus\"><input type=\"image\" name=\"skaala$kys_num\" title=\"vastan\" src=\"_img/?w=690&h=18&c1=ffaaaa&c2=aaffaa$loc\" width=\"690\" height=\"18\" name=\"pidev\"><img src=\"./_img/plus.gif\" width=\"18\" height=\"18\" alt=\"plus\"></td></tr>\n";
   $ret_str .= "    <tr><td colspan=\"2\" align=\"center\"><img src=\"./_img/hea-halb$lang.gif\" width=\"690\" height=\"18\" alt=\"hea-halb\"></td></tr>\n";
   $ret_str .= "    <tr><td width=\"659\" rowspan=\"2\"><textarea class=\"text100\" name=\"tekst\" rows=\"4\">" . $vastused_a[$kys_num][tekst] . "</textarea></td>\n";
   if( $vastused_a[$kys_num][vastus] == -2 ) $eioskagif = "ei-oska$lang-r.gif"; else $eioskagif = "ei-oska$lang.gif";
   $salvestagif = "salvesta-tekst$lang.gif";
   $ret_str .= "        <td class=\"plain\" width=\"106\" align=\"right\" valign=\"top\"><input type=\"image\" title=\"ei oska vastata\" name=\"eioska\" src=\"./_img/$eioskagif\" width=\"100\" height=\"18\" border=\"0\"></td></tr>\n";
   $ret_str .= "    <tr><td class=\"plain\" width=\"106\" align=\"right\" valign=\"bottom\"><input type=\"image\" title=\"salvesta tekst\" name=\"salvestatekst\" src=\"./_img/$salvestagif\" width=\"100\" height=\"18\" border=\"0\"></td></tr>\n";
   $ret_str .= " </table></td></tr>\n";
   $ret_str .= " </form>\n";
  }
  /**/echo $ret_str; flush(); unset( $ret_str );
 }
//**/ return $ret_str;
}


function arrange_plokid_a( $plokid_a ) {
 if( !$plokid_a ) return;
 foreach( $plokid_a as $key => $val ) {
  $ret_a[$key] = $val[title];
 }
 return $ret_a;
}

function compile_kysimused( $kysitlus, $plokid_a ) {
 global $lang;
 if( $lang == "eng" )
 foreach( $plokid_a as $key => $val ) $kys[$key][plokk] = $val;
 if( $lang == "eng" )
  $rs = mysql_query( "select kys_num,kys_eng as kysimus,vastused,komment,plokk from kysimused where kysitlus='$kysitlus' order by kys_num" );
 else
  $rs = mysql_query( "select kys_num,kysimus,vastused,komment,plokk from kysimused where kysitlus='$kysitlus' order by kys_num" );
 while( $row = mysql_fetch_assoc( $rs ) ) $kys[$row[plokk]][$row[kys_num]] = $row;
 return $kys;
}

function get_current_rel() {
 global $relations_a;
 global $kysimustik;
 $kysimustik_count = 1;
 if( $kysimustik ) {
  foreach( $kysimustik as $plokk => $array ) {
   $kysimustik_count += sizeof( $array ) - 1;
  }
 }
 foreach( $relations_a as $kuidas => $rel_row ) {
  foreach( $rel_row as $rel => $row ) {
   if( $row[status] == "1" ) continue;
   if( $row[status] == "-" AND $kysimustik_count == vastus_count( $rel ) ) {
    SQL_update_row( "relations", "status='1/2'", "num=$rel", false );
    $relations_a[$kuidas][$rel][status] = "1/2";
   }
   $temp_a = $relations_a[$kuidas][$rel];
   return array( $rel, $relations_a[$kuidas][$rel][status], current( $temp_a ) );
  }
 }
 return false;
}

function get_relations_table( $relations_a, $current_rel ) {
 global $SIDSTR;
 global $current_rel_status;
 global $lang;
 $ret_str .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
 $class = "hinnatav0";
 foreach( $relations_a as $kuidas => $rel_row ) {
  foreach( $rel_row as $rel => $row ) {
   $keys_a = array_keys( $row );
   if( $rel == $current_rel ) $class = "hinnatav1";
   $ret_str .= " <tr><td class=\"$class\"><nobr>&nbsp; " . $row[$keys_a[0]] . " &nbsp;</nobr></td></tr>\n";
   if( $rel == $current_rel ) $class = "hinnatav2";
  }
  $ret_str .= "";
 }
 if( $current_rel_status == "1/2" )
  $ret_str .= " <tr><td align=\"center\" height=\"26\"><a href=\"$SIDSTR&valmis=$current_rel\"><img src=\"./_img/valmis$lang.gif\" width=\"100\" height=\"18\" alt=\"valmis\" border=\"0\"></a></td></tr>\n";
 $ret_str .= "</table>\n";
 return $ret_str;
}

function arrange_relations_a( $in_a ) {
 foreach( $in_a as $kuidas => $row ) {
  if( $row ) {
   foreach( $row as $rel_num => $row ) {
    $name_a = SQL_select_row( "name", "user_data", "num=$row[keda]" );
    $out_a[$kuidas][$rel_num][$row[keda]] = $name_a[name];
    $out_a[$kuidas][$rel_num][status] = $row[status];
   }
  }
 }
 return $out_a;
}

function mark_valmis( $valmis ) {
 if( !$valmis ) return;
 SQL_update_row( "relations", "status='1'", "num=$valmis", false );
}
?>


<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-4583751-1");
pageTracker._initData();
pageTracker._trackPageview();
</script>


</body>
