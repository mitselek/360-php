<?
$link = mysql_connect(_HOST_, _USER_, _PASS_);
$select = mysql_select_db(_DB_, $link);

//................. SQL access functions ..................//

function SQL_hit( $ID ) {
 global $query;
 global $v;
 global $o;

 if( isset( $query ) ) {
  $query = addslashes( $query );
  SQL_insert_row( "hits", "ID='$ID', tyyp='q', value='$query'" );
 }
 if( isset( $v ) ) {
  SQL_insert_row( "hits", "ID='$ID', tyyp='v', value='$v'" );
  if( isset( $o ) ) SQL_insert_row( "hits", "ID='$ID', tyyp='o', value='" . $v . "_" . $o . "'" );
 }
}

function SQL_login( $rank ) {
 mt_srand( (double) microtime() * 1000000 );
 $ID = md5( mt_rand(0,9999999) );
 SQL_insert_row( "sess_data", "ID='$ID', rank=$rank" );
 return $ID;
}
function SQL_check_login( $ID ) {
 mysql_query( "delete from sess_data where unix_timestamp() - unix_timestamp(timestamp) > 1800" );
 if( SQL_update_field( "sess_data", "timestamp=null", "ID='$ID'" ) > 0 ) {
  $row = SQL_select_row( "rank", "sess_data", "ID='$ID'" );
  return $row[rank];
 } else {
  return -1;
 }
}

function SQL_new_text( $text ) {
 if( !isset( $text ) ) return false;
 $text = addslashes( $text );
 SQL_insert_row( "texts", "text='$text'", false );
 $row = SQL_select_row( "num", "texts", "text='$text'", false );
 return $row[num];
}
function SQL_get_text( $num ) {
 global $l;
 if( $l == 0 ) $l_str = "text";
 elseif( $l == 1 ) $l_str = "english";
 $rs = mysql_query( "select $l_str as text from texts where num=$num" );
 if( !mysql_num_rows($rs) ) return;
 $row = mysql_fetch_assoc( $rs );
 $text = stripslashes( $row[text] );
 return $text;
}

function SQL_update_field( $table, $set, $where = "", $echo = false ) {
 return SQL_update_row( $table, $set, $where, $echo );
}
function SQL_update_row( $table, $set, $where = "", $echo = false ) {
 if( $where ) $where = " where $where";
/**/ if( $echo ) echo( "update $table set $set$where<br>\n" );
 mysql_query( "update $table set $set$where" );
 return mysql_affected_rows();
}

function SQL_delete_row( $table, $where, $echo = false ) {
/**/ if( $echo ) echo( "delete from $table where $where<br>\n" );
 mysql_query( "delete from $table where $where" );
 return mysql_affected_rows();
}

function SQL_insert_row( $table, $set, $echo = false ) {
/**/ if( $echo ) echo( "insert into $table set $set<br>\n" );
 mysql_query( "insert into $table set $set" );
}

function SQL_select_row( $list, $table, $where = "", $echo = false ) {
 if( $where ) $where = " where $where";
/**/ if( $echo ) echo( "select $list from $table$where<br>\n" );
 $rs = mysql_query( "select $list from $table$where" );
 if( !mysql_num_rows( $rs ) ) return false;
 return mysql_fetch_assoc( $rs );
}
function SQL_select_table( $key, $list, $table, $where = "", $echo = false ) {
 $list_a = explode( ",", $list );
 if( $list == "" ) unset( $list_a );
 $list_a[] = $key;
 $list = implode( ",", $list_a );
 if( $where ) $where = " where $where";
/**/ if( $echo ) echo( "select $list from $table$where<br>\n" );
 $rs = mysql_query( "select $list from $table$where" );
 if( !mysql_num_rows( $rs ) ) return false;
 while( $row = mysql_fetch_assoc( $rs ) ) {
  $ret_a[$row[$key]] = $row;
 }
 return $ret_a;
}

?>
