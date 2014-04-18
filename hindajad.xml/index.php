<?php

// constants
define( "VASTAJAL_ON_ANKEEDID_VALMIS", "Valmis" );
define( "VASTAJAL_EI_OLE_ANKEEDID_VALMIS", "vastamine lõpetamata" );
define( "EMAIL_M", "mihkel.putrinsh@gmail.com" );
define( "EMAIL_MAREPORK", "mpork@hot.ee" );



$link = mysql_connect(_HOST_, _USER_, _PASS_);
$select = mysql_select_db(_DB_, $link);

$kysitlus = $_GET['kysitlus'];
$event = $_GET['event'];

$kysimustik = get_kysimustik( $kysitlus );
$kysimustiku_suurus = $kysimustik[suurus];
echo event_handler( $kysitlus, $event );


/*!*/ $xml[kysitlus][number] = $kysitlus;
/*!*/ $xml[kysitlus][nimi] = get_kysitlus_nimi( $kysitlus );
/*!*/ $xml[kysitlus][kysimustik] = &$kysimustik;

/*!*/ $xml[kysitlus][kasutajad] = get_kasutajad( $kysitlus, $ID = false );
//filter_kasutajad( $xml[kysitlus][kasutajad], "staatus", VASTAJAL_EI_OLE_ANKEEDID_VALMIS );

$xml_str .= turn_XML( $xml );
echo $xml_str;



/************************************* event handlerid ***************/
function event_handler( $kysitlus, $event = "*" ) {
   switch( $event ) {
      case "*":
         break;
      case "saadaKutsed":
         $kutsed = get_kysitlus_kutsed( $kysitlus );
         $kasutajad = get_kasutajad( $kysitlus );

         echo( $kasutajad[valmis] . '/' . $kasutajad[vastamata] . "<br/>\n");
         unset( $kasutajad[valmis] );
         unset( $kasutajad[vastamata] );
         filter_kasutajad( $kasutajad, "staatus", VASTAJAL_EI_OLE_ANKEEDID_VALMIS );
     $body = $kutsed['est'][body];
     echo $body;
#    print_r($kasutajad); die("\n\rEND");

/**/    send_mail( $kasutajad, $kutsed );

         // raport mailide eduka saatmise kohta
/**/    init_mail_headers( $headers );
/**/    mail( EMAIL_M, "360 Nr $kysitlus", "Kutsed edukalt saadetud", $headers );
         die( "kutsed saadetud\n</PRE></BODY></HTML>" );


         /* see blokk jagaks kasutajad kahte lehte ja l6petab proge t88
         || eesti ja inglise kasutajate kuvamisega.
         ||
         || $kasutajad_eng = $kasutajad;
         || $kasutajad_est = $kasutajad;
         || unset( $kasutajad );
         || filter_kasutajad( $kasutajad_est, "keel", "est" );
         || filter_kasutajad( $kasutajad_eng, "keel", "eng" );
         || $kasutajad[XML][est] = $kasutajad_est;
         || $kasutajad[XML][eng] = $kasutajad_eng;
         || die( turn_XML( $kasutajad ) );
         */
         break;
      case "*":
      default:
         break;
   }
   return $result_str;
}

function filter_kasutajad( &$kasutajad, $tulp, $kriteerium ) {
   foreach( $kasutajad[koos] as $num => $row ) {
      if( $row[$tulp] != $kriteerium ) {
         unset( $kasutajad[koos][$num] );
      }
   }
}

function send_mail( $kasutajad, $kutsed )
{
  init_mail_headers( $headers );
  echo( "<HTML><BODY><PRE>\n" );
  foreach( $kasutajad[koos] as $num => $row )
  {
    //**/echo ($row['nimi'] . '<br/>' );
    if (
       //**/ $row['inviteGroup'] != 1 // juhtgrupp
       //**/ $row['inviteGroup'] != 2 // hindajad
       //**/ $row['inviteGroup'] != 3 // üksikud
       $row['nimi'] != 'Ülle Filin'  // false
       && $row['nimi'] != 'Mihkel Putrinsh'  // false
	&& false
       )
    {
      continue;
    }

    $subject = str_replace( "[[nimi]]", $row[nimi], $kutsed[$row[keel]][subject] );
    $body = str_replace( "[[nimi]]", $row[nimi], $kutsed[$row[keel]][body] );
    $body = str_replace( "[[ID]]", $row[ID], $body );
    //**/mail( $row[email], $subject, $body, $headers );
    //**/mail( EMAIL_M, $subject, $body, $headers );
    echo( ++$counter . ". $row[nimi] ==-> $row[email]\n" );
    flush();
  }
  $subject = str_replace( "[[nimi]]", "[[nimi]]", $kutsed[est][subject] );
  $body = str_replace( "[[nimi]]", "[[nimi]]", $kutsed[est][body] );
  $body = str_replace( "[[ID]]", "[[ID]]", $body );
  /**/mail( EMAIL_M, "Eesti-CHECK: $subject", $body, $headers );
  $subject = str_replace( "[[nimi]]", "Miüõhkel", $kutsed[eng][subject] );
  $body = str_replace( "[[nimi]]", "Mihüõkel", $kutsed[eng][body] );
  $body = str_replace( "[[ID]]", "XXXXXXX", $body );
  //**/mail( EMAIL_M, "English-CHECK: $subject", $body, $headers );
}

function init_mail_headers( &$headers ) {
   $headers = "From: Mare Pork<" . EMAIL_MAREPORK . ">\n";
   $headers .= "X-Sender: " . EMAIL_MAREPORK . "\n";
   $headers .= "X-Mailer: PHP\n"; //mailer
   $headers .= "X-Priority: 1\n"; //1 UrgentMessage, 3 Normal
   $headers .= "Return-Path: " . EMAIL_M . "\n";
   $headers .= "Content-Type: text/html; charset=iso-8859-1;\n";
   $headers .= "MIME-Version: 1.0\n";
   $headers .= "Reply-To: " . EMAIL_MAREPORK;
}
/************************* END OF **** event handlerid ***************/


/************************************* XML generator *****************/
function init_XML( &$XML_str ) {
   $XML_str = "<?xml version=\"1.0\" encoding='iso-8859-1'?>\n\n";
}

function turn_XML( $xml, $indent = 0 ) {
  init_XML( $xml_str );
  $xml_str .= recurse_turn_XML( $xml, $indent = 0 );
  return $xml_str;
}

function recurse_turn_XML( $xml, $indent = 0 ) {
  foreach( $xml as $key => $val ) {
    $xml_str .= str_pad( "", $indent, "\t" ) . "<$key>";
    if( is_array( $val ) )
      $xml_str .= "\n" . recurse_turn_xml( &$val, $indent+1 ) . str_pad( "", $indent, "\t" );
    else
      $xml_str .= $val;
    $xml_str .= "</$key>\n";
  }
  return $xml_str;
}
/************************* END OF **** XML generator *****************/


/******************************** source array generator *************/

function get_kysitlus_kutsed( $kysitlus ) {
   $sql = "
      SELECT *
        FROM kutsed
       WHERE kysitlus = '$kysitlus'
       ORDER BY jrk DESC
       LIMIT 1
";
   $rs = mysql_query( $sql );
   $row = mysql_fetch_assoc( $rs );

   $est[subject] = ($row[title_est]);
   $est[body] = ($row[est]);
   $eng[subject] = ($row[title_eng]);
   $eng[body] = ($row[eng]);

   $ret_a[est] = $est;
   $ret_a[eng] = $eng;

   return $ret_a;
}

function get_kysimustik( $kysitlus ) {
  $sql = "select count(*) as suurus from kysimused where kysitlus='$kysitlus'";
  $rs = mysql_query( $sql );
  $row = mysql_fetch_assoc( $rs );
  return $row;
}

function get_kysitlus_nimi( $kysitlus ) {
  $sql = "select title from kysitlused where num='$kysitlus'";
  $rs = mysql_query( $sql );
  $row = mysql_fetch_assoc( $rs );
  return ($row[title]);
}

function get_kasutajad( $kysitlus, $id = true ) {

  $kasutajad[valmis] = 0;
  $kasutajad[vastamata] = 0;

  $sql = "
    SELECT ud.num, ud.name AS nimi, ud.email, ud.ID,
           if(ud.language = 'eng', 'eng', 'est') AS keel,
           if(ud.rank = 'h' , 'hindaja' , 'hinnatav') AS roll,
           ud.inviteGroup
      FROM user_data AS ud
     WHERE kysitlus = $kysitlus
     ORDER BY ud.rank desc, name
";
  $rs = mysql_query( $sql );
  while( $row = mysql_fetch_assoc( $rs ) )
  {
    $row[name] = ($row[name]);
    $roll = $row[roll];
    $number = "Nr" . str_pad( $row[num], 6, "0", STR_PAD_LEFT );
    unset( $row[roll] );
    if( !$id )
       unset( $row[ID] );

    $kasutajad[koos][$number] = $row;
    $ankeedid = get_ankeedid( $row[num] );
//die(turn_XML($ankeedid));
    if( $ankeedid[puudu] == 0 ) {
       $kasutajad[koos][$number][staatus] = VASTAJAL_ON_ANKEEDID_VALMIS;
       $kasutajad[valmis]++;
    } else {
       $kasutajad[koos][$number][staatus] = VASTAJAL_EI_OLE_ANKEEDID_VALMIS;
       $kasutajad[vastamata]++;
       $kasutajad[koos][$number][ankeedid] = $ankeedid;
    }

  }
  return $kasutajad;
}

function get_ankeedid( $hindaja_num ) {
   global $kysimustiku_suurus;
   $ret_a[valmis] = 0;
   $ret_a[puudu] = 0;

   $sql = "
      SELECT rel.num AS num,
             ud.name AS nimi
        FROM relations AS rel,
	     user_data AS ud
       WHERE rel.keda = ud.num AND
             rel.kes = $hindaja_num
       ORDER BY ud.name
   ";
   //echo $sql;
   $rs = mysql_query( $sql );
   while( $row = mysql_fetch_assoc( $rs ) ) {
      $number = "Nr" . str_pad( $row[num], 6, "0", STR_PAD_LEFT );

      $rs2 = mysql_query( "select count(*) as arv from vastused where rel='" . $row[num] . "'" );
      $row2 = mysql_fetch_assoc( $rs2 );
      if( $row2[arv] < $kysimustiku_suurus )
      {
         $row[staatus] = "-";
         $ankeedinumber = "Nr" . str_pad( $row[num], 6, "0", STR_PAD_LEFT );
         $ret_a[vastamata][$ankeedinumber][hinnatav] = $row[nimi];
         if( $row2[arv] == 0 ) {
           $ret_a[vastamata][$ankeedinumber][vastatud] = "Kõik $kysimustiku_suurus küsimust on vastamata!";
         } elseif( $row2[arv] == 1 ) {
           $ret_a[vastamata][$ankeedinumber][vastatud] = "Vastatud on üks küsimus " . $kysimustiku_suurus . "-st";
         } else {
           $ret_a[vastamata][$ankeedinumber][vastatud] = "Vastatud on $row2[arv] küsimust " . $kysimustiku_suurus . "-st";
         }
      }

//$XML[XML] = $ret_a; die(turn_XML($XML));
      if( $row[staatus] == "-" )
      {
         $ret_a[puudu]++;
      } else
      {
         $ret_a[valmis]++;
      }

   }
   return $ret_a;
}

function get_hindajad( $keda ) {
  $sql = "
  select rel.kes, rel.kuidas
       , ud.name as nimi, ud.email
  from relations as rel, user_data as ud
  where rel.kes=ud.num
    and rel.keda = $keda
  order by ud.nimi
  ";
  $rs = mysql_query( $sql );
  while( $row = mysql_fetch_assoc( $rs ) ) {
    $kuidas = $row[kuidas];
    $kes = "Nr" . str_pad( $row[kes], 6, "0", STR_PAD_LEFT );
    unset( $row[kuidas], $row[kes] );
    $hindajad[$kuidas][$kes] = $row;
  }
  return $hindajad;
}
/************************* END OF source array generator *************/


?>
