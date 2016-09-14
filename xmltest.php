<?php
    $shortName="ommok";
    $xml = simplexml_load_file("http://dblp.uni-trier.de/search/author?xauthor=".$shortName) or die("Error: Cannot create object");
    echo "<pre>";
    print_r($xml);
    
    if(!empty($xml->author))
    {
        $whatWeWant = $xml->author[0]->attributes()[0];
        echo "http://dblp.uni-trier.de/pers/hd/".$whatWeWant;
    }
    else
    {
        echo "or nah";
    }   
    

?>