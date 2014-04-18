<?
include( "sql_access.php" );
include( "global.php" );

/*
echo('<pre>');
echo('GET-');
print_r($_GET);
echo('POST-');
print_r($_POST);
echo('REQUEST-');
print_r($_REQUEST);
echo('</pre>');
*/


$swap_user_rank = $_GET['swap_user_rank'];
$m = $_GET['m'];
$SID = $_GET['SID'];
$kysitlus = $_GET['kysitlus'];

$rem_user = $_GET['rem_user'];
$rem_rel = $_GET['rem_rel'];

$new_user = $_POST['new_user'];
$add_rel = $_POST['add_rel'];

$user_data = handshake( $SID );
if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' ) die( "forbidden02" );
include( "head.html" );
?>
<body bgcolor="#dddddd" onLoad=\"document.forms[0].elements[0].focus();\">
<?
if( !$kysitlus ) die( "</body></html>" );
/**/create_new_user( $new_user, $kysitlus );
/**/rem_user( $rem_user );
/**/swap_user_rank( $swap_user_rank );
add_rel( $add_rel );
rem_rel( $rem_rel );
$relations = get_relations( $kysitlus );
$kes_relations = get_kes_relations( $kysitlus );
//**/precho( $relations );
//**/precho( $kes_relations );

$SIDSTR = "./l3.osalejad.php?SID=$SID&kysitlus=$kysitlus";
if( $m ) $SIDSTR .= "&m=$m";
$SIDSTRnM = "./l3.osalejad.php?SID=$SID&kysitlus=$kysitlus";
$osalejad = SQL_select_table( "num", "name, email, rank, language", "user_data", "kysitlus=$kysitlus AND ( rank='h' OR rank='hh' ) order by name", false );

echo( "<table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\" border=\"0\">\n" );
echo( " <tr><td class=\"title\" colspan=\"3\">Lisa uus hindaja</td></tr>\n" );
echo( " <tr><td class=\"lead\">Nimi</td><td class=\"lead\">E-mail</td><td width=\"66\">&nbsp;</td></tr>\n" );
echo( " <form method=\"post\" name=\"lisaForm\" action=\"$SIDSTR#mark\"><tr>\n" );
echo( "  <td class=\"plain\"><input type=\"text\" name=\"new_user[name]\" class=\"inp100\" TABINDEX=\"1\"></td>\n" );
echo( "  <td class=\"plain\"><input type=\"text\" name=\"new_user[email]\" class=\"inp100\"></td>\n" );
echo( "  <td class=\"plain\" width=\"66\"><input type=\"submit\" name=\"new_user[submit]\" value=\"[ lisa ]\" class=\"subbut\"></td>\n" );
echo( " </tr></form>\n" );
echo list_users( $osalejad, "hh" );
echo list_users( $osalejad, "h" );
echo( "</table>\n" );
echo( "<br><div class=\"plain\">Täidetud ankeete " . $total_score[ok] . " / Täitmata ankeete " . $total_score[nok] . "</div>\n" );
echo( "<div class=\"plain\">100% " . count_status( " -OK- " ) . " / Poolikuid " . count_status( " -1/2- " ) . " / Puudujaid " . count_status( " --- " ) . "</div>\n" );
?>
</body>
</html>

<?

/*************** funcs *****************/
function count_status( $status )
{
	global $kes_relations;
	
	if ( ! is_array( $kes_relations ) )
	{
		return 0;
	}

	foreach( $kes_relations as $kes )
	{
		if( $kes[status] == $status )
		{
			$ret_int++;
		}
	}
	return $ret_int;
}

function get_kes_relations( $kysitlus ) {
 global $total_score;
 $sql = "select kes,keda,relations.status as status from relations left join user_data as u1 on kes=u1.num left join user_data as u2 on keda=u2.num where relations.kysitlus=$kysitlus order by kes";
 $rs = mysql_query( $sql );
 while( $row = mysql_fetch_assoc( $rs ) ) {
  if( $row[status] != '-' ) {
   if( !isset( $relations[$row[kes]][status] ) ) $relations[$row[kes]][status] = " -OK- ";
   elseif( $relations[$row[kes]][status] != " -OK- " ) $relations[$row[kes]][status] = " -1/2- ";
   $row[status] = 'OK';
   $total_score[ok] ++;
  } else {
   if( !isset( $relations[$row[kes]][status] ) ) $relations[$row[kes]][status] = " --- ";
   elseif( $relations[$row[kes]][status] != " --- " ) $relations[$row[kes]][status] = " -1/2- ";
   $total_score[nok] ++;
  }
  $relations[$row[kes]][$row[keda]] = $row[status];
 }
 return $relations;
}
function get_relations( $kysitlus ) {
 $sql = "select u1.name as kesname, u2.name as kedaname,kuidas,kes,keda,relations.status as status from relations left join user_data as u1 on kes=u1.num left join user_data as u2 on keda=u2.num where relations.kysitlus=$kysitlus";
 $rs = mysql_query( $sql );
 while( $row = mysql_fetch_assoc( $rs ) ) {
  $relations[$row[keda]][$row[kes]][keda] = $row[kedaname];
  $relations[$row[keda]][$row[kes]][kes] = $row[kesname];
  $relations[$row[keda]][$row[kes]][kuidas] = $row[kuidas];
  $relations[$row[keda]][$row[kes]][status] = $row[status];
 }
 return $relations;
}
function create_new_user( $new_user, $kysitlus ) {
 if( !$new_user ) return;
 $name = addslashes( $new_user[name] );
 $email = addslashes( $new_user[email] );
 mt_srand( (double) microtime() * 1000000 );
 $ID = md5( mt_rand(0,9999999) );
 SQL_insert_row( "user_data", "ID='$ID', kysitlus=$kysitlus, name='$name', email='$email'", false );
}

function list_users( $users_a, $rank ) {
 if( !$users_a ) return;
 global $SIDSTR;
 global $SIDSTRnM;
 global $kes_relations;
 global $relations;
 global $m;
//**/ precho( $users_a );
 foreach( $users_a as $num => $row ) {
  if( $row[rank] != $rank ) continue;
  if( $m == $num ) $mark = "<a name=\"mark\"></a>"; else $mark = "";
  if( $bgcolor == "#ddddff" ) $bgcolor = "#ffffdd"; else $bgcolor = "#ddddff";
  $ret_str .= " <tr bgcolor=\"$bgcolor\">\n";
  $ret_str .= "  <td class=\"lead\">$mark" . swap_rank_str( $num, $rank ) . "\n";
		$ret_str .= "   | <b><big><a href=\"$SIDSTRnM&m=$num#mark\">$row[name]</a></big></b> [" 
		          . ( is_array( $kes_relations[$num] ) ? implode( "][", $kes_relations[$num] ) : '' ) 
			  . "] <b><big>" 
			  . $row[language] 
			  . "</big></b></td>\n";
  $ret_str .= "  <td class=\"plain\"><b>$row[email]</b></td>\n";
  $ret_str .= "  <td class=\"plain\" width=\"66\"><a href=\"$SIDSTR&rem_user=$num#mark\">[! kustuta !]</a></td>\n";
  $ret_str .= " </tr>\n";
  if( $rank == "hh" ) {
   $ret_str .= " <tr bgcolor=\"$bgcolor\">\n  <td colspan=\"4\">\n" . show_hindajad( $num, $relations[$num], $users_a ) . "  </td>\n </tr>";
  }
 }
 if( !$ret_str ) return;
 if( $rank == "h" ) $rank_str = "(H)indaja"; elseif( $rank == "hh" ) $rank_str = "(H)indaja + (H)innatav";
 $ret_str = " <tr><td class=\"title\" colspan=\"4\">$rank_str</td></tr>\n" . $ret_str;
 return $ret_str;
}

function show_hindajad( $keda_num, $user_rel, $users_a ) {
 global $SIDSTR;
 global $m;
 if( $user_rel )
 foreach( $user_rel as $num => $row ) {
  if( $row[status] == "1" ) $class = " class=\"hinnatav0\""; else $class = "";
  $rel_a[$row[kuidas]][$num] = "<a href=\"$SIDSTR&rem_rel[kes]=$num&rem_rel[keda]=$keda_num#mark\">[X]</a> <span$class>" . $row[kes] . " [" . $row[status] . "]</span>";
 }
 if( $rel_a )
 foreach( $rel_a as $kuidas => $list ) {
  $rel[$kuidas] = implode( "<br>", $list );
 }
//**/ precho( $rel_a );
 if( $keda_num != $m ) return $ret_str;
 $ret_str .= "   <table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\" border=\"1\">\n";
 $ret_str .= "    <tr>\n";
// $ret_str .= "     <td class=\"plain\" valign=\"bottom\"><b>Teda hindavad</b>:</td>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"bottom\"><b>Alt</b>:<br>$rel[alt]" . add_rel_form( $keda_num, $users_a, "alt" ) . "</td>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"bottom\"><b>Kõrvalt</b>:<br>$rel[k6rvalt]" . add_rel_form( $keda_num, $users_a, "k6rvalt" ) . "</td>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"bottom\"><b>Ülalt</b>:<br>$rel[ylalt]" . add_rel_form( $keda_num, $users_a, "ylalt" ) . "</td>\n";
 $ret_str .= "    </tr>\n";
 $ret_str .= "   </table>\n";
 return $ret_str;
}

function add_rel_form( $keda, $users_a, $kuidas ) {
 if( !$users_a ) return;
 global $m;
 if( $keda != $m ) return;
 global $SIDSTR;
 $ret_str .= "\n<table width=\"100%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">\n";
 $ret_str .= " <form method=\"post\" action=\"$SIDSTR#mark\">\n";
 $ret_str .= "  <tr>\n";
 $ret_str .= "   <td width=\"100%\">\n";
 $ret_str .= "    <input type=\"hidden\" name=\"add_rel[keda]\" value=\"$keda\">\n";
 $ret_str .= "    <input type=\"hidden\" name=\"add_rel[kuidas]\" value=\"$kuidas\">\n";
 $ret_str .= "    <select name=\"add_rel[kes]\" size=\"1\" class=\"inp100\">\n";
 foreach( $users_a as $num => $row ) {
  $ret_str .= "     <option value=\"$num\">$row[name]</option>\n";
 }
 $ret_str .= "    </select>\n";
 $ret_str .= "   </td>\n";
 $ret_str .= "   <td>\n";
 $ret_str .= "    <input type=\"submit\" value=\"[ lisa ]\" class=\"subbut\">\n";
 $ret_str .= "   </td>\n";
 $ret_str .= "  </tr>\n";
 $ret_str .= " </form>\n";
 $ret_str .= "</table>\n";
 return $ret_str;
}

function add_rel( $rel ) {
 global $kysitlus;
 if( !$rel ) return;
 SQL_insert_row( "relations", "kysitlus=$kysitlus, kes=$rel[kes], keda=$rel[keda], kuidas='$rel[kuidas]'", false );
}
function rem_rel( $rel ) {
 global $kysitlus;
 if( !$rel ) return;
 SQL_delete_row( "relations", "kysitlus=$kysitlus AND kes=$rel[kes] AND keda=$rel[keda]" );
}

function swap_rank_str( $num, $rank ) {
 global $SIDSTR;
 if( $rank == "h" ) $rank_str = "<a href=\"$SIDSTR&swap_user_rank=$num#mark\">H -> H+H</a>";
 elseif( $rank == "hh" ) $rank_str = "<a href=\"$SIDSTR&swap_user_rank=$num#mark\">H+H -> H</a>";
 return $rank_str;
}

function swap_user_rank( $num ) {
 global $kysitlus;
 if( !$num ) return;
 SQL_update_field( "user_data", "rank=5-rank", "num=$num" );
 if( SQL_delete_row( "relations", "kes=$num AND keda=$num" ) == 0 )
  SQL_insert_row( "relations", "kysitlus=$kysitlus, kes=$num, keda=$num", false );
 return;
}

function rem_user( $num ) {
 if( !$num ) return;
 SQL_delete_row( "user_data", "num=$num" );
 SQL_delete_row( "relations", "kes=$num OR keda=$num" );
}
