<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];

$user_data = handshake( $SID );

if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' && $user_data[rank] !== '1' ) die( "forbidden02" );
include( "head.html" );
?>
<body bgcolor="#dddddd">
<?

$kysitlus = $_GET['kysitlus'];

if( !$kysitlus ) die( "FOO</body></html>" );
echo kys_title_update( $kysitlus, $kystitle );
$kys_params = get_kysparams( $kysitlus );
/**/send_invitations( $invite );
generate_report( $textgen );

$row = SQL_select_row( "title, status, unix_timestamp(muudetud) as timestamp", "kysitlused", "num=$kysitlus+0", false );
echo( "<table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\" border=\"0\">\n" );
echo( " <tr><td class=\"title\" colspan=\"2\">$row[title]</td></tr>\n" );
echo( " <tr><td class=\"plain\" colspan=\"2\">" . single_text_form( "Nimetus:", "./l2.php?SID=$SID&kysitlus=$kysitlus", "kystitle", "[ muuda ]", $row[title] ) . "</td></tr>\n" );

echo( " <tr><td class=\"plain\">Staatus : </td><td class=\"plain\">$row[status]</td></tr>\n" );

echo( " <tr><td class=\"plain\">Muudetud : </td><td class=\"plain\">" . timestamp_format( $row[timestamp] ) . "</td></tr>\n" );

if( $user_data[rank] === '0' )
{
	echo( " <tr><td class=\"plain\"><a href=\"./hindajad.xml/?kysitlus=$kysitlus\" target=\"360_l3\">XML</a>:</td><td class=\"plain\">$kys_params[plokke] / $kys_params[kysimusi]</td></tr>\n" );

	echo( " <tr><td class=\"plain\"><a href=\"./l3.kysimused.php?SID=$SID&kysitlus=$kysitlus\" target=\"360_l3\">Plokke / Kysimusi</a>:</td><td class=\"plain\">$kys_params[plokke] / $kys_params[kysimusi]</td></tr>\n" );

	echo( " <tr><td class=\"plain\"><a href=\"./l3.osalejad.php?SID=$SID&kysitlus=$kysitlus\" target=\"360_l3\">Osalejaid</a>:</td><td class=\"plain\">h - $kys_params[h]; hh - $kys_params[hh]</td></tr>\n" );

	echo( " <tr><td class=\"plain\"><a href=\"./l3.osalusraport.php?SID=$SID&kysitlus=$kysitlus\" target=\"360_l3\">Osalusraport</a></td></tr>\n" );

	echo( " <tr><td class=\"plain\"><a href=\"./hindajad.xml/?kysitlus=$kysitlus&event=saadaKutsed\" target=\"360_l3\">Saada kutsed</a></td></tr>\n" );
}

echo( " <tr><td class=\"plain\"><a href=\"./l3.aruanded.php?SID=$SID&kysitlus=$kysitlus\" target=\"360_l3\">ARUANDED</a></td></tr>\n" );

echo( " <tr><td class=\"plain\"><a href=\"./l3.tulemused.php?SID=$SID&kysitlus=$kysitlus\" target=\"360_l3\">TULEMUSED</a></td></tr>\n" );
//**/echo( " <tr><td class=\"plain\"><a href=\"./l2.php?SID=$SID&kysitlus=$kysitlus&textgen=true\">genereeri RTF</a></td></tr>\n" );

echo list_report_files( $kysitlus );

echo( "</table>\n" );
//**/precho( $row);

/*************** funcs *****************/

function generate_report( $textgen ) {
 global $kysitlus;
 $RTF = "{\\rtf1{\\stylesheet{\\s0 persoon;}{\\s1 kysimus;}{\\s2 vastus;}}\n\n";
 $rs = mysql_query(
 "
select
 ud1.name as KESname, ud2.name as KEDAname,
 concat( vas.kysimus, \".\\t\", k.kysimus ) as kysimus,
 replace(t.text,\"\\r\\n\",\"<NL>\") as tekstivastus
from relations as rel
left join vastused as vas on rel.num=vas.rel
left join user_data as ud1 on rel.kes=ud1.num
left join user_data as ud2 on rel.keda=ud2.num
left join texts as t on vas.tekst_num=t.num
left join kysimused as k on vas.kysimus = k.kys_num and k.kysitlus=rel.kysitlus
where rel.kysitlus=$kysitlus                      -- AND vas.kysimus <= 26
order by ud2.name, lpad(vas.kysimus,3,\"0\")       , tekstivastus
" );
 while( $row = mysql_fetch_assoc( $rs ) ) {
  $vastus = $row[tekstivastus];
  if( strlen( $vastus ) == 0 ) continue;
  if( $keda_name != $row[KEDAname] ) {
   $keda_name = $row[KEDAname];
   $RTF .= "\\s0\n{" . $keda_name . "\n\\par}\n";
  }
  if( $kysimus != $row[kysimus] ) {
   $kysimus = $row[kysimus];
   $RTF .= "\\s1\n{" . implode( $keda_name, explode( "[XXX]", $kysimus ) ) . "\n\\par}\n";
  }
  if( $keda_name == $row[KESname] ) $italic = "\\i "; else $italic = "";
  $RTF .= "\\s2" . "\n{" . $italic . $vastus . "\n\\par}\n";
 }
 $RTF .= "}";
 $fp = fopen( "./_docs/" . $kysitlus . "_text_report.rtf", "w" );
 fwrite( $fp, $RTF );
 fclose( $fp );
}

function list_report_files( $kysitlus ) {
 if( $handle = opendir( "./_docs/" ) ) {
  while( false !== ( $file = readdir( $handle ) ) ) {
   $split = explode( "_", $file );
   if( $split[0] == $kysitlus ) {
    echo( " <tr><td class=\"plain\" bgcolor=\"#ffffff\" colspan=\"2\"><a href=\"./_docs/$file\">$file</a></td></tr>\n" );
   }
  }
 closedir( $handle );
 }
}

function kys_title_update( $num, $title ) {
 global $SID;
 if( !$title ) return;
 $title = addslashes( $title );
 SQL_update_field( "kysitlused", "title='$title'", "num=$num", false );
//**/ return "<script>parent.360_l1.location = \"l1.php?SID=$SID\";</script>\n";
}

function get_kysparams( $num ) {
 if( !$num ) return false;
 $row = SQL_select_row( "count(distinct plokk) as count", "blob_plokid", "kysitlus=$num", false );
 $ret_a[plokke] = $row[count];
 $row = SQL_select_row( "count(kysitlus) as count", "kysimused", "kysitlus=$num", false );
 $ret_a[kysimusi] = $row[count];
 $row = SQL_select_row( "count(rank) as count", "user_data", "kysitlus=$num AND rank='h'", false );
 $ret_a[h] = $row[count];
 $row = SQL_select_row( "count(rank) as count", "user_data", "kysitlus=$num AND rank='hh'", false );
 $ret_a[hh] = $row[count];
 return $ret_a;
}

function send_invitations( $invite ) {
 if( !$invite[kysitlus] ) return;
 $kysitlus = $invite[kysitlus];

// $kutsekeel = '';
 $kutsekeel = '';

 $user_a = SQL_select_table( "num", "name,email,ID,language", "user_data", "kysitlus='$kysitlus' and language='$kutsekeel' order by name", false );
 $sql = "select kes from relations where kysitlus=$kysitlus and status='-' order by num";
 $rs = mysql_query( $sql );
 while( $row = mysql_fetch_assoc( $rs ) ) {
  $ready[] = $row[kes];
 }

//**/precho( $ready );
//**/precho( $user_a );
 echo "<pre>\n";
 foreach( $user_a as $num => $row ) {
//  if( !in_array( $num, $ready ) ) continue;
  echo( ++$count . ". " . $row[name] . "  -  " . $row[email] );
  flush();
//**/  invite_user( $row[name], $row[email], $row[ID], $row[language] );
  echo( "  -  kutsutud.\n" );
  flush();
 }
 echo "</pre>\n";
//**/ invite_user( "Mare Pork", "mpork@hot.ee", "XXXXX[ID_on_siin]XXXXX" );
//**/ invite_user( "Carl Magnus Stenberg", "7a1a6e09fd7fdffa73a3d8efd000ecaf", "carl-magnus.stenberg@tele2.lt", "eng" );
//**/ invite_user( "Mats Tilly", "mats.tilly@tele2.ee", "ee314c3c35aaf5c4264e2bd3e96b816a", "eng" );
//**/ invite_user( "Gundega Kanepe", "gundega.kanepe@tele2.lv", "8a30f8e66b536cc15920a25baf9a3988", "eng" );
//**/ invite_user( "Kristiina Tukk", "kristiina.tukk@tele2.ee", "942827cca29f80cf436d3dda38b4f252", "" );
//**/ invite_user( "Jaana Aduson", "jaana.aduson@tele2.ee", "5d1e9903bb51c366fbd59b0c5907bbed", "" );
//**/ invite_user( "Raivo Bergman", "raivo.bergman@tele2.ee", "3658f7d326d3630676efd6947a08c221", "" );
//**/ invite_user( "Margarita Saal", "tcg.linnamae@tele2.ee", "01f893b411def3f42d6e8ba95bf0cacf", "" );

//**/ invite_user( "Mare Pork", "mpork@hot.ee", "XXXXX[ID_on_siin]XXXXX" );
/**/ invite_user( "Michelek", "Mihkel@ww.ee", "XXXXX[ID_on_siin]XXXXX", "" );
}

function invite_user( $nimi, $email, $ID, $lang ) {
 if( $lang == "" ) $lang = "est";
 $subj = "$nimi - Uuringu 360 kutse";
 $body[est] = "
<html>
<head>
 <title>360</title>
 <link rel=\"stylesheet\" href=\"http://ww.ee/360/mp.css\" type=\"text/css\">
</head>
<body>
<div class=\"title\">Lp. $nimi</div>
<br>
<div class=\"lead\">
Meie ühine soov on oma organisatsiooni pidevalt arendada selleks, et kindlustada parim tulemus. Meie väärtushinnangutes on selgelt öeldud,
et meie organisatsiooni juhid peavad alati olema eeskujuks ja edendama meeskonna vaimu.
</div>
<div class=\"lead\">
Palun leidke aeg ja andke oma hinnangud ettevõtte juhtide kohta.
</div>
<div class=\"lead\">
Küsitluse kestuseks on üks nädal, seega arvesse lähevad vastused, mis laekuvad enne (10:00) järgmist teisipäeva, s.o. 18. veebruar.
</div>
<div class=\"lead\">
Iga töötaja panus on vajalik; mida rohkem vastajaid, seda paremaid üldistusi saame teha ja õigemaid otsuseid vastu võtta.
</div>
<div class=\"lead\">
Uuringus osalemiseks 
<a href=\"http://ww.ee/360/?ID=$ID\">http://ww.ee/360/?ID=$ID</a>.
</div>
<div class=\"lead\"><br>Jõudu tööle!</div>
<hr>
<div class=\"plain\">Sisulised konsultatsioonid:<br>
Mare Pork<br>
e-mail: <a href=\"mailto:mpork@hot.ee\">mpork@hot.ee</a><br>
GSM: 050 11 438
</div>
<div class=\"plain\">Tehnilist laadi probleemid/küsimused/ettepanekud:<br>
Mihkel Putrinsh<br>
e-mail: <a href=\"mailto:michelek@ut.ee\">michelek@ut.ee</a><br>
GSM: 053 937 407
</div>
</body>
</html>
";

 $body[eng] = "
<html>
<head>
 <title>360</title>
 <link rel=\"stylesheet\" href=\"http://ww.ee/360/mp.css\" type=\"text/css\">
</head>
<body>
<div class=\"title\">Dear $nimi.</div>
<br>
<div class=\"lead\">Our common task is to develop our organization to have the best possible environment for reaching our goals.</div>
<div class=\"lead\">Our corporate values clearly state the role of management in modelling and supporting team spirit.</div>
<div class=\"lead\">Please take your time to give feedback to your managers about their managerial competencies.</div>
<div class=\"lead\">We have planned a week for answering. Deadline is on TUESDAY Feb 18, at 10.00 a.m.</div>
<div class=\"lead\">YOUR INPUT IS ABSOLUTELY NECESSARY - the more we get answers the better will be assessment and conclusions to be made.</div>
<div class=\"lead\">GOOD LUCK</div>
<br>
<div class=\"plain\">We are sorry, if previous invitation was in Estonian.<br>
Also we kindly ask You to use Internet Explorer as the environment might not be stable in other browsers.<br></div>
<div class=\"lead\">Use the link
<a href=\"http://ww.ee/360/?ID=$ID\">http://ww.ee/360/?ID=$ID</a></div>
<div class=\"plain\">to participate in our 360&#176; feedback of managerial competencies.</div>
<br>
<div class=\"plain\">Wishing patience</div>
<div class=\"plain\">Project Manager</div>
<div class=\"plain\"><a href=\"mailto:mpork@hot.ee\">Mare Pork</a>, Ph.D.</div>
</body>
</html>
";

$headers .= "From: Mare Pork<mpork@hot.ee>\n";
$headers .= "X-Sender: <mpork@hot.ee>\n";
$headers .= "X-Mailer: PHP\n"; //mailer
$headers .= "X-Priority: 3\n"; //1 UrgentMessage, 3 Normal
$headers .= "Return-Path: <michelek@ut.ee>\n";
$headers .= "Content-Type: text/html; charset=iso-8859-1\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Reply-To: mpork@hot.ee";

//**/ $email = "michelek@ut.ee";
  mail( $email, $subj, $body[$lang], $headers );
//  mail( $email, $subj, $body, $headers );
}


?>
</body>
</html>
