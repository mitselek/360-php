<HTML>
<BODY>
<?php

// constants

$link = mysql_connect(_HOST_, _USER_, _PASS_);
$select = mysql_select_db(_DB_, $link);

$q1 = "select v1.kysitlus, v1.rel, v1.kysimus, v2.vastus, v1.tekst_num, v1.timestamp
  from vastused as v1,
       vastused as v2
 where v1.kysitlus = v2.kysitlus AND
       v1.rel = v2.rel AND
       concat( '', v1.kysimus ) = concat( '0', v2.kysimus ) AND
			 v1.vastus < v2.vastus;";

$rs = mysql_query( $q1 );

while( $row = mysql_fetch_assoc( $rs ) ) {
  $q2 = "UPDATE vastused
   SET vastus = $row[vastus]
 WHERE kysitlus = $row[kysitlus] AND
       rel = $row[rel] AND
       kysimus = '$row[kysimus]' AND
       tekst_num = $row[tekst_num] AND
       timestamp = $row[timestamp];";
  mysql_query( $q2 );
  echo( ++$counter . ". " . $q2 . "<br>\n" );
  flush();
}


?>
</BODY>
</HTML>
