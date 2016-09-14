<?php

//function to populate a list of the abbreviations of names and their respective dblp page
//made to work wtih the requests per second limitations of the dblp api.

    $jsonString = file_get_contents("namelist.json");
    $nameList = json_decode($jsonString,true);
    
    $jsonString = file_get_contents("dblp.json");
    $dblpList = json_decode($jsonString,true);
    
    $jsonString = file_get_contents("docset.json");
    $docset = json_decode($jsonString);
    
    $counter = 0;
    $bigCounter = 0;
    
    foreach($docset as $key=>$value) //for each author
    {
        $bigCounter++;
        if($bigCounter<850) continue;
        
        if(property_exists($value,"CoAuthors"))
        {
            foreach($value->{'CoAuthors'} as $shortName=>$val) //for each coauthor
            {
                if(array_key_exists($shortName,$nameList))
                {
                    //doNothing
                }
                else if(array_key_exists($shortName,$dblpList))
                {
                    //also do nothing
                }
                else if($counter<100)
                {
                    $counter++;
                    $xml = simplexml_load_file("http://dblp.uni-trier.de/search/author?xauthor=".$shortName) or die("Error: Cannot create object");
                    if(!empty($xml->author))
                    {
                        $whatWeWant = $xml->author[0]->attributes()[0];
                        $dblpList[$shortName] = "http://dblp.uni-trier.de/pers/hd/".$whatWeWant;
                    }
                }
            }
        }
    }
    
    $fp = fopen('dblp.json', 'w');
	fwrite($fp, json_encode($dblpList));
	fclose($fp);
    
    echo "<pre>";    
    print_r($dblpList);
?>