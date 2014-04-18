<?
header('Content-type: text/html; charset="iso-8859-1"',true);

include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$kysitlus = $_GET['kysitlus'];
$plokk = $_GET['plokk'];
$new_plokk = $_POST['new_plokk'];
$rem_plokk = $_GET['rem_plokk'];
$new_kys = $_POST['new_kys'];
$rem_kys = $_GET['rem_kys'];

echo('<pre>');
echo('GET-');
print_r($_GET);
echo('POST-');
print_r($_POST);
echo('REQUEST-');
print_r($_REQUEST);
echo('</pre>');

$user_data = handshake( $SID );

if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' ) die( "forbidden02" );
include( "head.html" );
?>
<body bgcolor="#dddddd">
<?
if( !$kysitlus ) die( "</body></html>" );
create_new_plokk( $new_plokk, $kysitlus );
rem_plokk( $rem_plokk );
create_new_kys( $new_kys, $kysitlus );
rem_kys( $rem_kys );
//**/swap_user_rank( $swap_user_rank );
//**/add_rel( $add_rel );
//**/rem_rel( $rem_rel );
//**/$relations = get_relations( $kysitlus );
//**/precho( $relations );

$SIDSTR = "./l3.kysimused.php?SID=$SID&kysitlus=$kysitlus";
$plokid_a = arrange_plokid_a( SQL_select_table( "plokk", "title", "blob_plokid", "kysitlus=$kysitlus order by plokk", false ) );
$kysimused_a = compile_kysimused( $kysitlus, $plokid_a );
//**/precho( $plokid_a );
//**/precho( $kysimused_a );

$new_plokk_num = $plokid_a ? end( array_keys( $plokid_a ) ) + 1 : 1;

if( is_array( $kysimused_a ) )
{
	foreach( $kysimused_a as $p_num => $p_a )
	{
		if( $p_num == $plokk ) break;
		$new_kys_num += sizeof( $p_a ) - 1;
	}
}
$new_kys_num += sizeof( $kysimused_a[$plokk] );
echo( "<table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\" border=\"0\">\n" );
echo( " <tr><td class=\"title\" colspan=\"3\"><a href=\"$SIDSTR\">Plokid</a></td></tr>\n" );
echo list_plokk_titles( $plokid_a );
echo( " <form method=\"post\" action=\"$SIDSTR\"><tr>\n" );
echo( " <tr>\n" );
echo( "  <td class=\"lead\">$new_plokk_num<input type=\"hidden\" name=\"new_plokk[num]\" value=\"$new_plokk_num\"></td>\n" );
echo( "  <td class=\"lead\"><input type=\"text\" name=\"new_plokk[title]\" class=\"inp100\"></td>\n" );
echo( "  <td class=\"plain\"><input type=\"submit\" name=\"new_plokk[submit]\" value=\"[ lisa ]\" class=\"subbut\"></td>\n" );
echo( " </tr></form>\n" );
if( $plokk ) echo( " <tr><td class=\"title\" colspan=\"3\">Ploki $plokid_a[$plokk] küsimused</td></tr>\n" );
elseif( $kysimused_a ) echo( " <tr><td class=\"title\" colspan=\"3\">Kõik küsimused</td></tr>\n" );
echo list_kysimused( $kysitlus, $kysimused_a, $plokk );
if( $plokk ) {
 echo( " <form method=\"post\" action=\"$SIDSTR&plokk=$plokk\"><tr>\n" );
 echo( " <tr>\n" );
 echo( "  <td class=\"lead\">$new_kys_num<input type=\"hidden\" name=\"new_kys[plokk]\" value=\"$plokk\"><input type=\"hidden\" name=\"new_kys[num]\" value=\"$new_kys_num\"></td>\n" );
 echo( "  <td class=\"lead\"><input type=\"text\" name=\"new_kys[kysimus]\" class=\"inp100\"></td>\n" );
 echo( "  <td class=\"plain\"><input type=\"submit\" name=\"new_kys[submit]\" value=\"[ lisa ]\" class=\"subbut\"></td>\n" );
 echo( " </tr></form>\n" );
}
echo( "</table>\n" );
?>
</body>
</html>

<?

/*************** funcs *****************/

function list_kysimused( $kysitlus, $kysimused_a, $plokk = false ) {
 if( !$plokk AND !$kysimused_a ) return;
 global $SIDSTR;
 if( $plokk ) $scan_a[$plokk] = $kysimused_a[$plokk]; else $scan_a = $kysimused_a;
//**/ precho( $scan_a );
 foreach( $scan_a as $plokk_key => $plokk_kys_a ) {
  if( !$plokk ) $ret_str .= "<tr><td colspan=\"3\" class=\"lead\">$plokk_kys_a[plokk]</td></tr>\n";
  foreach( $plokk_kys_a as $kys_num => $row ) {
   if( $kys_num == "plokk" ) continue;
   $ret_str .= " <tr>\n";
   $ret_str .= "  <td class=\"plain\">$kys_num</td>\n";
   $ret_str .= "  <td class=\"plain\">$row[kysimus]</td>\n";
   $ret_str .= "  <td class=\"plain\"><a href=\"$SIDSTR&plokk=$plokk&rem_kys=$kys_num\">[! kustuta !]</a></td>\n";
   $ret_str .= " </tr>\n";
  }
 }
 return $ret_str;
}

function create_new_plokk( $new_plokk, $kysitlus ) {
 if( !$new_plokk ) return;
 SQL_insert_row( "blob_plokid", "kysitlus=$kysitlus, plokk=$new_plokk[num], title='" . addslashes( $new_plokk[title] ) . "'", false );
}

function rem_plokk( $plokk )
{
	if( !$plokk )
	{
		return;
	}
	
	global $kysitlus;
	global $kysimused_a;
	$margin = count( $kysimused_a[$plokk] ) ? end( array_keys( $kysimused_a[$plokk] ) ) : 0;
	$power = sizeof( $kysimused_a[plokk] ) - 1;
	SQL_delete_row( "kysimused", "kysitlus=$kysitlus AND plokk=$plokk", true );
	SQL_update_row( "kysimused", "kys_num=kys_num-$power", "kys_num>$margin", true );
	SQL_delete_row( "blob_plokid", "kysitlus=$kysitlus AND plokk=$plokk", true );
}

function create_new_kys( $new_kys, $kysitlus ) {
 if( !$new_kys ) return;
 SQL_update_row( "kysitlused", "size=size+1", "num=$kysitlus", false );
 SQL_update_row( "kysimused", "kys_num=kys_num+1", "kys_num>=$new_kys[num] AND kysitlus=$kysitlus", false );
 SQL_insert_row( "kysimused", "kysitlus=$kysitlus, plokk=$new_kys[plokk], kys_num=$new_kys[num], kysimus='" . addslashes( $new_kys[kysimus] ) . "'", false );
}

function rem_kys( $kys_num ) {
 if( !$kys_num ) return;
 global $kysitlus;
 SQL_update_row( "kysitlused", "size=size-1", "num=$kysitlus", false );
 SQL_delete_row( "kysimused", "kysitlus=$kysitlus AND kys_num=$kys_num", false );
 SQL_update_row( "kysimused", "kys_num=kys_num-1", "kys_num>$kys_num", false );
}

function arrange_plokid_a( $plokid_a ) {
 if( !$plokid_a ) return;
 foreach( $plokid_a as $key => $val ) {
  $ret_a[$key] = $val[title];
 }
 return $ret_a;
}

function compile_kysimused( $kysitlus, $plokid_a )
{
	if (! is_array( $plokid_a ))
	{
		return '';
	}
	foreach( $plokid_a as $key => $val )
	{
		$kys[$key][plokk] = $val;
	}
	$rs = mysql_query( "select kys_num,kysimus,vastused,komment,plokk from kysimused where kysitlus='$kysitlus' order by lpad(kys_num,3,\"0\")" );
	while( $row = mysql_fetch_assoc( $rs ) ) $kys[$row[plokk]][$row[kys_num]] = $row;
	return $kys;
}

function list_plokk_titles( $plokid_a ) {
 if( !$plokid_a ) return;
 global $SIDSTR;
 foreach( $plokid_a as $key => $val ) {
  $ret_str .= " <tr>\n";
  $ret_str .= "  <td class=\"plain\">$key</td>\n";
  $ret_str .= "  <td class=\"plain\"><a href=\"$SIDSTR&plokk=$key\">$val</a></td>\n";
  $ret_str .= "  <td class=\"plain\"><a href=\"$SIDSTR&rem_plokk=$key\">[! kustuta !]</a></td>\n";
  $ret_str .= " </tr>\n";
 }
 return $ret_str;
}
