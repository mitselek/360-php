<?php
header ("Content-type: image/png");

$RGB1 = hex2dec ($c1);
$RGB2 = hex2dec ($c2);

$im = ImageCreateTrueColor ($w, $h);
$cursor = ImageColorAllocate( $im, 0, 0, 0 );
$white = ImageColorAllocate( $im, 255, 255, 255 );

$src = ImageCreate (256, 1);
for ($i=0; $i<256; $i++) {
  $red   = round( $RGB1["R"] + ($RGB2["R"]-$RGB1["R"])*$i/256 );
  $green = round( $RGB1["G"] + ($RGB2["G"]-$RGB1["G"])*$i/256 );
  $blue  = round( $RGB1["B"] + ($RGB2["B"]-$RGB1["B"])*$i/256 );
  $fount_col = ImageColorAllocate( $src, $red, $green, $blue );
  ImageLine( $src, $i, 0, $i, 0, $fount_col );  
}
ImageCopyResized( $im, $src, 0, 0, 0, 0, $w, $h, 256, 1 );

if( isset( $loc ) ) {
 ImageRectangle( $im, $loc-1, 0, $loc+1, $h-1, $cursor );
 ImageLine( $im, $loc, 1, $loc, $h-2, $white );
}
Imagepng ($im);

//int imagecopyresized (int dst_im, int src_im, int dstX, int dstY, int srcX, int srcY, int dstW, int dstH, int srcW, int srcH)


function hex2dec ($color) {
  $r = hexdec(substr($color, 0, 2));
  $g = hexdec(substr($color, 2, 2));
  $b = hexdec(substr($color, 4, 2));
  return array ("R" => $r, "G" => $g, "B" => $b);
}
?>
