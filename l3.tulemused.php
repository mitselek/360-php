<?
include( "sql_access.php" );
include( "global.php" );

$SID = $_GET['SID'];
$kysitlus = $_GET['kysitlus'];

$user_data = handshake( $SID );
if( !$user_data ) die( "forbidden01" );
if( $user_data[rank] !== '0' && $user_data[rank] !== '1' ) die( "forbidden02" );
include( "head.html" );

?>
<body bgcolor="#dddddd">
<?

if( !$kysitlus ) die( "</body></html>" );

$tulemused = new Tulemused($kysitlus);

echo "<h1>Tulemused</h1>";
/**/$tulemused->printHindajateArv();
/**/$tulemused->printHinnatavad();
/**/$tulemused->printHindajad();


?>
</body>
</html>

<?

class Tulemused
{
	var $kysitlus;
	var $keskmised;
	var $vastused;
	var $hindajad;
	var $hinnatavad;
	
	function Tulemused($kysitlus)
	{
//**/		$this->kysitlused[] = 12;
		$this->kysitlused[] = $kysitlus;
		$this->kysitlus = '(' . implode(',',$this->kysitlused) . ')';

		$this->loeKeskmised();
		$this->loeVastused();
		$this->sordiVastajad();
		$this->sordiHinnatavad();
		$this->sordiYldstatsid();
	}

	function loeKeskmised()
	{
		$sql = "
select v.kysimus, sum(v.vastus)/count(v.vastus)/689 as keskmine
from vastused as v
where v.kysitlus IN $this->kysitlus and v.vastus >= 0 and v.kysimus >= 0
group by v.kysimus
order by lpad(v.kysimus,2,\"0\")
";

		$rs = mysql_query( $sql );
		while( $row = mysql_fetch_assoc( $rs ) )
		{
			$this->keskmised[$row['kysimus']] = $row['keskmine'];
		}
	}

	function loeVastused()
	{
		$sql = "
select r.num, r.kuidas, kes.name as kes, r.kuidas, keda.name as keda, v.kysimus, if(v.vastus=-2,-2,v.vastus/689.000000) as vastus
from relations as r
left join user_data as kes on kes.num = r.kes
left join user_data as keda on keda.num = r.keda
left outer join vastused as v on v.rel = r.num
where r.kysitlus IN $this->kysitlus
and v.vastus > -3
and v.kysimus > 0
order by kes.name, r.kuidas, lpad(if(v.kysimus>0,v.kysimus,rpad(v.kysimus,2,\".\")),2,\"0\")
";

		$rs = mysql_query( $sql );
		while( $row = mysql_fetch_assoc( $rs ) )
		{
			$this->vastused[$row['num']]['kes'] = $row['kes'];
			$this->vastused[$row['num']]['kuidas'] = $row['kuidas'];
			$this->vastused[$row['num']]['keda'] = $row['keda'];
			$this->vastused[$row['num']]['vastused'][$row['kysimus']] = $row['vastus'];
		}
	}

	function sordiVastajad()
	{
		foreach ($this->vastused as $relation => $vastus)
		{
			$this->hindajad[$vastus['kes']][$vastus['keda']]['kuidas'] = $vastus['kuidas'];
			$this->hindajad[$vastus['kes']][$vastus['keda']]['omavastused'] = $vastus['vastused'];
			$this->hindajad[$vastus['kes']][$vastus['keda']]['teistevastused'] = $this->teisteVastused($vastus['kes'], $vastus['keda']);
			$this->hinnatavad[$vastus['keda']]['kuidas'] = array();
			$this->hinnatavad[$vastus['keda']]['relation'][$relation]['vastused'] = $vastus;
		}
	}

	function teisteVastused($kes, $keda)
	{
		$teistevastused['keskmine'] = array();

		foreach ($this->vastused as $vastus)
		{
			if ($vastus['kes'] == $kes)
				continue;

			if ($vastus['kes'] == $keda)
				continue;

			if ($vastus['keda'] == $keda)
			{
				$teistevastused['eraldi'][$vastus['kes']]['kuidas'] = $vastus['kuidas'];
				$teistevastused['eraldi'][$vastus['kes']]['vastused'] = $vastus['vastused'];
			}
		}

		foreach ($teistevastused['eraldi'] as $vastus)
		{
			foreach ($vastus['vastused'] as $number => $vaartus)
			{
				if ($vaartus == -2)
					continue;

				$teistevastused['kokku'][$number] += $vaartus;
				$teistevastused['arv'][$number] ++;
			}
			
			if (count ($teistevastused['kokku']) > 0)
			{
				foreach ($teistevastused['kokku'] as $number => $kokku)
				{
					$teistevastused['keskmine'][$number] = 
						round($kokku*1000 / $teistevastused['arv'][$number]) / 1000;
				}
			}
		}
		
		return $teistevastused;
	}

	function sordiHinnatavad()
	{
		foreach ($this->hinnatavad as $hinnatavaNimi => $hinnatav)
		{
			foreach ($hinnatav['relation'] as $relation => $hindaja)
			{
				$this->hinnatavad[$hinnatavaNimi]['kuidas'][$hindaja['vastused']['kuidas']]['ankeedid'][] = $hindaja['vastused']['vastused'];
			}
			unset($this->hinnatavad[$hinnatavaNimi]['relation']);
		}

		foreach ($this->hinnatavad as $hinnatavaNimi => $hinnatav)
		{
			//**/precho($hinnatav['kuidas']);
			foreach ($hinnatav['kuidas'] as $kuidas => $ankeedid)
			{
				foreach ($ankeedid['ankeedid'] as $ankeet)
				{
					foreach ($ankeet as $nr => $vastus)
					{
						if ($vastus == -2)
							continue;

						$this->hinnatavad[$hinnatavaNimi]['kuidas'][$kuidas]['count'][$nr] ++;
						$this->hinnatavad[$hinnatavaNimi]['kuidas'][$kuidas]['sum'][$nr] += $vastus;
					}
				}
				unset($this->hinnatavad[$hinnatavaNimi]['kuidas'][$kuidas]['ankeedid']);
			}
		}
	}

	function sordiYldstatsid()
	{
		$this->yldstatsid['hindajaid']['juhid'] = array();
      $_hindajaid =& $this->yldstatsid['hindajaid']['juhid'];
      
		$this->yldstatsid['küsimused']['koos'] = array();
		$_koos =& $this->yldstatsid['küsimused']['koos'];
		
		$_koos['juhid']['koos'] = array();
		$_koos_koos =& $_koos['juhid']['koos'];

		$_koos_koos['ise']['count'] = 0;
		$_koos_koos['ise']['sum'] = 0;

		$_koos_koos['teised']['count'] = 0;
		$_koos_koos['teised']['sum'] = 0;

		$_koos['juhid']['eraldi'] = array();
		$_koos_eraldi =& $_koos['juhid']['eraldi'];

		$this->yldstatsid['küsimused']['eraldi'] = array();
		$_eraldi =& $this->yldstatsid['küsimused']['eraldi'];

		foreach ($this->vastused as $ankeet)
		{
         $_hindajaid[$ankeet['keda']][$ankeet['kuidas']]++;
			foreach ($ankeet['vastused'] as $nr => $vastus)
			{
				if ($vastus == -2)
					continue;

				if ($ankeet['kuidas'] == 'seest')
					$suund = 'ise';
				else
					$suund = 'teised';

				$_koos_koos[$suund]['count'] ++;
				$_koos_koos[$suund]['sum'] += $vastus;

				$_koos_eraldi[$ankeet['keda']][$suund]['count'] ++;
				$_koos_eraldi[$ankeet['keda']][$suund]['sum'] += $vastus;

				$_koos_eraldi[$ankeet['keda']][$ankeet['kuidas']]['count'] ++;
				$_koos_eraldi[$ankeet['keda']][$ankeet['kuidas']]['sum'] += $vastus;

				$_eraldi[$nr]['juhid']['koos'][$suund]['count'] ++;
				$_eraldi[$nr]['juhid']['koos'][$suund]['sum'] += $vastus;

				$_eraldi[$nr]['juhid']['eraldi'][$ankeet['keda']][$suund]['count'] ++;
				$_eraldi[$nr]['juhid']['eraldi'][$ankeet['keda']][$suund]['sum'] += $vastus;

				$_eraldi[$nr]['juhid']['eraldi'][$ankeet['keda']][$ankeet['kuidas']]['count'] ++;
				$_eraldi[$nr]['juhid']['eraldi'][$ankeet['keda']][$ankeet['kuidas']]['sum'] += $vastus;
			}
		}
	}

	function printHindajad()
	{
		echo "<h1>HINDAJAD</h1>";

		foreach ($this->hindajad as $hindajaNimi => $hinnatavad)
		{
			echo "<table border=\"1\" width=\"300\">";


			foreach ($hinnatavad as $hinnatavaNimi => $hinnatav)
			{
			   echo "<tr>";
			   echo "<td>hindaja:</td><th colspan=\"3\"><h3>$hindajaNimi</h3></th>";
			   echo "</tr>";

				echo "<tr>";
				echo "<td>hinnatav:</td><th colspan=\"3\"><h4>$hinnatavaNimi</h4></th>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>nr</td>";
				echo "<td>mina</td>";
				echo "<td>teised</td>";
				echo "<td>keskmine</td>";
				echo "</tr>";

				foreach ($hinnatav['omavastused'] as $number => $vaartus)
				{
					echo "<tr>";
					echo "<td>$number</td>";
					if ($vaartus == -2)
						$vaartus = "";
					echo "<td>" . str_replace(",",".",$vaartus) . "</td>";
					echo "<td>" . str_replace(",",".",$hinnatav['teistevastused']['keskmine'][$number])  . "</td>";
					echo "<td>" . str_replace(",",".",$this->keskmised[$number])  . "</td>";
					echo "</tr>";
				}
				flush();
			}

			echo "</table><br/>";
		}
	}

	function printHinnatavad()
	{
		//**/print_r ($this->yldstatsid);
		//**/precho ($this->hinnatavad);
		$_koos =& $this->yldstatsid['küsimused']['koos'];
		$_koos_koos =& $_koos['juhid']['koos'];
		$_koos_eraldi =& $_koos['juhid']['eraldi'];
		$_eraldi =& $this->yldstatsid['küsimused']['eraldi'];

		echo "<h1>HINNATAVAD</h1>";

		foreach ($this->hinnatavad as $hinnatavaNimi => $hinnatav)
		{
			echo "<table border=\"1\" width=\"300\">";
			echo "<tr><td colspan=\"9\"><h3>$hinnatavaNimi</h3></td></tr>";
			echo "<tr><th>Nr</th>";
			echo "<th>Kõik juhid - ise</th>";
			echo "<th>Kõik juhid - teised</th>";
			echo "<th>ise</th>";
			echo "<th>teised</th>";
			echo "<th>alluv</th>";
			echo "<th>kolleeg</th>";
			echo "<th>ülemus</th>";
			echo "<th>k+ü</th>";
			echo "</tr>";

			echo "<tr><td colspan=\"9\"><h4>Kõik küsimused</h4></td></tr>";
			echo "<tr>";
			echo "<td>S</td>";
			echo "<td>" . $this->evalAverage($_koos_koos['ise']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_koos['teised']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_eraldi[$hinnatavaNimi]['ise']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_eraldi[$hinnatavaNimi]['teised']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_eraldi[$hinnatavaNimi]['alt']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_eraldi[$hinnatavaNimi]['k6rvalt']) . "</td>";
			echo "<td>" . $this->evalAverage($_koos_eraldi[$hinnatavaNimi]['ylalt']) . "</td>";
			echo "<td>"
			   . round(
			      ($_koos_eraldi[$hinnatavaNimi]['k6rvalt']['sum']+$_koos_eraldi[$hinnatavaNimi]['ylalt']['sum'])
			      * 1000
			      / ($_koos_eraldi[$hinnatavaNimi]['k6rvalt']['count']+$_koos_eraldi[$hinnatavaNimi]['ylalt']['count'])
			          ) / 1000
			   . "</td>";
			echo "</tr>";

			foreach ($_eraldi as $nr => $eraldi)
			{
				echo "<tr>";
				echo "<td>" . $nr . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['koos']['ise']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['koos']['teised']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['eraldi'][$hinnatavaNimi]['ise']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['eraldi'][$hinnatavaNimi]['teised']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['eraldi'][$hinnatavaNimi]['alt']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['eraldi'][$hinnatavaNimi]['k6rvalt']) . "</td>";
				echo "<td>" . $this->evalAverage($eraldi['juhid']['eraldi'][$hinnatavaNimi]['ylalt']) . "</td>";
				echo "<td>"
				   . round(
				      ($eraldi['juhid']['eraldi'][$hinnatavaNimi]['k6rvalt']['sum']+$eraldi['juhid']['eraldi'][$hinnatavaNimi]['ylalt']['sum'])
				      * 1000
				      / ($eraldi['juhid']['eraldi'][$hinnatavaNimi]['k6rvalt']['count']+$eraldi['juhid']['eraldi'][$hinnatavaNimi]['ylalt']['count'])
				          ) / 1000
				   . "</td>";
				echo "</tr>";
			}
			
			echo "</table><br/>";
		}
	}


   function printHindajateArv()
   {
			echo "<table border=\"1\" width=\"300\">";
				echo "<tr>";
				echo "<th>Hinnatav</th>";
				echo "<th>alt</th>";
				echo "<th>kõrvalt</th>";
				echo "<th>ülalt</th>";
				echo "</tr>";
     foreach ($this->yldstatsid['hindajaid']['juhid'] as $juhiNimi => $kuidas)
     {
				echo "<tr>";
				echo "<td>" . $juhiNimi . "</td>";
				echo "<td>" . 1*$kuidas['alt'] . "</td>";
				echo "<td>" . 1*$kuidas['k6rvalt'] . "</td>";
				echo "<td>" . 1*$kuidas['ylalt'] . "</td>";
				echo "</tr>";
     }
			echo "</table><br/>";
   }

	function evalAverage( $v )
	{
		if ($v['count'] > 0)
			return str_replace(",",".", round($v['sum']*1000 / $v['count']) / 1000);
		else
			return "-";
	}
	
}
