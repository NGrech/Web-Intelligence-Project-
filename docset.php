<?php
echo("<pre>");
$string = file_get_contents("C:\\Users\\Matthew\\Downloads\\docset.json");
$json_a = json_decode($string, true);
$namelist = json_decode(file_get_contents("C:\\Users\\Matthew\\Downloads\\namelist.json"), true);
$coAuthorsList = array();

$staffMembers = array();
$keys = array();
$faculties = array();
$arr = array(); // author indices as keys, {coAuthIndex => noOfPubs} as objects

// extract faculties
foreach ($json_a as $author) {
	if (!empty($author['Faculty'])) {
		$faculty = $author['Faculty'];
		if (!in_array($faculty, $faculties)) {
			array_push($faculties, $faculty);
		}
	}
}

$n_faculties = count($faculties);
// echo($n_faculties) . "<br />";
$flipped = array_flip($faculties);
$ids = array();

foreach ($json_a as $key => $author) {
	addToStaffMembers($author, $key);
}

function addToStaffMembers($author, $key) {
	global $keys; // processed items keys
	global $n_faculties;
	global $namelist;
	global $flipped; // maps faculty names to their index
	global $staffMembers;
	global $coAuthorsList;
	global $ids;
	global $json_a;
	global $arr;
	
	
	preg_match('/_([^_]+)\./', $key, $matches); // /[profile|contact]_([^_]+)\./
	$match = $matches[1];
	if (!in_array($match, $keys)) {
		// if profile OR contact and profile exists
		if (preg_match('/profile_' . $match . '\.txt$/', $key) || // /e_
		(preg_match('/contact_' . $match . '\.txt$/', $key) && // /t_
		!(isset($json_a["http___www_um_edu_mt_profile_" . $match . ".txt"]) ||
		isset($json_a["https___www_um_edu_mt_profile_" . $match . ".txt"])))) {
			array_push($keys, $match);
			if (!empty($author['Faculty'])) {
				$faculty = $flipped[$author['Faculty']];
			} else {
				$faculty = $n_faculties;
			}
			$cweight = 0;
			if (!empty($author['CoAuthors'])) {
				$n_ca = count($author['CoAuthors']);
 				$cname = (!empty($author['Title'])) ? 
							$author['Title'] . " " . $author['Personal Name'] : 
							$author['Personal Name']; 
				foreach ($author['CoAuthors'] as $coauthor=>$weight) {
					if (isset($namelist[$coauthor])) {
						addToStaffMembers($json_a[$namelist[$coauthor]], $namelist[$coauthor]);
						
						$coname = (!empty($json_a[$namelist[$coauthor]]['Title'])) ? 
									$json_a[$namelist[$coauthor]]['Title'] . " " . $json_a[$namelist[$coauthor]]['Personal Name'] : 
									$json_a[$namelist[$coauthor]]['Personal Name'];
						
						$arr[$cname][$coname] = $weight;
					} else {
						$resolvedName = nameResolution($coauthor);
						if (!isset($staffMembers[$resolvedName])) {
							$staffMembers[$resolvedName] = array(
								"name" => $coauthor, 
								"group" => $n_faculties + 1, 
								"size" => $weight
							);
						} else {
							$staffMembers[$resolvedName]["size"] += $weight;
						}
						$arr[$cname][$resolvedName] = $weight;
						$cweight += $weight;
					}
				}
			} 
			$staffMembers[$author['Personal Name']] = array(
				"name" => (!empty($author['Title'])) ? 
					$author['Title'] . " " . $author['Personal Name'] : 
					$author['Personal Name'], 
				"group" => $faculty, 
				"size" => $cweight
			);
			array_push($ids, $key);
		} 
		
		
	}
}

$links = array();
$ids = array_flip($ids); // ids now maps from key (filename) to index


// extract links
foreach ($arr as $author => $coauthors) {
	foreach ($coauthors as $coauthor => $n_pub) {
		if(isset($staffMembers[$coauthor])){
			$name = $staffMembers[$coauthor]['name'];
		}
		else{
			$name = $coauthor;
		}
		
		if (isset($arr[$coauthor][$author]) && $arr[$coauthor][$author] > $n_pub) {
			continue;
		} else {
			array_push($links, array("source" => $author, "target" => $name, "value" => $n_pub));
		}
	}
}

print_r($links);
print_r($staffMembers);

file_put_contents("acad_pubs.json", json_encode(array("nodes" => array_values($staffMembers), "links" => $links)));

// echo count($staffMembers) . "<br />";
// echo count(array_unique($staffMembers));
// echo $cnt;
// var_dump($matches[1]);
// echo count($matches[1]) . "<br />";
// $arr = array_flip($matches[1]);
// echo count($arr);
// print_r($arr);
// echo count($namelist);

function nameResolution($name){

    $namecomponents =explode(' ',trim(preg_replace('/\.|,\s/', ' ', $name))); 
    // print_r($namecomponents);

    if(strlen($namecomponents[0]) == 1){ //case starts with initial
    // echo "case 1";
        $newName = $namecomponents[count($namecomponents)-1];
         if(strlen($namecomponents[count($namecomponents)-1])>1){
                   $newName = $newName.$namecomponents[1][0];
                }
                else{
                    $newName = $newName.$namecomponents[1];
                } 
        return $newName;
        
    }else{
        
        if(strlen($namecomponents[count($namecomponents)-1]) >= 2){ // case ends with last name
            $newName = $namecomponents[count($namecomponents)-1]; 
            if(strlen($namecomponents[count($namecomponents)-1])>1){
                // echo "case 2";
                   $newName = $newName.$namecomponents[0][0];
                }
                else{
                    $newName = $newName.$namecomponents[0];
                } 
            return $newName;
            
        }
        else{ // case ends with initial 
            $newName = $namecomponents[0];
                // echo "case 3";
                
            if(strlen($namecomponents[count($namecomponents)-1])>1){
                    $newName = $newName.$namecomponents[1][0];
                }
                else{
                    $newName = $newName.(string)$namecomponents[1];
                } 
            return $newName;
        }
    }

}
?>