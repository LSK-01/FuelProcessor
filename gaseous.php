<?php

$workingDirectory = '/Users/god/Desktop/tjb';
$newFuelFilesDir = "$workingDirectory/newFuelPrices";
$fuelStatistics = "$workingDirectory/fuelStatistics.csv";
$fuelPriceFilesPrefix = "fuelPrices";
$fuelPricesFile = "$workingDirectory/$fuelPriceFilesPrefix-" . date('Y') . ".csv";
//each object referenced by airport name,
//each has the keys 'location' for country, an array of 'prices' of each month, and the 'averages' for the 'prices' of each month 
$airportObjects = []; 

function getObjects($CSVFile){
    $expiredAirports = [];
    $arrayStore = [];

    if (($h = fopen($CSVFile, "r")) !== FALSE) 
    {
      while (($data = fgetcsv($h, 1000, ",")) !== FALSE) 
      {
          if(array_key_exists(3, $data) && array_key_exists(4, $data) && array_key_exists(7, $data)){
            $discountInt = $data[3];
            //this value has 265 and 1 - 265 is the doo doo discount value so dont use that -_-
            if ($discountInt == 1){
                //some prices are expired and have values of 99999
                if(intval($data[4]) > 999){
                    $airport = $data[0];
            
                    if(!in_array($airport, $expiredAirports)){
                        $expiredAirports[] = $airport;
                    }
                    
                }
                else{
                    $airport = $data[0];
                    $price = floatval($data[4]);
                    $date = $data[7];
                    $date = explode("-", $date);
                    $month = $date[1];
                    $month = date('n', strtotime($month));
                    
                    //see if existing airport obj for this airport - ie. were just pushing another price
                    if(array_key_exists($airport, $arrayStore)){
                        if(array_key_exists($month, $arrayStore[$airport]['prices'])){
                            //$arrayStore[$airport]['prices'][$month][] = $price;
                            array_push($arrayStore[$airport]['prices'][$month], $price);
                        }
                        else{
                            $arrayStore[$airport]['prices'][$month][] = $price;
                        }
                    }
                    else{
                        //create new obj
                        $newAirportObject = ['location' => $data[1], 'prices' => [
                            $month => []
                        ]];
        
                        array_push($newAirportObject['prices'][$month], $price);
                        //push new airport object 
                        $arrayStore[$airport] = $newAirportObject;
                 }            
                }
            }
          }
        
      }
      fclose($h);
    //  print("\n Airports with expired prices: ");
       // print_r($expiredAirports);
      //calculate averages
      foreach($arrayStore as $airport => $values){
        foreach($values['prices'] as $month => $prices){
            $total;
            $number;
            if (is_array($prices)){
                $total = array_sum($prices);
                $number = count($prices);
            }
            else{
                $total = $prices;
                $number = 1;
            }
            $arrayStore[$airport]['averages'][$month] = $total/$number;
        }
      }
      return $arrayStore;
    }
    exit();
}

//download any new email attachments
shell_exec("php getNewFuelPrices.php");
//get all the downloaded files
$newFuelFiles = glob("$newFuelFilesDir/*");
//get the fuel price files containing all the prices from each year
$fuelPricesFiles = glob("$workingDirectory/*");

if (count($newFuelFiles) > 0) {

    print("\n found new fuel prices file - updating \n");
    $newFiles = [];

    //sort the files into the year they were sent - should all be the same, but just in case its not
    foreach($newFuelFiles as $file){
        $filename = pathinfo($file);
        $filename = $filename['filename'];
        $filename = explode("-", $filename);
        $year = $filename[1];


        if(array_key_exists($year, $newFiles)){
            array_push($newFiles[$year], $file);
        }
        else{
            $newFiles[$year][] = $file;
        }
    }
    print_r($newFiles);
  
        //iterate through the arrays of x year files
        foreach($newFiles as $year => $files){
            //the current main database file for whatever year we are currently iterating through
            $fuelPricesFileCurrent = "$workingDirectory/$fuelPriceFilesPrefix-$year.csv";
            $newFile = "";

            //merge all the files into one file
            foreach($files as $file){
                if(!empty($newFile)){
                    $content = file_get_contents($file);
                    file_put_contents($newFile, $content, FILE_APPEND);
                    unlink($file);
                }
                else{
                    $newFile = $file;
                }   
            }
            
            //check if  a .csv database for files of whatever year we are currently on exists
            //if not, create the file and populate it with the new prices, if it does, concatenate the new prices
            if(!file_exists($fuelPricesFileCurrent)){
                shell_exec("touch $fuelPricesFileCurrent");
                shell_exec("mv $newFile $fuelPricesFileCurrent");
            }
            else{
                $deprecated = "$workingDirectory/deprecatedFuelPrices.csv";
                shell_exec("mv $fuelPricesFileCurrent $deprecated");
                shell_exec("cat $deprecated $newFile > $fuelPricesFileCurrent");
                
                //delete deprecated files 
                unlink($deprecated);
                unlink($newFile);
            }
        }
        print("\n updated fuel prices database $fuelPricesFileCurrent \n");
     
    }

if(!file_exists($fuelPricesFile)){
    //just use data from last years file until we get a new year update
    print("using last years file - no file from this year found \n");
    $fuelPricesFile = "$workingDirectory/$fuelPriceFilesPrefix-" . (intval(date('Y')) - 1) . ".csv";
    if(!file_exists($fuelPricesFile)){
        exit();
    }
}
//
$airportObjects = getObjects($fuelPricesFile);
$output = print_r($airportObjects, true);
file_put_contents("analyse.txt", $output);

file_put_contents($fuelStatistics, '');
$file = fopen($fuelStatistics, 'w');
//headings
fputcsv($file, array('Location', 'Airport', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'));

foreach($airportObjects as $airport => $object){

    unset($object['prices']);

    $largestIndex = 0;
    foreach($object['averages'] as $date => $average){

    $index = intval($date) + 1;
    $object[$index] = $average;

    if ($index > $largestIndex){
        $largestIndex = $index;
    }
    }
    unset($object['averages']);

    //fill with blank cells if no data for other months
    for($i = 2; $i <= 13; $i++){
        if(!array_key_exists($i, $object)){
            $object[$i] = "no data";
        }
    }

    $object[0] = $object['location'];
    unset($object['location']);
    $object[1] = $airport;
   
    ksort($object);

    fputcsv($file, $object);

}

print("\n written info to $fuelStatistics \n");


   


