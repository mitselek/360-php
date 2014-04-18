<?
if( handshake( $SID ) === false ) die( "_SMP: forbidden" );
?>
<html>
<head>
<title>360</title>
<script>self.name = "ww_top";</script>
</head>
   <frameset rows="460,*" framespacing="0" border="0">
      <frame src="./blank.html" NAME="ww_submit"  scrolling="auto" frameborder="0">
      <frame SRC="./sisu_hindaja_js.php?SID=<? echo $SID; ?>" NAME="ww_main"  scrolling="auto" frameborder="0">
   </frameset>
</html>
