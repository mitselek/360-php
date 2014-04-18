<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];

if( $user_data = handshake( $SID ) === false ) die( "Forbidden" );
include( "head.html" );
?>
<body bgcolor="#dddddd">
<?

if( strlen( $_POST['uuskys'] ) > 2 ) $kys = add_kysitlus( $_POST['uuskys'] );
swap_kys_status( $_GET['swap_kys_status'] );
remove_kys( $_GET['remove_kys'] );

echo( "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">\n");
echo( " <tr><td><a href=\"./\" target=\"mp_360\" title=\"Log OUT\">{X}</a></td><td class=\"title\" colspan=\"2\">Küsitluste nimekiri</td><tr>\n");
echo kysitluslist();
echo " <tr><td colspan=\"3\">" . single_text_form( "Uus küsitlus", "./l1.php?SID=$SID", "uuskys", "[ lisa ]" ) . "</td></tr>\n";
echo( "<table>\n");


/********** funcs ***********/
function kysitluslist()
{
	global $SID;
	global $user_data;

	
	$table = SQL_select_table( "num", "title, status", "kysitlused", "", false );
	
	foreach( $table as $num => $row )
	{
		$rem_str = "[X]";
		if( $row[status] == "aktiivne" )
		{
			$bgcolor = "#ddffdd";
		}
		elseif( $row[status] == "inaktiivne" )
		{
			$bgcolor = "#ffffdd";
		}
		elseif( $row[status] == "kustutatud" )
		{
			$bgcolor = "#ffdddd";
			$rem_str = "[U]";
		}
		$ret_str .= " <tr bgcolor=\"$bgcolor\">\n";
		if( $user_data[rank] === '0')
		{
			$ret_str .= "  <td class=\"plain\"><a href=\"./l1.php?SID=$SID&remove_kys=$num\">$rem_str</a></td>\n";
		}
		else
		{
			$ret_str .= "  <td class=\"plain\">$rem_str</td>\n";
		}
		$ret_str .= "  <td class=\"plain\"><a href=\"./l2.php?SID=$SID&kysitlus=$num\" target=\"360_l2\">$row[title]</a></td>\n";
		$ret_str .= "  <td class=\"plain\"><a href=\"./l1.php?SID=$SID&swap_kys_status=$num\">$row[status]</td>\n";
		$ret_str .= " </tr>\n";
	}
	return $ret_str;
}

function add_kysitlus( $uuskys ) {
 $uuskys = addslashes( $uuskys );
 SQL_insert_row( "kysitlused", "title='$uuskys'" );
 $row = SQL_select_row( "num", "kysitlused", "title='$uuskys'", false );
 return $row[num];
}

function swap_kys_status( $num ) {
 if( !$num ) return;
 SQL_update_field( "kysitlused", "status=4-status", "num=$num");
}
function remove_kys( $num ) {
 if( !$num ) return;
 SQL_update_field( "kysitlused", "status=abs(status-2)+1", "num=$num");
}


?>
</body>
</html>

