<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$ty = $_GET['ty'];

if( $ty == "thankyou" )
{
	include( "thankyou.php" );
	die();
}


$user_data = handshake( $SID );
if( $user_data === false ) {
	$user_data = login( $_REQUEST['ID'] );
	if( $user_data === false ) {
		include( "login_form.php" );
	}
}
$SID = $user_data[SID];
unset( $user_data[ID] );
unset( $user_data[SID] );
//**/precho( $user_data );

if( $user_data[rank] === "0" )
{
	include( "sisu_mp.php" );
}
elseif( $user_data[rank] === "1" )
{
	include( "sisu_mp.php" );
}
elseif( $user_data[rank] === "h" or $user_data[rank] === "hh" )
{
	if( $REMOTE_ADDR == "80.235.26.154" )
	{
		include( "frameset_hindaja.php" );
	}
	else
	{
		include( "frameset_hindaja.php" );
	}
}
?>
