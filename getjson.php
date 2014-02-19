<?php

$database = "/var/lib/wview/archive/wview-archive.sdb";

//------------list of sensors and their units--------------
$sensorList =  array( 'dateTime' => 's',
    'usUnits' => 'none',
    'interval' => 's',
    'barometer' => 'mbar', 'pressure' => 'mbar', 'altimeter' => 'mbar',
    'inTemp' => 'deg C', 'outTemp' => 'deg C',
    'inHumidity' => 'percent', 'outHumidity' => 'percent',
    'windSpeed' => 'm/s', 'windDir' => 'deg.', 'windGust' => 'm/s', 'windGustDir' => 'deg.',
    'rainRate' => 'mm/h', 'rain' => 'mm', 'rainCumulative' => 'mm',
    'dewpoint' => 'deg C', 'windchill' => 'deg C', 'heatindex' => 'deg C',
    'ET' => 'none',
    'radiation' => 'W/m2',
    'UV' => 'none',
    'extraTemp1' => 'deg C', 'extraTemp2' => 'deg C', 'extraTemp3' => 'deg C',
    'sunHeating' => 'deg C',
    'soilTemp1' => 'deg C', 'soilTemp2' => 'deg C', 'soilTemp3' => 'deg C', 'soilTemp4' => 'deg C',
    'leafTemp1' => 'deg C', 'leafTemp2' => 'deg C',
    'extraHumid1' => 'percent', 'extraHumid2' => 'percent',
    'soilMoist1' => 'percent', 'soilMoist2' => 'percent', 'soilMoist3' => 'percent', 'soilMoist4' => 'percent',
    'leafWet1' => 'percent', 'leafWet2' => 'percent',
    'rxCheckPercent' => 'percent',
    'txBatteryStatus' => 'percent',
    'consBatteryVoltage' => 'V',
    'hail' => 'mm', 'hailRate' => 'mm/h',
    'heatingTemp' => 'deg C', 'heatingVoltage' => 'V', 'supplyVoltage' => 'V', 'referenceVoltage' => 'V',
    'windBatteryStatus' => 'percent', 'rainBatteryStatus' => 'percent', 'outTempBatteryStatus' => 'percent', 'inTempBatteryStatus' => 'percent',
);

//---------------function to convert values in US units to metric----------
function makeMetric($value,$unit) {
    if (is_null($value)) {
        $metricvalue = NULL;
    }
    else {
        if ($unit === 'mm') {
            $metricvalue = $value*25.4; //in to mm
        }
        else if ($unit === 'mm/h') { 
            $metricvalue = $value*25.4; //in/h to mm/h
        }
        else if ($unit === 'mbar') {
            $metricvalue = $value*33.86; //in Hg to mbar
        }
        else if ($unit === 'deg C') {
            $metricvalue = ($value-32)*5/9; // deg F to deg C
        }
        else if ($unit === 'm/s') {
            $metricvalue = $value*0.44704; //mph to m/s
        }
        else {
            $metricvalue = $value; //no conversion needed
        }
    }
    return $metricvalue;
}

//   ----------function to cumulate values-----------------
function cumsum($values = array()) {
    // modified from http://stackoverflow.com/questions/12715255/cumulative-array
    // re-index the array for guaranteed-success with the for-loop
    $values = array_values($values);
    $cumulated = array();
    array_push($cumulated,$values[0]);
    $count = count($values);
    if ($count == 1) {
        // there is only a single element in the array; no need to loop through it
        return $cumulated;
    } else {
        // iterate through each element (starting with the second) and add
        // the prior-element's value to the current
        for ($i = 1; $i < $count; $i++) {
            $cumulated[] = $values[$i] + $cumulated[$i - 1];
        }
    }
    return $cumulated;
}


//   ----------function to subtract arrays elementwise-----------------
function arraySubtract($values1 = array(), $values2 = array()) {
  $values1 = array_values($values1);
  $values2 = array_values($values2);
  $arraySubt = array();
  //array_push($cumulated,$values[0]);
  $count = count($values1);
  for ($i = 0; $i < $count; $i++) {
    if ( !is_null($values1[$i]) && !is_null($values2[$i]) ) {
      $arraySubt[] = $values1[$i] - $values2[$i];
    }
    else {
      $arraySubt[] = NULL;
    }
  }
  return $arraySubt;
}


//   ----------function to add constant to array elementwise-----------------
function arrayAddConstant($values = array(), $constval) {
    $values = array_values($values);
    $arrayAdded = array();
    //array_push($cumulated,$values[0]);
    $count = count($values);
    for ($i = 0; $i < $count; $i++) {
        if (!is_null($values[$i])) {
            $arrayAdded[] = $values[$i] + $constval;
        }
        else {
            $arrayAdded[] = NULL;
        }
    }
    return $arrayAdded;
}


//-------------- main program-------------------

//parse url arguments,check if the request is valid
$validrequest = 1;
$message = 'ok';

$daysEnd = htmlspecialchars($_GET["dE"]);
$daysStart = htmlspecialchars($_GET["dS"]);
$dateEnd =  htmlspecialchars($_GET["dateEnd"]);
$dateStart =  htmlspecialchars($_GET["dateStart"]);

$absDates = htmlspecialchars($_GET["dAbs"]);

$aggregate = htmlspecialchars($_GET["aggr"]); 

$sensors = array(); //empty array for sensor names

if(isset($_GET['s'])) { //sensors set?
    $one=$_GET['s'];
    foreach ($one as $a=>$value) {
        array_push($sensors,$value); //fetch list of requested sensors 
    }
} else {
    $validrequest = 0;
    $message = 'no sensors selected';
}

//start and end of time interval (unix epoch)
if ($absDates == 1) {
    $tsStart = strtotime($dateStart);
    $tsEnd = strtotime($dateEnd) + 3600*24;
}
else {
    $tsStart = time()-$daysStart*3600*24;
    $tsEnd   = time()-$daysEnd*3600*24;
}
if ($tsEnd<=$tsStart) {
    $validrequest = 0;
    $message = 'invalid dates selected';
}



if ($validrequest == 1) {
    // string containing column names
    $sensorstring = '';
    $sensorstringAggr = '';

    //check requested sensors, direct from database or calculated?
    $sensorsDB = array("dateTime"); //always include dateTime

    foreach ($sensors as $sens) {
        //add columns needed to calculate the virtual sensors
        if ($sens === "rainCumulative") {
            array_push($sensorsDB,"rain");
        }
        elseif ($sens === "sunHeating") {
            array_push($sensorsDB,"outTemp");
            array_push($sensorsDB,"extraTemp3");
        }
        else {
            array_push($sensorsDB,$sens);
        }
    }

    //remove duplicates, don't request things twice..
    $sensorsDB = array_unique($sensorsDB);

    //build string of database names
    foreach ($sensorsDB as $sens) {
        //no averaging
        $sensorstring = $sensorstring . $sens . " AS " . $sens . "Ren,";
        //with averaging
        if ($sens === "rain" OR $sens === "hail") {
            // rain and hail should be summed, not avaraged
            $sensorstringAggr = $sensorstringAggr . "SUM(" . $sens . ") AS " . $sens . "Ren,";
        }
        else {
            $sensorstringAggr = $sensorstringAggr . "AVG(" . $sens . ") AS " . $sens . "Ren,";
        }
    }
    $sensorstring = rtrim($sensorstring, ","); //remove extra comma at the end
    $sensorstringAggr = rtrim($sensorstringAggr, ","); //remove extra comma at the end

    if ($aggregate === "auto") {
        $nbrDays = ($tsEnd - $tsStart)/(3600*24);
        if ($nbrDays >= 31) { //aggregate 1 day
            $aggregate = "1day";
        }
        elseif ($nbrDays >= 14) { //aggregate 4 hours
            $aggregate = "4hours";
        }
        elseif ($nbrDays >= 2) { //aggregate 1 hour
            $aggregate = "1hour";
        }
    else { //no aggregation
        $aggregate = "none";
    }
}


    $dbquery = "SELECT " . $sensorstringAggr . " FROM archive WHERE dateTime>" . $tsStart . " AND dateTime<" . $tsEnd; 
    if ($aggregate === "1day") { //aggregate 1 day
        $groupby = "GROUP BY strftime('%Y-%m-%d', dateTime, 'unixepoch')";
    }
    elseif ($aggregate === "1hour") { //aggregate 1 hour
        $groupby = "GROUP BY strftime('%Y-%m-%dT%H', dateTime, 'unixepoch')";
    }
    elseif ($aggregate === "4hours") { //aggregate 4 hours
        //$groupby = "GROUP BY strftime('%Y-%m-%dT%H:00:00.000', 4*3600*(dateTime/(4*3600)),'unixepoch')"; //this also works
        $groupby = "GROUP BY strftime('%Y-%m-%dT', dateTime,'unixepoch') || (strftime('%H', dateTime, 'unixepoch')/4)";
    }
    else { //no aggregation
        $dbquery = "SELECT " . $sensorstring . " FROM archive WHERE dateTime>" . $tsStart . " AND dateTime<" . $tsEnd;
        $groupby = "";
    }


    $statementstr = $dbquery . " " . $groupby . ";";

    //open sqlite3 database
    $db = new SQLite3($database, SQLITE3_OPEN_READONLY); 

    //run query
    $statement = $db->prepare($statementstr);
    $result = $statement->execute();

    //create an array to store the data
    $data = array();

    //push in arrays for each column from database query
    foreach ($sensorsDB as $sens) {
        array_push($data[$sens], array());
    }

    //get units for the sensors
    $units = array();
    foreach ($sensorsDB as $sens) {
        $units[$sens] = $sensorList[$sens];
    }

    //get the data from query result and convert to metric,
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { //loop for each row in sqlite output
        foreach ($sensorsDB as $sens) {
            //get each sensor value
            $data[$sens][] = makeMetric($row[$sens . "Ren"],$units[$sens]);
        }
    }

    //copy data to output structure, calculate virtual sensors as needed
    $dataout["dateTime"] =  $data["dateTime"];
    $sensorsout = array("dateTime");
    $unitsout = array();
    $unitsout["dateTime"] = $sensorList["dateTime"];
    foreach ($sensors as $sens) {
        array_push($sensorsout,$sens);
        $unitsout[$sens] = $sensorList[$sens];
        //check if sens must be calculated
        if ($sens === "sunHeating") {
            $tempdiff =  arraySubtract($data["extraTemp3"],$data["outTemp"]); //calculate difference
            $dataout[$sens] = arrayAddConstant($tempdiff,1.3); //add offset
        }
        elseif ($sens === "rainCumulative") {
            $dataout[$sens] = cumsum($data["rain"]); //cumulate rain
        }
        else {
            $dataout[$sens] = $data[$sens]; //just copy
        }
    }

    //status message, not yet used for anything..
    //$message = 'ok';
    } //end if validrequest

//generate output json
echo json_encode(array('sensors' => $sensorsout, 'units' => $unitsout, 'data' => $dataout, 'message' => $message));

?>

