<?
/*
variables
$S_x skaala pikkus - 1 (0..689)
$SID
$kys
$rel
$coords
*/
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$kys = $_GET['kys'];
$rel = $_GET['rel'];
$coords = $_GET['coords'];
$vastus = $_GET['vastus'];

$user_data = handshake( $SID );
if( $user_data === false ) die( "SMP:: forbidden" );

//**/ detect da browser
echo( "<script>
var isNS4 = document.layers ? true : false;
var isIE = document.all ? true : false;
var isDOM = document.getElementById ? true : false;

if( isDOM ) {
 var dimages=parent.frames.ww_main.window.document.getElementsByTagName(\"img\");
 document.write(\"j\"+dimages.length);
 var skaala=dimages.skaala$kys;
 var eioska=dimages.eioska$kys;
}
</script>\n" );


$S_x = 689;
$kysitlus = $user_data[kysitlus];

//**/precho( $user_data );
$split = explode( ",", $coords );
$vastus = substr( $split[0], 1 );
if( $vastus != -2 ) $vastus = $S_x - $vastus;

if( !SQL_insert_row( "vastused", "kysitlus='$kysitlus', rel='$rel', kysimus='$kys', vastus='$vastus'", false ) )
 SQL_update_row( "vastused", "vastus='$vastus'", "rel='$rel' AND kysimus='$kys'", false );

if( $vastus == -2 ) {
 echo(
 "<script>
 skaala.src = \"http://moos.ww.ee/360/_img/?c1=aaffaa&c2=ffaaaa&w=690&h=18\";
 eioska.src = \"./_img/ei-oska$lang-r.gif\";
</script>\n" );
} else {
 $vastus = $S_x - $vastus;
 echo(
 "<script>
 skaala.src = \"http://moos.ww.ee/360/_img/?c1=aaffaa&c2=ffaaaa&w=690&h=18&loc=$vastus\";
 eioska.src = \"./_img/ei-oska$lang.gif\";
</script>\n" );
}
?>
