<?php

set_time_limit(0);
	
	//vars 
	$frontier = new SplPriorityQueue();
	$frontier->setExtractFlags(SplPriorityQueue::EXTR_BOTH); //to extract priority too
	$visited = array();
    $inQueue = array();
	$seed = "http://www.um.edu.mt/about/academic/faculties";
	
	//init
	$dom = new DOMDocument('1.0');
	@$dom->loadHTMLFile($seed);
	$links = $dom->getElementsByTagName('a');

    
    //Initialize queue from the seed
	foreach ($links as $element) 
	{
		$href = $element->getAttribute('href'); 
		str_replace("http://","",$href);
		str_replace("https://","",$href);
		$text = $element->nodeValue;
        if(strpos($href, "www.um.edu.mt")!==FALSE){
            initPrioritise($href, $text);
        }
		
		
	} 

    
	
    
	//crawling 
	while(!$frontier->isEmpty()){
        $next = getNext();
		$url = $next['data'];
		$p = $next['priority'];
		unset($inQueue[$url]);

		//download page
        $dom2 = new DOMDocument('1.0'); 
		@$dom2->loadHTMLFile($url);
		// md5() takes the document as a string as an argument, not its URL
		$visited[$url]=true;
        
		//if p = 7 save 
		if ($p == 7) {
            $dom2->saveHTMLFile("profiles\\".preg_replace('~[/:.]~', '_', $url) .".txt");
		}
		
		//extract links 
		$linkSet = $dom2->getElementsByTagName('a');
		
        foreach ($linkSet as $l) {
              $href = $l->getAttribute('href'); 
		      str_replace("http://","",$href);
		      str_replace("https://","",$href);
		      $text = $l->nodeValue;
              if(strpos($url, "www.um.edu.mt")!==FALSE){
                  prioritise($href, $p, $text);
              }
              
        }

	}
	

	function prioritise($url, $p, $text){

		global $visited;
		$lower = strtolower($text);
				
		if(!isset($visited[$url])&& !isset($inQueue[$url]))
		{
			if (((strpos($url, 'contact' )!== FALSE)||(strpos($url, 'profile'))!== FALSE)) {
				add2Front($url, 7);
			}
			
			elseif( (strpos($url, 'staff') !== FALSE)){
				add2Front($url, 6);
			}
			elseif (((strpos($lower, 'faculty' )!== FALSE)||(strpos($lower, 'department'))!== FALSE)) {
				add2Front($url,  5);
			}
            elseif( (count(explode("/", $url)) <= 3) && $p > 0){
				add2Front($url,  ($p -1));
			}
            else{
                $visited[$url] = TRUE;
                return;
            }
            $inQueue[$url] = TRUE;
		}	
		
	}
	
	function initPrioritise($url, $text){	
		$lower = strtolower($text);
		
		if((strpos($url, 'contact' )!== FALSE)||(strpos($url, 'profile')!== FALSE) ){
			add2Front($url, 7);
		}
		elseif (strpos($url, 'staff')!== FALSE) {
			add2Front($url, 6);
		}
        elseif ((strpos($lower, 'faculty' )!== FALSE)||(strpos($lower, 'department'))) {
			add2Front($url, 5);
		}
		elseif (count(explode("/", $url)) <= 3) {
			add2Front($url, 4);
		}
        else{
            $visited[$url] = TRUE;
            return;
        }
        $inQueue[$url] = TRUE;
		
	}
	
	function add2Front($url, $priority){
		//TODO: add to frontier ccording to priority
		global $frontier;		
		$frontier->insert($url,$priority);
	}
	
	function getNext(){
		global $frontier;
		
		// returns an associative array with 'data' and 'priority' as keys
		return $frontier->extract();
	}
?>