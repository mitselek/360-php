<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$kysitlus = $_GET['kysitlus'];
$valik = $_GET['valik'];
$orderBy = $_GET['orderBy'];

echo('<pre>');
echo('GET-');
print_r($_GET);
echo('POST-');
print_r($_POST);
#echo('REQUEST-');
#print_r($_REQUEST);
echo('</pre>');


$user_data = handshake( $SID );
if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' && $user_data[rank] !== '1' ) die( "forbidden02" );
include( "head.html" );

?>
<body bgcolor="#dddddd">
<?

if( !$kysitlus ) die( "</body></html>" );

$aruanded = get_aruanded();

echo("<div class=\"menu\">\n");

echo(" | \n");
foreach($aruanded as $aruanne)
{
	echo( "<a href=\"./l3.aruanded.php?SID=$SID&kysitlus=" . $kysitlus . "&valik=" . $aruanne['link'] . "\">" . $aruanne['link'] . "</a>\n" );
	echo( " | \n" );
}

echo("</div><!-- menu -->\n<hr/>\n");

if( $valik )
{
	print_aruanne();
}

?>
</body>
</html>

<?

/*************** funcs *****************/
function get_aruanded()
{
	$sql = "select * from aruanded where deleted != '1'";
	$rs = mysql_query( $sql );
	while( $row = mysql_fetch_assoc( $rs ) )
	{
		$aruanded[$row['link']] = $row;
	}
	return $aruanded;
}

function print_aruanne()
{
	global $aruanded;
	global $valik;
	global $kysitlus;
	global $orderBy;
	global $SID;

	$aruanne = $aruanded[$valik];

	echo( "<h2 class=\"pealkiri\">" . $aruanne['pealkiri'] . "</h2>\n" );
	
	$sql = str_replace('$küsitlus', "$kysitlus", $aruanne['query']);

	if (count($orderBy) > 0)
	{
		$sql .= " ORDER BY";
		
		foreach ($orderBy as $key => $direction)
		{
			$strA[] = " " . $key . " " . $direction;
		}
		$sql .= implode( ", ", $strA );
	}
	
	$rs = mysql_query( $sql );

#echo $sql;
	$rowcount = mysql_num_rows($rs);
	if ($rowcount == 0)
	{
		echo( "<div class=\"bold\"><b>Ühtegi kirjet ei leitud!</b></div>\n" );
		precho($sql);
		return;
	}
	echo( "<div class=\"bold\"><b>Leiti $rowcount kirjet.</b></div>\n" );

	
	while( $row = mysql_fetch_assoc( $rs ) )
	{
		$dataset[] = $row;
	}

	echo("<table border=\"1\">\n");

	echo("<tr class=\"bold\">\n");
	$keys = array_keys($dataset[0]);
	foreach( $keys as $key )
	{
		$direction = "ASC";
		$orderStr = "";
		if (isset($orderBy[$key]))
		{
			$orderStr = "v";
			if ($orderBy[$key] == "ASC")
			{
				$orderStr = "^";
				$direction = "DESC";
			}
		}
		
		$keyStr = "<a href=\"./l3.aruanded.php?SID=$SID&kysitlus=$kysitlus&valik=$valik&orderBy[$key]=$direction\">$orderStr $key $orderStr</a>";
		echo("<td><b>$keyStr</b></td>\n");
	}
	echo("</tr>\n");

	foreach( $dataset as $datarow )
	{
		echo("<tr>\n");

		foreach( $keys as $key )
		{
			echo("<td>" . $datarow[$key] . "</td>\n");
		}

		echo("</tr>\n");
	}

	echo("</table>");

	echo( "<div class=\"bold\"><b>" . $aruanne['query'] . "</b></div>\n" );
}
