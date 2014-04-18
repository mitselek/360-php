<?
if( handshake( $SID ) === false ) die( "SMP: forbidden" );
?>

<html>
<head>
 <title>360</title>
 <link rel="stylesheet" href="mp.css" type="text/css">
 <script> window.name = 'mp_360'; </script>
</head>
<frameset cols="160,*" frameborder="1" framespacing="3" marginwidth="0" border="3">
   <frameset rows="50%,*" frameborder="1" framespacing="3" marginwidth="0" border="3">
     <frame src="l1.php?SID=<?echo $SID;?>" NAME="360_l1" scrolling="auto">
     <frame src="l2.php?SID=<?echo $SID;?>" NAME="360_l2" scrolling="auto">
   </frameset>
   <frame src="l3.php" NAME="360_l3" scrolling="auto">
</frameset>
</html>
