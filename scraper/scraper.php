<?php

$workingDirectory = '/Users/god/Desktop/tjb/scraper';
require "$workingDirectory/htmlParserFunctions.php";

$storeValues = "$workingDirectory/aircraft.csv";

//paste a link from privatefly.com/private-jets/xxx.html to scrape the jets from their
$urls = ["LightJets" => "https://www.privatefly.com/private-jets/small-jet-hire.html",
 "MediumJets"=>"https://www.privatefly.com/private-jets/medium-jet-hire.html",
  "LongRangeJets"=>"https://www.privatefly.com/private-jets/longrange-jet-hire.html",
   "RegionalAirliners"=>"https://www.privatefly.com/private-jets/regional-airliner-hire.html",
    "LargeAirliners"=>"https://www.privatefly.com/private-jets/large-airliner-hire.html"
];
$file = fopen($storeValues, 'w');
fputcsv($file, array('Title', 'Image Link', 'Type',  'Seats', 'Speed', 'Range', 'Price Per Hour'));

foreach($urls as $type => $url){
    $dom = file_get_html( $url );

    $aircraft = [];
    
    foreach($dom->find('h4[class="aircraft-listing-results__title"]') as $title) {
     $aircraft[] = ['title' => $title->plaintext, 'details' => [], 'image' => ''];
    }
    
    $countInfo = 1;
    $countAircraft = 0;
    foreach($dom->find('dd') as $detail) {
        
       array_push($aircraft[$countAircraft]['details'], $detail->plaintext);
    
        if($countInfo % 4 == 0){
            $countAircraft ++;
        }
        $countInfo ++;
       }
    
       $countAircraft = 0;
       foreach($dom->find('img[class="img img-responsive"]') as $img) {
        $aircraft[$countAircraft]['image'] = $img->src;
        $countAircraft++;
       }
    
    
       $countAircraft = 0;
    
       
       foreach($aircraft as $aeroplane){
           $aeroplane['type'] = $type;
    $aeroplane['seats'] = $aeroplane['details'][0];
    $aeroplane['speed'] = $aeroplane['details'][1];
    $aeroplane['range'] = $aeroplane['details'][2];
    $aeroplane['price'] = preg_replace("/[^0-9]/", "", $aeroplane['details'][3]);
    unset($aeroplane['details']);
    
    if($aeroplane['title'] != null){
        fputcsv($file, $aeroplane);
    }
       }
       
     
    
}
fclose($file);
