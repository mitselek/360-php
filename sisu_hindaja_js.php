<?
header('Content-type: text/html; charset="iso-8859-1"',true);

//die("TEHNILINE PAUS 2 TUNDI. KATSUGE ANDESTADA");
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$rel = $_POST['rel'];
$tekstid = $_POST['tekstid'];
$valmis = $_GET['valmis'];
$hindan = $_GET['hindan'];
$ty = $_GET['ty'];

//precho($_GET);
//precho($_POST);

$user_data = handshake( $SID );
if( $user_data === false ) die( "SmP: forbidden" );
//global $hid_ID_field;
$scale = 420;
//**/precho( $user_data );

$kysitlus = $user_data[kysitlus];
$hindaja = $user_data[num];
$hindaja_nimi = $user_data[name];
$SIDSTR = "./sisu_hindaja_js.php?SID=$SID";
$lang = $user_data[language];

$kasComplete = true;

if( $hindan == "uuesti" )
{
  SQL_update_row( "relations", "status='1/2'", "kes=$hindaja", false );
}

mark_valmis( $valmis );

$relations_a[ise] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='seest' order by num" );
$relations_a[ylemus] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='alt' order by num" );
$relations_a[kolleeg] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='k6rvalt' order by num" );
$relations_a[alluv] = SQL_select_table( "num", "keda,status", "relations", "kysitlus=$kysitlus AND kes=$hindaja AND kuidas='ylalt' order by num" );
$relations_a = arrange_relations_a( $relations_a );
$current_rel_a = get_current_rel();


$plokid_a = SQL_select_table( "plokk", "title$lang as title, rendered", "blob_plokid", "kysitlus=$kysitlus order by plokk", false );

if ($hinnatav_id)
{
	$kysimustik = compile_kysimused( $kysitlus, $plokid_a );
}

//**/precho( $plokid_a );

salvesta_tekstid( $rel, $tekstid );


//$vastus_count = vastus_count( $rel );
//if( check_form_completion( $plokid_a, $vastus_count );

//precho($hinnatav_id);
//precho($current_rel_a);
//precho($relations_a );


if( !$current_rel_a )
{
	header( "Location: http://ww.ee/360/?SID=$SID&ty=thankyou" );
	die('. .');
}


$current_rel = $current_rel_a[0];
$current_rel_status = $current_rel_a[1];
$hinnatav_nimi = $current_rel_a[2];
$hinnatav_sex = $current_rel_a[3];

$vastused_a = get_vastused_a( $current_rel );

$kysimused_HTML = get_kysimused_HTML( $kysitlus, $kysimustik, $plokid_a, $plokk );

$rel_table_str = get_relations_table( $relations_a, $current_rel );

//**/precho( $plokid_a );
//**/precho( $kysimustik );
//**/precho( $relations_a );
//**/precho( $current_rel_a );
//**/precho( $vastused_a );

/******************** output starts here *******************/
include( "head.html" );
echo( "<body>\n" );
if( is_file( "head_hindaja$lang$kysitlus.php" ) )
 include( "head_hindaja$lang$kysitlus.php" );
else
 echo( "Missing header for questionary $kysitlus, language $lang.<br>\n" );

flush();
echo( "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\" width=\"725\">\n" );
echo( "<form method=\"post\" name=\"mainform\" action=\"$SIDSTR#vastamata\">\n" );
echo( "<input type=\"hidden\" name=\"rel\" value=\"$current_rel\">\n" );
echo $kysimused_HTML;


echo( " <tr>\n" );
echo( "  <td align=\"right\" colspan=\"2\">\n" );
echo( "   <input type=\"image\" name=\"submit\" src=\"./_img/submit$lang.gif\" width=\"100\" height=\"18\" alt=\"next\" border=\"0\" align=\"right\">\n" );
echo( "  </td>\n" );
echo( " </tr>\n" );
echo( "</form>\n" );
echo( "</table>\n" );



/******************* funcs *******************/
function salvesta_tekstid( $rel, $tekstid ) {
 if( !$rel ) return;
 global $kysitlus;
 foreach( $tekstid as $kys => $tekst ) {
  if( strlen( $tekst ) < 3 ) continue;
  $tekst_num = SQL_new_text( $tekst );
  if( !SQL_insert_row( "vastused", "kysitlus='$kysitlus', rel='$rel', kysimus='$kys', tekst_num='$tekst_num'", false ) )
   SQL_update_row( "vastused", "tekst_num='$tekst_num'", "rel='$rel' AND kysimus='$kys'", false );
 }
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

function get_vastused_a( $current_rel ) {
 $table = SQL_select_table( "kysimus", "vastus, texts.text as tekst", "vastused left join texts on texts.num=tekst_num", "rel=$current_rel", false );
 return $table;
}

function get_kysimused_HTML( $kysitlus, $kysimused_a, $plokid_a, $plokk = false ) {

  if( !$kysimused_a ) return;

  global $SID;
  global $lang;
  global $current_rel_status;
  global $current_rel;
  global $hindaja_nimi;
  global $hinnatav_nimi;
  global $vastused_a;
  global $kasComplete;


  if( $plokk )
  {
    $scan_a[$plokk] = $kysimused_a[$plokk];
  }
  else
  {
    $scan_a = $kysimused_a;
  }

  foreach( $scan_a as $plokk_key => $plokk_kys_a )
  {
    //**/precho( $plokid_a[$plokk_key] );
    if( $plokid_a[$plokk_key][rendered] == 1 )
    {
      $title = $plokid_a[$plokk_key][title];
      if( strstr( $title, "[XXX]" ) )
      {
        $split_a = explode( "[XXX]", $title );
        $title = $split_a[0] . $hinnatav_nimi . $split_a[1];
      }
      $ret_str .= "
      <tr>
       <td colspan=\"2\" class=\"hinnatav1\" height=\"50\" valign=\"bottom\">".$title."</td>
      </tr>\n";
    }


    foreach( $plokk_kys_a as $kys_num => $row )
    {
      //**/precho( $row );
    
      if( $kys_num == "plokk" )
        continue;
   
      if( ( $vastused_a[$kys_num][vastus] >= 0 ) AND ( $vastused_a[$kys_num] ) )
      {
        $loc = "&loc=" . ( 689 - $vastused_a[$kys_num][vastus] );
      } else {
        $loc = "";
      }
   
      if( $vastused_a[$kys_num][vastus] == -2 )
      {
        $eioskagif = "ei-oska$lang-r.gif";
      } else {
        $eioskagif = "ei-oska$lang.gif";
      }
   
      if( strstr( $row[kysimus], "[XXX]" ) )
      {
        $split_a = explode( "[XXX]", $row[kysimus] );
        $kysimus_str = $split_a[0] . $hinnatav_nimi . $split_a[1];
      } else {
        $kysimus_str = $row[kysimus];
      }
      $kysimus_str = setsex( $kysimus_str );


      $vanatekstivastus_bool = false;
      unset( $vanatekstivastus_str );
      $sql = "
select txt.text as tekst
from texts as txt,
     vastused as vas,
		 relations as rel,
		 user_data as ud1,
		 user_data as ud2
where txt.num = vas.tekst_num
  and vas.kysimus = $kys_num
  and vas.kysitlus = $kysitlus
  and vas.rel = rel.num
  and rel.kes = ud1.num
  and rel.keda = ud2.num
  and ud1.name = '$hindaja_nimi'
  and ud2.name = '$hinnatav_nimi'";
/**/ $sqlsave = $sql;  
      $rs = mysql_query( $sql );
      if( @ mysql_num_rows( $rs ) )
      {
        $_row = mysql_fetch_assoc( $rs );
        $vanatekstivastus_str = $_row[tekst];
        $vanatekstivastus_bool = true;
      }
   
      $komment_bool = false;
      unset( $komment_str );
      $sql = "select komment$lang as komment from blob_komment where kysitlus=$kysitlus AND kysimus=$kys_num";
      $rs = mysql_query( $sql );
      if( @ mysql_num_rows( $rs ) )
      {
        $_row = mysql_fetch_assoc( $rs );
        $komment_str = setsex( $_row[komment] );
        $komment_bool = true;
      }



//**/        precho($vastused_a[$kys_num]);

      if (!isset($vastused_a[$kys_num]))
      {
        $class = "kysimus-vastamata";
        #$mark = "<a name=\"#vastamata\"/>";
        $mark = " id=vastamata";
        $kasComplete = false;
      }
      else if ($vastused_a[$kys_num]['vastus'] == -1)
      {
        $class = "kysimus-vastamata";
        $mark = "<a name=\"#vastamata\"/>";
        $mark = " id=vastamata";
        $kasComplete = false;
      }
/*
      else if (!isset($vastused_a[$kys_num][tekst]) && $vastused_a[$kys_num][vastus] >= 0)
      {
        $class = "kysimus-vastamata";
        $mark = "<a name=\"#vastamata\"></a>";
        $kasComplete = false;
      }
      else if (strlen($vastused_a[$kys_num][tekst]) < 5 && $vastused_a[$kys_num][vastus] == -2)
      {
        $class = "kysimus-vastamata";
        $mark = "<a name=\"#vastamata\"></a>";
        $kasComplete = false;
      }
      else if (strlen($vastused_a[$kys_num][tekst]) < 5 && $vastused_a[$kys_num][vastus] >= 0)
      {
        $class = "kysimus-vastamata";
        $mark = "<a name=\"#vastamata\"></a>";
        $kasComplete = false;
      }
*/
      else
      {
        $class = "kysimus-vastatud";
        $mark = "";
      }


      
      
      // TELE2 jaoks erilahendus
      if( $kysitlus == 14 )
      {
        $abitekst_str = $komment_str;// . $sqlsave;
        $komment_str = $vanatekstivastus_str;
      }
      //*********************** TELE2


      if( !$vastused_a[$kys_num][tekst] )
      {
        $vastused_a[$kys_num][tekst] = $komment_str;
      }

      $ret_str .= "\n <tr><td colspan=\"2\" class=\"plain\"$mark>&nbsp;</td></tr>\n <tr>\n  <td class=\"$class\" align=\"right\" valign=\"top\" width=\"25\">$kys_num.</td>\n  <td class=\"$class\" width=\"700\">$kysimus_str</td>\n </tr>\n";


      //**/precho ($row);
      if( $row[liik] == 'pidev' )
      {
        $ret_str .= "
        <tr>
         <td colspan=\"2\"><img
          src=\"./_img/plus.gif\" width=\"18\" height=\"18\" alt=\"minus\"><a
          target=\"ww_submit\" href=\"./360_hinnang.php?SID=$SID&rel=$current_rel&kys=$kys_num&lang=$lang&coords=\"><img
          ismap name=\"skaala$kys_num\" src=\"http://moos.ww.ee/360/_img/?w=690&h=18&c1=aaffaa&c2=ffaaaa$loc\" width=\"690\" height=\"18\" border=\"0\"></a><img
          src=\"./_img/minus.gif\" width=\"18\" height=\"18\" alt=\"plus\"><br>
         <img src=\"./_img/hea-halb$lang.gif\" width=\"732\" height=\"18\" alt=\"hea-halb\"></td>
        </tr>\n";
      }
      $ret_str .= "\n <tr>\n  <td colspan=\"2\">\n   <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">\n    <tr>\n     <td width=\"659\">";


      if( $abitekst_str )
      {
        $ret_str .= "\n      <div class=\"abitekst\">" . nl2br($abitekst_str) . "</div>";
      }
      $ret_str .= "\n      <textarea class=\"text100\" name=\"tekstid[$kys_num]\" rows=\"4\">" . $vastused_a[$kys_num][tekst] . "</textarea></td>\n";



      if( $row[liik] == 'pidev' )
      {
        $ret_str .= "\n     <td class=\"plain\" width=\"106\" align=\"right\" valign=\"top\"><a\n      target=\"ww_submit\" href=\"./360_hinnang.php?SID=$SID&rel=$current_rel&kys=$kys_num&lang=$lang&coords=?-2,0\"><img\n      name=\"eioska$kys_num\" id=\"eioska$kys_num\" src=\"./_img/$eioskagif\" width=\"100\" height=\"18\" border=\"0\"></a></td>\n";
      } else {
        $ret_str .= "\n     <td class=\"plain\" width=\"106\" align=\"right\" valign=\"top\"><img src=\"./_img/spacer.gif\"></td>\n";
      }
      $ret_str .= "\n    </tr>\n   </table>\n  </td>\n </tr>\n";
    }
  }
/**/ return $ret_str;
}

function setsex( $input ) {
 global $hinnatav_sex;
 if( !$hinnatav_sex ) return $input;
 if( $hinnatav_sex == "m" ) {
  $output = eregi_replace( "his/her", "his", $input );
  $output = eregi_replace( "himself/herself", "himself", $output );
 } elseif( $hinnatav_sex == "f" ) {
  $output = eregi_replace( "his/her", "her", $input );
  $output = eregi_replace( "himself/herself", "herself", $output );
 }
 return $output;
}

function arrange_plokid_a( $plokid_a ) {
 if( !$plokid_a ) return;
 foreach( $plokid_a as $key => $val ) {
  $ret_a[$key] = $val[title];
 }
 return $ret_a;
}


//----------------------------- compile_kysimused ---------------------------

function compile_kysimused($kysitlus, $plokid_a)
{
  global $lang;
  global $hinnatav_id;
// foreach( $plokid_a as $key => $val ) $kys[$key][plokk] = $val;
  $rs = mysql_query( "select id,kys_num,kysimus$lang as kysimus,vastused,komment,plokk,liik,required,special from kysimused where kysitlus='$kysitlus' order by lpad(kys_num,3,\"0\")" );
  
  while( $row = mysql_fetch_assoc( $rs ) )
  {
    if($row[special] == 1)
    {
      $kysimus_id = $row[id];
      $query = "select * from kysimus_hinnatav where fk_kysimus_id=$kysimus_id AND fk_user_num=$hinnatav_id";
      // echo $query;
      $rs2 = mysql_query( $query );
      if (mysql_num_rows($rs2) == 0)
      {
        continue;
      }
    }
    $kys[$row[plokk]][$row[kys_num]] = $row;
  }
 
  return $kys;
}




function get_current_rel()
{
 global $relations_a;
 global $kysitlus;
 global $kysimustik;
 global $hinnatav_id;
 
//**/ precho( $relations_a );
//**/ precho( $kysimustik );
/**/ $row = SQL_select_row( "size", "kysitlused", "num=$kysitlus", false );
/**/ $kysimustik_count = $row[size];
//**/ if( $kysimustik ) {
//**/  foreach( $kysimustik as $plokk => $array ) {
//**/   $kysimustik_count += sizeof( $array ) - 0;
//**/  }
//**/ }
  foreach( $relations_a as $kuidas => $rel_row )
  {
    foreach( $rel_row as $rel => $row )
    {
      if( $row[status] == "1" )
      {
        continue;
      }
//   precho( vastus_count( $rel ) );
//   precho( $kysimustik_count );
      if( $row[status] == "-" AND $kysimustik_count == vastus_count( $rel ) )
      {
//    echo( "ASDGFASDGV" );
        SQL_update_row( "relations", "status='1/2'", "num=$rel", false );
        $relations_a[$kuidas][$rel][status] = "1/2";
      }
      $temp_a = $relations_a[$kuidas][$rel];
      $hinnatav_id = key($temp_a);
      
      return array(
        $rel, 
        $relations_a[$kuidas][$rel][status], 
        $temp_a[$hinnatav_id], 
        $relations_a[$kuidas][$rel][sex]
        );
    }
  }
  return false;
}
  
function vastus_count( $rel ) {
 $row = SQL_select_row( "count(*) as count", "vastused", "rel=$rel", false );
 return $row[count];
}

function get_relations_table( $relations_a, $current_rel ) {
 global $SIDSTR;
 global $current_rel_status;
 global $lang;
 global $kasComplete;
 
 $ret_str .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
 foreach( $relations_a as $kuidas => $rel_row ) {
  foreach( $rel_row as $rel => $row ) {
   $keys_a = array_keys( $row );
   if( $rel == $current_rel ) {
    $class = "hinnatav1";
   } elseif( $row[status] == "1" ) {
    $class = "hinnatav0";
   } else {
    $class = "hinnatav2";
   }
   $ret_str .= " <tr><td class=\"$class\"><nobr>&nbsp; " . $row[$keys_a[0]] . " &nbsp;</nobr></td></tr>\n";
  }
  $ret_str .= "";
 }
// if( $current_rel_status == "1/2" )
 if( $kasComplete )
  $ret_str .= " <tr><td align=\"center\" height=\"26\"><a href=\"$SIDSTR&valmis=$current_rel\"><img src=\"./_img/next$lang.gif\" width=\"100\" height=\"18\" alt=\"next\" border=\"0\"></a></td></tr>\n";
 $ret_str .= "</table>\n";
 return $ret_str;
}

function arrange_relations_a( $in_a ) {
 foreach( $in_a as $kuidas => $row ) {
  if( $row ) {
   foreach( $row as $rel_num => $row ) {
    $name_a = SQL_select_row( "name, nick, sex", "user_data", "num=$row[keda]" );
    if( $name_a[nick] ) $name = $name_a[nick]; else $name = $name_a[name];
    $out_a[$kuidas][$rel_num][$row[keda]] = $name;
    $out_a[$kuidas][$rel_num][sex] = $name_a[sex];
    $out_a[$kuidas][$rel_num][status] = $row[status];
   }
  }
 }
//**/ precho( $out_a );
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
</html>
