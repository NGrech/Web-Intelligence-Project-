<?php

    //initialization

    set_time_limit(0);
    
    $jsonStringNames = file_get_contents("namelist.json");
    $jsonStringDoc = file_get_contents("docset.json");
    
    $authorFinder=json_decode($jsonStringNames);
    
    $jsonStringDBLP = file_get_contents("dblp.json");
    $dblpFinder = json_decode($jsonStringDBLP);

    //rdf generation
    
    $publicationList = array(); //publications will be stored here to be transformed to RDF
    $counter = 0;
    
    $rdfAuthors = createRDFAuthors($jsonStringDoc);
    
    $fp = fopen('authors.rdf', 'w');
    fwrite($fp, $rdfAuthors);
    fclose($fp);
    
    $rdfPublications = createRDFPublications($publicationList);
  
    $fp = fopen('publications.rdf', 'w');
    fwrite($fp, $rdfPublications);
    fclose($fp);
    
    echo "Done.";
    
    function fixLink($link)
    {
        $link = str_replace("___","://",$link); //fix dashes
        $link = str_replace("www_um_edu_mt","www.um.edu.mt",$link);
        $link = str_replace("_","/",$link);
        $link = substr($link, 0, -4); //cut .txt
        
        return $link;
    }
    
    function createRDFAuthors($jsonString)
    {
        global $publicationList;
        
        $rdfString = "<?xml version=\"1.0\" encoding=\"US-ASCII\"?> \n".
            "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" \n".
            "xmlns:um=\"http://www.nlm.com/am/schema.txt#\"\n".
            "xmlns:dblp=\"http://dblp.uni-trier.de/rdf/schema-2015-01-26#\">\n";
        $jsonArray = json_decode($jsonString);
        
        foreach($jsonArray as $key => $value)
        {
            //key is the profile link
            $link = fixLink($key);
            $rdfString.="   <dblp:Person rdf:about=\"".$link."\">\n"; //start author description
            $rdfString.="       <dblp:primaryFullPersonName>".$value->{'Personal Name'}."</dblp:primaryFullPersonName>\n"; //personal name
            $rdfString.="       <um:title rdf:datatype=\"http://www.w3.org/2001/XMLSchema#string\">".$value->{'Title'}."</um:title>\n"; //title
            if(property_exists($value,"Qualifications"))
            {
                $rdfString.="       <um:qualifications rdf:datatype=\"http://www.w3.org/2001/XMLSchema#string\">".$value->{'Qualifications'}."</um:qualifications>\n"; //qualifications
            }
            if(property_exists($value,"Faculty"))
            {
                $rdfString.="       <um:faculty rdf:datatype=\"http://www.w3.org/2001/XMLSchema#string\">".str_replace("&","&amp;",$value->{'Faculty'})."</um:faculty>\n"; //faculty
            }
            
            if(property_exists($value,"Publications"))
            {
                foreach($value->{'Publications'} as $key=>$publication)
                {
                    //invent a new publication in the list of createRDFPublications
                    $pubKey = md5($publication->{'Info'});
                    $publicationList["http://www.publications.com/".$pubKey]=$publication;
                    
                    $rdfString.="       <dblp:authorOf rdf:resource = \"http://www.publications.com/".$pubKey." \"/>\n";
                    
                }
            }
            if(property_exists($value,"CoAuthors"))
            {
                foreach($value->{'CoAuthors'} as $author=>$freq)
                {
                    $rdfString.="       <dblp:coCreatorWith rdf:resource = \"".getAuthorUrl(trim($author))." \"/>\n";
                }
                $rdfString.="   </dblp:Person>\n"; //end author description
            }
        }
        
        $rdfString.="</rdf:RDF>\n";
        return $rdfString;
    }
    
    
    function createRDFPublications($publist)
    {

        $rdfString = "<?xml version=\"1.0\" encoding=\"US-ASCII\"?> \n".
            "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" \n". 
            "xmlns:um=\"http://www.nlm.com/am/schema.txt#\"\n".
            "xmlns:dblp=\"http://dblp.uni-trier.de/rdf/schema-2015-01-26#\">\n"         
            ;
        
        
        foreach($publist as $key => $value)
        {
            $rdfString.="   <dblp:Publication rdf:about=\"".$key."\">\n";
            $rdfString.="       <um:info>".$value->{'Info'}."</um:info>\n";
            
            foreach($value->{'Authors'} as $key=>$authorValue)
            {
                $rdfString.="       <dblp:authoredBy rdf:resource = \"".getAuthorUrl(trim($authorValue))." \"/>\n";
            }
            $rdfString.="   </dblp:Publication>\n";
        }
        
        $rdfString.="</rdf:RDF>\n";
        return $rdfString;
    }
    
    function getAuthorUrl($shortName)
    {
        global $authorFinder;
        global $counter;
        global $dblpFinder;
        if(array_key_exists($shortName,$authorFinder))
        { //first check if the author exists in the university website
            return fixLink($authorFinder->{$shortName});
        }
        else if(array_key_exists($shortName,$dblpFinder))
        {  //else chek if the author exists in the dblp website
            return $dblpFinder->{$shortName};
            
        }
        else
        {  //else give it a uniqe id
            return "http://www.unattributedauthors/".md5($shortName);
        }
    }
    
?>
