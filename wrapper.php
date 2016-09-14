<?php
function extract_info($doc_as_str, $xml_frag) {
	global $docSet;
    global $fileName;

	$tag = $xml_frag->elementType; // e.g. div, h2  
	$id = (string)$xml_frag->elementId; // the value of the id attribute if it exists
    
	if (!empty($id)) {
		$doc_as_str = $doc_as_str->getElementById($id);
	} else {
		$offset = (int)$tag['offset'];
		$doc_as_str = $doc_as_str->getElementsByTagName($tag)[$offset];
	}
    
    $type = $xml_frag->action->attributes()->type;
    
	if ((int)$type===1) {
        
        if(empty($doc_as_str)) {return -1;}
        
        $xml_frag = $xml_frag->action->rule;
		return extract_info($doc_as_str, $xml_frag);
	}elseif ((int)$type===2) {
        
        $tagset = (string)$xml_frag->action->elementType;
        $filterstring = (string)$xml_frag->action->filter;
        
        $vals = array();
        if(empty($doc_as_str)){return -1;}
        $elementset = $doc_as_str->getElementsByTagName($tagset);
        
        if(empty($elementset)){return -1;}
        
        foreach ($elementset as $e) {
            if(!empty($e->nodeValue)){
                
                $nodeval = (string)$e->nodeValue;
               
                if(preg_match($filterstring, $nodeval)){
                    
                    array_push($vals,$nodeval);
                }
            }
        }
        return array((string)$xml_frag->action['key'] => $vals);
    } 
    else {
        
        if(empty($doc_as_str)) {return -1;}
        
		return array((string)$xml_frag->action['key'] => trim((string)$doc_as_str->nodeValue));
	}
    
    
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////
echo("<pre>");
$xml = simplexml_load_file("rules.xml");    

////vars 

$docSet = array();
$warnings = array();
$NameList = array();

//vars end
$dir = new DirectoryIterator(dirname(__FILE__)."/profiles");
foreach ($dir as $fileinfo) {
    if ($fileinfo->isfile()) {
        $fileName = $fileinfo->getFilename();
        $docSet[$fileName] = array();

        $dom = new DOMDocument('1.0');
        @$dom->loadHTMLFile("profiles\\".$fileName);
        
        foreach($xml->rule as $rule) {
            
            $ret = extract_info($dom, $rule);
            if ($ret === -1){
                if(isset($warnings[$fileName])){
                    array_push($warnings[$fileName], (string)$rule['name']);
                }else {
                    $warnings[$fileName] = array((string)$rule['name']);;
                }
                
            }
            else{
                $docSet[$fileName] = array_merge($docSet[$fileName],$ret);
            }

            
        }
    }

}



$titles = array("Dr", "Esq", "Hon", "Jr", "Mr", "Mrs", "Ms", "Messrs", "Mmes", "Msgr", "Prof", "Rev", "Rt Hon", "Sr", "St", "Ing", "miss", "dott");
$pattern = "((\p{Lu}\p{Ll}+( |-))*\p{Lu}\p{Ll}+, (\p{Lu}\.)+|(([A-Z]\.)+\s\p{Lu}\p{Ll}+)|((\p{Lu}\p{Ll}+\s)\p{Lu}{2,})|(((\w+,\s)([A-Z\.])+\.)))u";


foreach ($docSet as $id => $details) {

    
    if(!isset($details["Personal Name"])){
         unset($docSet[$id]);
    }else{
            $rawName = explode(" ",preg_replace("/[^a-z0-9]+/i"," ",$details["Personal Name"]));
            $nameComponents = array_values( array_diff($rawName,$titles));
            $docSet[$id]["Title"] = implode(" ", array_intersect($titles,$rawName));
            $docSet[$id]["Personal Name"] = implode(" ", $nameComponents); 
            
            //patterns to find the current author in list of authors
            $temp = array_slice($nameComponents, 0, count($nameComponents)-1);
            $initials = "";
            foreach ($temp as $nc) {
                $initials = $initials.strtoupper($nc[0]).".";
            }
            $variant1 = $nameComponents[0]." ".strtoupper(implode(" ", array_slice($nameComponents, 1, count($nameComponents)-1)));
            $variant2 = $initials." ".$nameComponents[count($nameComponents)-1];
            $variant3 = $nameComponents[count($nameComponents)-1].", ".$initials;    
            $variant4 = $initials." ".strtoupper($nameComponents[count($nameComponents)-1]);
            $variant5 = strtoupper($nameComponents[count($nameComponents)-1]).", ".$initials;    
            $variant6 = $nameComponents[count($nameComponents)-1]." ".preg_replace("$\.$","", $initials); 
            $variant7 = $nameComponents[count($nameComponents)-1].", ".$temp[0][0]."."; 
            $namePattern = "(".$variant1."|".$variant2."|".$variant3."|".$variant4."|".$variant5."|".$variant6."|".$variant7.")";
            

            if(!isset($details["Publications"])){
                unset($docSet[$id]);
            }elseif(count($details["Publications"])==0){
                unset($docSet[$id]);
            }
            else{
                //temp to store coAuthors 
                $tempTally = array();   
                //extracting author info
                foreach ($details["Publications"] as $p => $pub) {
                    
                    $datematch = array();
                    preg_match('/(\d\d\d\d,)|"/', $pub, $datematch, PREG_OFFSET_CAPTURE);
                    
                    
                    $dateindex = (int)$datematch[0][1];
                    $matchIndex = $dateindex -1;

                    
                    $matches = array();
                    preg_match_all($pattern, substr($pub,0,$matchIndex), $matches);
                    $auths = $matches[0];

                    
                    $docSet[$id]["Publications"][$p] = array("Pub" => $pub, "Info" => substr($pub, $dateindex), "Authors" => $auths);
                    
                    foreach ($auths as $a) {
                        if(preg_match($namePattern, trim($a))){
                            $NameList[$a] =$id;
                        }elseif(count($auths) == 1){
                            $NameList[$a] =$id;
                        }
                        else{
                            if(isset($tempTally[$a])){
                                $tempTally[$a]++;
                        }
                            else{
                                $tempTally[$a] = 1;
                            }
                        }
                        

                    }
                    
                    //setting the tally to the persons object 
                    $docSet[$id]["CoAuthors"] = $tempTally;
                    
                }
            }


    }
    


    
    

    


}
file_put_contents("docset.json", json_encode($docSet)) ;
file_put_contents("namelist.json", json_encode($NameList)) ;


print_r($docSet)."<br>";
#print_r(count($docSet));
#print_r($NameList);
#print_r(count($NameList));


?>