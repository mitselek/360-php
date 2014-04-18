<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$kysitlus = $_GET['kysitlus'];


$user_data = handshake( $SID );
if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' ) die( "forbidden02" );
include( "head.html" );
?>
<body bgcolor="#dddddd">
<?
if( !$kysitlus ) die( "</body></html>" );
$relations = get_relations( $kysitlus );
//**/precho( $relations );

$SIDSTR = "./l3.osalejad.php?SID=$SID&kysitlus=$kysitlus";
if( $m ) $SIDSTR .= "&m=$m";
$SIDSTRnM = "./l3.osalejad.php?SID=$SID&kysitlus=$kysitlus";
$osalejad = SQL_select_table( "num", "name, email, rank", "user_data", "kysitlus=$kysitlus AND ( rank='h' OR rank='hh' ) order by name", false );

echo list_users( $osalejad, "hh" );
?>
</body>
</html>

<?

/*************** funcs *****************/
function get_relations( $kysitlus ) {
// $sql = "select u1.name as kesname, u2.name as kedaname,kuidas,kes,keda,relations.status as status from relations left join user_data as u1 on kes=u1.num left join user_data as u2 on keda=u2.num where relations.kysitlus=$kysitlus";
 $sql = "select concat(u1.name, ' &lt;', u1.email, '&gt;') as kesname, u2.name as kedaname,kuidas,kes,keda,relations.status as status from relations left join user_data as u1 on kes=u1.num left join user_data as u2 on keda=u2.num where relations.kysitlus=$kysitlus";
 $rs = mysql_query( $sql );
 while( $row = mysql_fetch_assoc( $rs ) ) {
  $relations[$row[keda]][$row[kes]][keda] = $row[kedaname];
  $relations[$row[keda]][$row[kes]][kes] = $row[kesname];
  $relations[$row[keda]][$row[kes]][kuidas] = $row[kuidas];
  $relations[$row[keda]][$row[kes]][status] = $row[status];
 }
 return $relations;
}

function list_users( $users_a, $rank ) {
 if( !$users_a ) return;
 global $SIDSTR;
 global $SIDSTRnM;
 global $relations;
 foreach( $users_a as $num => $row ) {
  if( $row[rank] != $rank ) continue;
  $ret_str .= "<table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\" border=\"0\">\n";
  $ret_str .= " <tr>\n";
  $ret_str .= "  <td class=\"lead\" colspan=\"3\">$row[name] &lt;$row[email]&gt;</td>\n";
  $ret_str .= " </tr>\n";
  if( $rank == "hh" ) {
   $ret_str .= show_hindajad( $num, $relations[$num], $users_a ) . "\n";
  }
  $ret_str .= "</table>\n\n";
 }
 if( !$ret_str ) return;
 return $ret_str;
}

function show_hindajad( $keda_num, $user_rel, $users_a ) {
 global $SIDSTR;
 global $m;
 if( $user_rel )
 foreach( $user_rel as $num => $row ) {
  $rel_a[$row[kuidas]][$num] = $row[kes];
 }
 if( $rel_a )
 foreach( $rel_a as $kuidas => $list ) {
  $rel[$kuidas] = implode( "<br>", $list );
 }
//**/ precho( $rel_a );
 $ret_str .= "    <tr>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"top\" width=\"33%\"><b>Alt</b>:<br>$rel[alt]</td>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"top\" width=\"33%\"><b>Kõrvalt</b>:<br>$rel[k6rvalt]</td>\n";
 $ret_str .= "     <td class=\"plain\" valign=\"top\" width=\"33%\"><b>Ülalt</b>:<br>$rel[ylalt]</td>\n";
 $ret_str .= "    </tr>\n";
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
 if( $rank == "h" ) $rank_str = "<a href=\"$SIDSTR&swap_user_rank=$num\">H -> H+H</a>";
 elseif( $rank == "hh" ) $rank_str = "<a href=\"$SIDSTR&swap_user_rank=$num\">H+H -> H</a>";
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
