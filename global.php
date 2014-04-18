<?

$hid_ID_field = hidden_user_ID_form_field();
$hid_ktk_field = hidden_kysitlus_form_field();
$hid_kms_field = hidden_kysimus_form_field();
$hid_kjp_field = hidden_kjp_form_field();
$hid_vas_field = hidden_vas_form_field();
$sess_get_vars = make_sess_get_vars();

function handshake( $SID )
{
	/**/ move_old_sess();
	global $REQUEST_URI;
	$user = SQL_select_row( "user as num, SID", "sess_data", "SID='$SID'", false );
#	print_r( $user ); die();
	if( $user )
	{
		SQL_update_field( "sess_data", "end=null, URI='$REQUEST_URI'", "SID='$SID'", false );
		$row = SQL_select_row( "rank, kysitlus, name, email, language", "user_data", "num=" . $user[num], false );
		$user = array_merge( $user, $row );
	}
	return $user;
}

function move_old_sess() {
 if( $row = SQL_select_row( "*", "sess_data", "unix_timestamp() - unix_timestamp(end) > 36000", false ) ) {
  SQL_delete_row( "sess_data", "SID='$row[SID]'", false );
  if( $row[address] == "80.235.26.154" ) return;
  foreach( $row as $key => $val ) {
   $sess_str_a[] = "$key='$val'";
  }
  $sess_str = implode( ",", $sess_str_a );
  SQL_insert_row( "old_sess", $sess_str, false );
 }
}

function login( $ID )
{
	$user = SQL_select_row( "*", "user_data", "ID='$ID'", false );
	if( $user )
	{
		$user = get_SID( $user );
		//**/precho( $user );
	}
	return $user;
}

function get_SID( $user ) {
 global $REMOTE_ADDR;
 global $REQUEST_URI;
 mt_srand( (double) microtime() * 1000000 );
 $SID = md5( mt_rand(0,9999999) );
 SQL_insert_row( "sess_data", "SID='$SID', user=$user[num], begin=null, address='$REMOTE_ADDR', URI='$REQUEST_URI'" );
 $user[SID] = $SID;
 return $user;
}

function precho( $var ) {
 if( $_SERVER['REMOTE_ADDR'] != "212.107.32.25" ) {
  //echo $_SERVER['REMOTE_ADDR'];
  return;
 }
 echo( "<pre>\nVar-dump:\n" );
 print_r( $var );
 echo( "</pre>\n" );
}


function single_text_form( $name, $action, $var_name, $button_name, $default = "" ) {
 $ret_str .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">";
 $ret_str .= "<form method=\"post\" action=\"$action\"><tr><td class=\"plain\">";
 $ret_str .= "$name<br />";
 $ret_str .= "<input type=\"text\" class=\"inp100\" name=\"$var_name\" value=\"$default\"><br />";
 $ret_str .= "<input type=\"submit\" value=\"$button_name\" class=\"subbut\">";
 $ret_str .= "</td></tr></form></table>";
 return $ret_str;
}

function timestamp_format( $stamp ) {
 if( $stamp == 0 ) return "N/A";
 return strftime( "%d/%m/%Y", $stamp );
}

function input_form_field( $size, $name, $value ) {
 return "<input type=\"text\" size=\"$size\" name=\"$name\" value=\"$value\">";
}

function input_radio_field( $name, $value, $title, $current ) {
 if( $current == $value ) $checked = " CHECKED";
 return "<input type=\"radio\" name=\"$name\" value=\"$value\"$checked> $title";
}


function hidden_user_ID_form_field() {
 global $ID;
 return "<input type=\"hidden\" name=\"ID\" value=\"$ID\">";
}
function hidden_kysitlus_form_field() {
 global $ktk;
 return "<input type=\"hidden\" name=\"ktk\" value=\"$ktk\">";
}
function hidden_kysimus_form_field() {
 global $kms;
 return "<input type=\"hidden\" name=\"kms\" value=\"$kms\">";
}
function hidden_kjp_form_field() {
 global $kjp;
 return "<input type=\"hidden\" name=\"kjp\" value=\"$kjp\">";
}
function hidden_vas_form_field() {
 global $vas;
 return "<input type=\"hidden\" name=\"vas\" value=\"$vas\">";
}
function make_sess_get_vars() {
 global $ID;
 global $ktk;
 global $kms;
 global $kjp;
 if( $ID )
  $str_a[] = "ID=$ID";
 if( $ktk )
  $str_a[] = "ktk=$ktk";
 if( $kms )
  $str_a[] = "kms=$kms";
 if( $kjp )
  $str_a[] = "kjp=$kjp";
 if( $vas )
  $str_a[] = "vas=$vas";

 if( $str_a )
  return "?" . implode( "&", $str_a );
}
?>
