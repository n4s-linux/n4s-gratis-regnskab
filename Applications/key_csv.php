<?php
require_once("/svn/svnroot/Applications/str_file_filter.php");
require_once("/svn/svnroot/Applications/proc_open.php");
require_once("/svn/svnroot/Applications/print_array.php");
require_once("/svn/svnroot/Applications/lookup_account.php");
require_once("/svn/svnroot/Applications/oledger/csv_to_array.php");
if (isset($argv[1]) && $argv[1] == "load") {
	loadfile();
	echo "loaded()";sleep(5);
	die();
}
function loadfile() {
	$path = getenv("tpath");
  global $accounts;
  $availablefields =array( "Date","Description","Reference","Amount","Account","Func","Comment","Choose");;
       array_push($availablefields,"No match");
$op=exec("whoami");
	global $argv;
	if (!isset($argv[2]))
  $filename = "/home/$op/tmp/" . time() . ".csv";
	else
		$filename = $argv[2];
       exec_app("nano $filename");
	if (!file_exists($filename)) die("afbrudt ingen data\n");
       $d = detectDelimiter("$filename");
       $data = csv_to_array("$filename",$d);
	unlink($filename);
       $contraacc = lookup_acc($accounts,0,"Bankimport");
       $mappings = array();
	require_once("/svn/svnroot/Applications/fzf.php");
       foreach ($data['header'] as $header) {
		$mappings[$header] = fzf(implode("\n",$availablefields),"chose what field $header maps to");
		if ($mappings[$header] == "Choose") {
			echo "Indtast navn på felt du ønsker at indlæse '$header' i: ";
			$fd = fopen("PHP://stdin","r");$str = trim(fgets($fd));fclose($fd);
			$mappings[$header] = $str;
		}
       }
       foreach ($data['rows'] as $row) {
           $c = array();
           foreach ($mappings as $csvheader => $datafield) {
                   $c[$datafield] = $row[$csvheader];
           }
      $c['Amount'] = str_replace(".","",$c['Amount']);
      $c['Amount'] = str_replace(",",".",($c['Amount']));
	$c['Date'] = str_replace(".","-",$c['Date']);
	$c['Date'] = str_replace("/","-",$c['Date']);
if (isset($c['Func']))
	$curfunc = $c['Func'];
else
	$curfunc = "";
if (isset($c['Account']))
	$curacc = $c['Account'];
else
	$curacc = "";
           $curtrans['Transactions'] = array();
	$curtrans['Comment'] = "";
         $curtrans['Description'] = clean($c['Description']);
	$x = explode("-",$c['Date']);
	if (strlen($x[0]) == 2 && strlen($x[1]) == 2 && strlen($x[2]) == 4)
		$curtrans['Date'] = date("Y-m-d",strtotime($x[2]."-" . $x[1] . "-" . $x[0]));
        else if (strlen($x[0]) == 2 && strlen($x[1]) == 2 && strlen($x[2]) == 2)
		$curtrans['Date'] = date("Y-m-d",strtotime(2000+$x[2]."-" . $x[1] . "-" . $x[0])); // could be problematic for the next generation
	else // assume iso
		$curtrans['Date'] = date("Y-m-d",strtotime($c['Date']));
	if (strtotime($curtrans['Date']) < strtotime("1986-12-25"))
		die("Aborting, this is some old stuff that should probably not be imported- cant understand date format $c[Date]...\n");
	if (strtotime($curtrans['Date']) > strtotime("+1 days") && getenv("future") != "1")
		die("Aborting, we should not import future transactions $c[Date]...\n");
         $curtrans['UID'] = uniqid();
	if (isset($c["Reference"]))
		 $curtrans['Reference'] = $c['Reference'];
	else
		$curtrans["Reference"] = "";
         if ($curtrans['Reference'] == "")
           $curtrans['Reference'] = 'CSV' . "-" . $curtrans['UID'];
         $curtrans['Filename'] =  $path . "/" . str_file_filter($c['Description'] . " - " . $curtrans['Date']) . "-$curtrans[UID].trans";
         $fn = $curtrans['Filename'];
         $curtrans['Filename'] =  str_file_filter($c['Description'] . " - " . $curtrans['Date']) . "-$curtrans[UID].trans";
$curtrans['History'] = array(array('op'=>$op,'Date'=>date("Y-m-d"),'Desc'=>'Loaded from CSV'));
                 $curtrans['Transactions'] = array(
                   array('Account'=>$contraacc,'Amount'=> $c['Amount'],'Func'=>''),
                   array('P-Start'=>'','P-End'=>'','Account'=> ($curacc == "") ? 
                         (( $c['Amount'] < 0) ? "Fejlkonto:Uhåndterede kreditorbetalinger" : "Fejlkonto:Uhåndterede debitorbetalinger") : $curacc
                         ,'Func'=>$curfunc,'Amount'=> $c['Amount'] * -1)
                   );
         file_put_contents($fn,json_encode($curtrans,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
          
         }

die();

}
function detectDelimiter($csvFile)
{
    $delimiters = array(
        ';' => 0,
        ',' => 0,
        "\t" => 0,
        "|" => 0
    );

    $handle = fopen($csvFile, "r");
    $firstLine = fgets($handle);
    fclose($handle); 
    foreach ($delimiters as $delimiter => &$count) {
        $count = count(str_getcsv($firstLine, $delimiter));
    }

    return array_search(max($delimiters), $delimiters);
}
function clean($string) {
	$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
	$string = str_replace(";",":",$string);
	$string = str_replace("\\","",$string);
   return $string;
}
?>
