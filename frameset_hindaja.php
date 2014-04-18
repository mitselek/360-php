<?
if( handshake( $SID ) === false ) die( "smp: forbidden" );
?>
<html>
<head>
<title>360</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script>self.name = "ww_top";</script>
</head>
   <frameset rows="0,*" framespacing="0" border="0">
      <frame src="./blank.html" NAME="ww_submit" noresize scrolling="no" frameborder="0">
      <frame SRC="./sisu_hindaja_js.php?SID=<? echo $SID; ?>" NAME="ww_main" noresize scrolling="auto" frameborder="0">
   </frameset>
</html>
