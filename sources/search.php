<?PHP
//------------------------------------------------------
//vMotion - Advanced Content Management Software
//	(C) 2007 vSoftware.org. All rights reserved.
//------------------------------------------------------

//-------------------------------------------------------------------------------------------------------------------------------------------------------------------
//NOTES ON CONFIGRUATION
//	--Most Configuration Values are Self Explanitory
//	--DB Structure is Simple || list the tables you want to crawl
//		--$scfg['table'][] = 'table1'; //this is the name of the table
//		--$scfg['column'][] = 'id'; //the column where the unique ids are location
//		--$scfg['link'][] = 'test{id}.html'; //where {id} will be automatically replaced by the current page unique id
//	--Thats it! Run a Fullindex And then update periodically. Happy Searching.
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------
//vSoftware Search Engine v0.2
//	--rewrote all search algorithms 
//	--crawler upgraded
//	--maintainability increased
//	--increased crawl and search speed
//	--added to exclude list
//	--added weight for keywords
//	--added strict title tag support
//	--added amount of keywords to index pp
//	--squashed lots of small bugs thx testers
//	--added support for cli verbose output
//--------------------------------------------------------
//vSoftware Search Engine v0.1
//	--completely standalone
//	--developed for a specific datbase
//	--plans for dynamic deployment
//	--plans for a non mysql based engine
//--------------------------------------------------------    

$scfg = array();
//Get Database Information For Crawling
$scfg['cfg_file'] 		= false; //only needed for crawling APPLIES TO VMOTION CONFIGRATUION FILES ONLY
$scfg['site_path'] 		= '/var/www/example/'; //with trailing slash
$scfg['site_url']		= 'http://www.example.com/'; //with trailing slash
$scfg['verbose']		= 'html';  //"html" "cli" or "false"
$scfg['titles'] 		= true; //strict use of title tags <title></title>
$scfg['keywords']		= '20'; //amount of keywords to index from content per page 0 unlmited
$scfg['search_limit'] 	= '10';	//results per page
$scfg['sleep']			= '1000'; //time between pages to reduce server load

//if no config file for db
$scfg['db']['host']  		= 'localhost';
$scfg['db']['name']  		= '';
$scfg['db']['user']  		= '';
$scfg['db']['pass']  		= '';
$scfg['db']['table'] 		= 'search';
$scfg['db']['properties'] 	= 'search_words';
$scfg['db']['error'] 		= '2';

//Tables To Search From
// {s} will load a special function to process the query it will look for a function
// in the class that has the same name as the table and skip otherwise

//Example Table 1
$scfg['table'][] 	= 'test_table';
$scfg['column'][] 	= 'id';
$scfg['link'][]		= 'test{id}.html';

//Example Table 2
$scfg['table'][] 	= 'table_test';
$scfg['column'][] 	= 'testid';
$scfg['link'][]		= 'index.php?view=true&page={id}';

//Copy and paste the 3 lines above to add another table

//Exclude Simple Words
$scfg['exclude'] = array(
	"a",
	"and",
	"the",
	"to",
	"from",
	"i",
	"we",
	"us",
	"them",
	".",
	"on",
	"for",
	"go",
	"is",
	"in",
	"do",
	"you",
	"can",
	"password?",
	"forgot",
	"create",
	"account",
	"will",
	"at",
	"submit",
	"as",
	"user",
	"rating",
	"na",
	"game",
	"rank",
	"please",
	"choose",
	"your",
	"of"
	);


//================================================================================================================
//END CONFIGURATION
//================================================================================================================
class search{

	var $info;
	var $start;
	var $end;
	var $queries;
	var $pages;
	var $error;

	//Load Config
	function search(){
	
		global $scfg;
		
		//Add in Database Configuration
		if(file_exists($scfg['cfg_file'])){
			include($scfg['cfg_file']);
			$scfg['db'] = $db;
			if(!isset($sql['db']['table'])){
				$scfg['db']['table'] = 'search';
			}
			if(!isset($sql['db']['table'])){
				$scfg['db']['properties'] = 'search_words';
			}
			if(!isset($sql['db']['type'])){
				$scfg['db']['type'] = 'mysql';
			}
			if(!isset($sql['db']['error'])){
				$scfg['db']['error'] = '2';
			}
			unset($db,$config);
		}
		
		$this->info = $scfg;
		$this->error = $this->info['db']['error'];
		unset($scfg);
	}
	
	//----------------------------------------
	//Strip HTML
	//----------------------------------------
	function striphtml($Document) {
		$Rules = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
		                '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
		                '@([\r\n])[\s]+@',                // Strip out white space
		                '@&(quot|#34);@i',					// Replace HTML entities
						'@(\'*)(\.*)(\$*)(\|*)(\!*)(\@*)(#*)(%*)(\^*)(&*)(\**)(\(*)(\)*)(-*)(_*)(-*)(=*)(\+*)(\<*)(\>*)(,*)(\/*)(;*)(:*)(`*)(~*)@si' //remove all special chars
						);            
		$Replace = array (' ',
		                  ' ',
		                  ' ',
		                  ' ',
						  '');
		  return preg_replace($Rules, $Replace, $Document);
	}
	
//================================================================
//Very lite Database Driver
//================================================================
	function db_connect($db){
		mysql_connect($db['host'],$db['user'],$db['pass']);
		mysql_select_db($db['name']);
		$this->queries = 0;
	}
	function db_query($query){
		$result =  mysql_query($query) or die($this->db_error($query,mysql_error()));
		$this->queries++;
		return $result;
	}
	function db_result($query,$func="mysql_fetch_array"){
		$result = $func($query);
		return $result;
	}
	function db_error($query,$error){
		//Display The error page.
		if($this->error == 2){
		
			$showquery = '<textarea rows="10" cols="80">'.$query.'</textarea><br />';
			
		}elseif($this->error == 1){
		
			$showquery = '';
			
		}
		else{
			$showquery = '';
		}
		
		$html = '
				<h1>Database Error</h1>
				<b>There was an error while processing the page.</b><br />
				Below is the query that was processed causing the error. And the feedback from database:<br />
				<i>You may want to contact the administrator of this site about the error.</i><br />
				'.$showquery.'				
				The database returned the following:<br />
				<textarea rows="4"	cols="80">'.$error.'</textarea><br />
			';
			
		if($this->error == 0){
			$html = $error;
		}
			

		
		echo $html;
		
	}
//=====================================================================
//End Database Driver
//=====================================================================

	//Weight of Keyword
	function weight($word){
		
		$weight = strlen($word);
		$word = wordwrap($word,1,' ',true);
		$word = explode(' ',$word);
	
		//Weight Forumula
		//thx scrabble
		$score = array(
			"0"		=>	"1",
			"1"		=>	"1",
			"2"		=>	"1",
			"3"		=>	"1",
			"4"		=>	"1",
			"5"		=>	"1",
			"6"		=>	"1",
			"7"		=>	"1",
			"8"		=>	"1",
			"9"		=>	"1",
			"a"		=>	"1",
			"b"		=>	"3",
			"c"		=>	"3",
			"d"		=>	"2",
			"e"		=>	"1",
			"f"		=>	"4",
			"g"		=>	"2",
			"h"		=>	"4",
			"i"		=>	"1",
			"j"		=>	"8",
			"k"		=>	"5",
			"l"		=>	"1",
			"m"		=>	"3",
			"n"		=>	"1",
			"o"		=>	"1",
			"p"		=>	"3",
			"q"		=>	"10",
			"r"		=>	"1",
			"s"		=>	"1",
			"t"		=>	"1",
			"u"		=>	"1",
			"v"		=>	"4",
			"w"		=>	"4",
			"x"		=>	"8",
			"y"		=>	"4",
			"z"		=>	"10" );
			
		foreach($word AS $key => $value){		
			if(!in_array($value,$score)){
				$add = 1;
			}
			else
			{
				$add = $score[$value];
			}			
			$weight = $weight + $add;			
		}
		
		$weight = $weight / 2;		
		$weight = round($weight);
		
		return $weight;
		
	}	

	//THe Crawl Functions of a single file
	function crawl_file($result,$key){
	
					$file = str_replace('{id}',$result[$this->info['column'][$key]],$this->info['link'][$key]);
					$url = $this->info['site_url'].$file;
					
					//Now We need to Open The page and grab the data
					$content = file_get_contents($url);
					//Grab Site Title
					preg_match('@<title>.*?<\/title>@si',$content,$matches);
					if(isset($matches[0])){
						$title = $matches[0];
					}
					else
					{
						$title = $this->info['link'][$key];
					}
					
					unset($matches);
					$title = $this->striphtml($title);
					$title = addslashes($title);
					
					//Strip HTML
					$content = $this->striphtml($content);
					$content = addslashes($content);
					
					//===================================================================
					//Keyword Algorithyms
					//===================================================================
					//Title Words
					$titleword = trim(stripslashes($title));
					$titleword = preg_replace('/\s\s+/', ' ', $titleword);
					$titleword = explode(' ',$titleword);
					
										//Content Words
					$contentword = trim(stripslashes($content));
					$contentword = preg_replace('/\s\s+/', ' ', $contentword);
					$contentword = explode(' ',$contentword);
					
						//Keyword Density
						foreach($contentword AS $word){
							$regword = $word;
							$word = strtolower($word);
							$density = 0;
							//Skip Simple Words
							if(!in_array($word,$this->info['exclude'])){
								$regword = preg_quote($word,'/');
								$dtmatches = preg_match_all('/'.$regword.'/i',$title,$matches);
								$density = $density + $dtmatches;
								
								$dcmatches = preg_match_all('/'.$regword.'/i',$content,$matches);
								$density = $density + $dcmatches;
													
								$location = @strpos($content,$regword);
								if($location == false){
									$location = 0;
								}
								
								$contentwords['word'][] = addslashes($word);
								$contentwords['density'][] = $density;
								$contentwords['location'][] = $location;
								$contentwords['weight'][] = $this->weight($word);
							}
							
						}
						
						//Keyword Density
						foreach($titleword AS $word){
							$regword = $word;
							$word = strtolower($word);
							$density = 0;
							//Skip Simple Words
								$regword = preg_quote($word,'/');
								$dtmatches = preg_match_all('/'.$regword.'/i',$title,$matches);
								$density = $density + $dtmatches;
								
								$dcmatches = preg_match_all('/'.$regword.'/i',$content,$matches);
								$density = $density + $dcmatches;
													
								$location = @strpos($content,$regword);
								if($location == false){
									$location = 0;
								}
								
								if(($csearch = array_search($word,$contentwords['word'])) !== FALSE){
									$density = $density + $contentwords['density'][$csearch];
								}
								
								$titlewords['word'][] = addslashes($word);
								$titlewords['density'][] = $density + 20;
								$titlewords['location'][] = $location;
								$titlewords['weight'][] = $this->weight($word);
						}
					//================================================================
					//End Keywords
					//================================================================
					
					//Insert content & link & timestamp into the db
					$this->db_query("INSERT INTO ".$this->info['db']['table']." 
								(
								id,
								pageid,
								title,
								created,
								url,
								content
								)
								VALUES
								(
								'',
								'".$result[$this->info['column'][$key]]."',
								'".$title."',
								'".time()."',
								'".$url."',
								'".$content."'
								) ");
								
					$id = mysql_insert_id();
					
					//Insert All Title Words
					foreach($titlewords['word'] AS $key => $data){
						$this->db_query("INSERT INTO ".$this->info['db']['properties']."
									( 
									id,
									sid,
									word,
									density,
									location,
									weight,
									title
									)
									VALUES
									(
									'',
									'".$id."',
									'".$data."',
									'".$titlewords['density'][$key]."',
									'".$titlewords['location'][$key]."',
									'".$titlewords['weight'][$key]."',
									'1'
									) ");
					}
					
					//Insert Top Density Content Words that Do not Match Title Words
					asort($contentwords['density']);
					arsort($contentwords['density']);
					if($this->info['keywords'] != 0){
						$contentinsert = array_slice($contentwords['density'],0,$this->info['keywords']);
					}
					else
					{
						$contentinsert = $contentwords['density'];
					}
					foreach($contentinsert AS $key => $data){
						if(!in_array($contentwords['word'][$key],$titlewords['word'])){
							$this->db_query("INSERT INTO ".$this->info['db']['properties']."
										( 
										id,
										sid,
										word,
										density,
										location,
										weight,
										title
										)
										VALUES
										(
										'',
										'".$id."',
										'".$contentwords['word'][$key]."',
										'".$data."',
										'".$contentwords['location'][$key]."',
										'".$contentwords['weight'][$key]."',
										'0'
										) ");
						}
					}
					
					if($this->info['verbose'] == 'html'){
						print "Indexed ".substr($title,0,50)."... @ ".$url."<br /><script type='text/javascript'>window.scrollTo(0,800000000);</script>";
						flush();
					}
					elseif($this->info['verbose'] == 'cli'){
						print "Indexed ".substr($title,0,50)."... @ ".$url."\n";
						flush();
					}
					
					$this->pages++;
					
					usleep($this->info['sleep']);
					
	}
	//Crawl For Data
	function crawl($full=false){
	
		set_time_limit(0);
	
		$this->start = microtime(true);
	
		if($this->info['verbose'] == 'html'){
			print "<h1>Welcome to the vSoftware Search Crawler</h1>";
			print "Starting crawl...<br />";
			flush();
		}
		elseif($this->info['verbose'] == 'cli'){
			print "Welcome to the vSoftware Search Crawler\n\n";
			print "Starting crawl...\n";
			flush();
		}
		
		//Establish a database connection
		$this->db_connect($this->info['db']);
		
		if($this->info['verbose'] == 'html'){
			print "Connected to database ".$this->info['db']['name']." <br />";
			flush();
		}
		elseif($this->info['verbose'] == 'cli'){
			print "Connected to database ".$this->info['db']['name']." \n";
			flush();
		}
		
		//Two types of crawl a full crawl and an update crawl. Full first
		$this->pages = 0;
		if($full == true){
		
			if($this->info['verbose'] == 'html'){
				print "Starting a full crawl.. <br />";
				flush();
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Starting a full crawl.. \n";
				flush();
			}
			//Ok full sweep we need to truncate the currect search db
			$this->db_query('TRUNCATE TABLE '.$this->info['db']['table'].' ');
			$this->db_query('TRUNCATE TABLE '.$this->info['db']['properties'].' ');
			
			if($this->info['verbose'] == 'html'){
				print "Cleared current search table. <br />
						//----------------------------------------------------<br />
						//Beginning Index<br />
						//----------------------------------------------------<br /><br />";
				flush();
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Cleared current search table.\n\n//----------------------------------------------------\n//Beginning Index\n//----------------------------------------------------\n\n";
				flush();
			}
			
			//Now Loop Through and Gather Information
			foreach($this->info['table'] AS $key => $table){
			
				if($this->info['verbose'] == 'html'){
					print "<b>Indexing table ".$table."</b><br />";
					flush();
				}
				elseif($this->info['verbose'] == 'cli'){
					print "Indexing table ".$table."\n\n";
					flush();
				}
			
				//Grab The current table
				$query = $this->db_query("SELECT ".$this->info['column'][$key]." FROM ".$table." ");
				//loop through all occurences in the table
				while($result = $this->db_result($query)){
					
					$this->crawl_file($result,$key);
					
				}

				if($this->info['verbose'] == 'html'){
					print "<br /><br />";
				}
				elseif($this->info['verbose'] == 'cli'){
					print "\n\n";
				}					
			}
			
			$this->end = microtime(true) - $this->start;
			$this->end = substr($this->end,0,10);

			if($this->info['verbose'] == 'html'){
				print "<h4>Crawl Completed Successfully!</h4>";
				print "-Completed in ".$this->end." seconds.<br />";
				print "-Using ".$this->queries." queries.<br />";
				print "-Index ".$this->pages." pages.<script type='text/javascript'>window.scrollTo(0,800000000);</script>";
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Crawl Completed Successfully!\n\n";
				print "-Completed in ".$this->end." seconds.\n";
				print "-Using ".$this->queries." queries.\n";
				print "-Index ".$this->pages." pages.\nExiting.";
			}
		}
		else
		{
		
			//update only crawl
		
			if($this->info['verbose'] == 'html'){
				print "Starting a partial crawl.. <br />";
				flush();
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Starting a partial crawl.. \n";
				flush();
			}
			
			if($this->info['verbose'] == 'html'){
				print "Adding to search table. <br />
						//----------------------------------------------------<br />
						//Beginning Index<br />
						//----------------------------------------------------<br /><br />";
				flush();
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Adding to search table.\n\n//----------------------------------------------------\n//Beginning Index\n//----------------------------------------------------\n\n";
				flush();
			}
			
			//Now Loop Through and Gather Information
			foreach($this->info['table'] AS $key => $table){
			
				$start = $this->pages;
			
				if($this->info['verbose'] == 'html'){
					print "<b>Indexing table ".$table."</b><br />";
					flush();
				}
			
				//Grab The current table
				$query = $this->db_query("SELECT ".$this->info['column'][$key]." FROM ".$table." ");
				//loop through all occurences in the table
				while($result = $this->db_result($query)){
				
					$file = str_replace('{id}',$result[$this->info['column'][$key]],$this->info['link'][$key]);				
					$url = $this->info['site_url'].$file;
					
					$checkq = $this->db_query("SELECT * FROM ".$this->info['db']['table']." WHERE url = '".$url."' ");
					$check = $this->db_result($checkq,"mysql_num_rows");
					if($check == 0){						

						$this->crawl_file($result,$key);
						
					}
						
				}
				
				if($start == $this->pages){
					if($this->info['verbose'] == 'html'){
							print "No Pages Indexed.<br /><script type='text/javascript'>window.scrollTo(0,800000000);</script>";
							flush();
					}
					elseif($this->info['verbose'] == 'cli'){
							print "No Pages Indexed.\n";
							flush();
					}
				}
					
				if($this->info['verbose'] == 'html'){
					print "<br /><br />";
				}
				elseif($this->info['verbose'] == 'cli'){
					print "\n\n";
				}					
			}
			
			$this->end = microtime(true) - $this->start;
			$this->end = substr($this->end,0,10);

			if($this->info['verbose'] == 'html'){
				print "<h4>Crawl Completed Successfully!</h4>";
				print "-Completed in ".$this->end." seconds.<br />";
				print "-Using ".$this->queries." queries.<br />";
				print "-Index ".$this->pages." pages.<script type='text/javascript'>window.scrollTo(0,800000000);</script>";
			}
			elseif($this->info['verbose'] == 'cli'){
				print "Crawl Completed Successfully!\n\n";
				print "-Completed in ".$this->end." seconds.\n";
				print "-Using ".$this->queries." queries.\n";
				print "-Index ".$this->pages." pages.\nExiting.";
			}
		}

		exit(0);
	}
	
	//display results
	function results($query,$url,$page,$limit=''){
	
		if($limit == ''){
			$limit = $this->info['search_limit'];
		}
		
		$timestart = microtime(true);
	
		//Figure out what page we are on.
		if($page == 1){
			$start = 0;
		}
		else
		{
			$start = ($page - 1) * $limit;
		}
		
		//Break apart keywords
		$query = str_replace('.',' ',$query);
		$query = trim($query);
		$statstring = stripslashes($query);
		$keywords = explode(' ',$query);
			
		//Build SQL String
		$string = '';
		foreach($keywords AS $data){
			if(!in_array($data,$this->info['exclude'])){
				
				$data = $data;
				if($string != ''){
					$string .= ' OR ';
				}
				$string .= "w.word LIKE '%".$data."%'";
					
				$regex[] = '/'.$data.'/i';
			}
		}
		
		if($this->info['titles'] == TRUE){
			$titletags = "AND s.title LIKE '%".$query."%' AND s.content LIKE '%".$query."%' ";
		}
		else
		{
			$titletags = '';
		}
			
		//Query Database
		if($string != ''){
			$dbcq 	 = $this->db_query("SELECT  w.word, s.title
												FROM ".$this->info['db']['properties']." w JOIN ".$this->info['db']['table']." s ON s.id=w.sid
												WHERE ".$string." ".$titletags." ");
			$dbcount = $this->db_result($dbcq,"mysql_num_rows");
			$dbquery = $this->db_query("SELECT  w.word, w.sid AS sid,w.location AS location, count(DISTINCT w.word) AS matches, sum(DISTINCT w.weight) AS weight, sum(w.density) AS density,
												s.title AS title, s.url AS url, s.content AS content, s.created AS created 
												FROM ".$this->info['db']['properties']." w JOIN ".$this->info['db']['table']." s ON s.id=w.sid 
												WHERE ".$string." ".$titletags."
												GROUP BY w.sid 
												ORDER BY matches DESC, weight DESC, w.title DESC, density DESC
												LIMIT ".$start.",".$limit." ");
		}
		else
		{
			$dbcount = 0;
		}
		$results = '';
		if($dbcount > 0 && trim($query) != ''){
			while($result = $this->db_result($dbquery)){
				
				//shorten tags
				$title = ucwords(strtolower(stripslashes(substr($result['title'],0,75).'...')));
				$content = stripslashes(substr($result['content'],$result['location'],250).'...');
				
				//Highlight Keywords
				$title = preg_replace($regex,'<b>\0</b>',$title);
				$content = preg_replace($regex,'<b>\0</b>',$content);
				
				//get date indexed
				$indexed = date('m/d/Y',$result['created']);
				
				$results .= '
					<div class="search_title"><a href="'.$result['url'].'">'.$title.'</a></div>
					<div class="search_desc">'.$content.'</div>
					<div class="search_details">'.$result['url'].' - page last indexed on: '.$indexed.'</div>
				';
				
			}
		}
		else
		{
			$dbcount = 0;
		
			$results.= '<div style="text-align:center">There are no results to display.</div>';
			
		}
		
		$timeend = microtime(true) - $timestart;
		$time = substr($timeend,0,7);
				
		$dispstart = $start;
		$displimit = $start + $limit;
		if($start == 0){
			$dispstart = 1;
		}
		if($displimit > $dbcount){
			$displimit = $dbcount;
		}
		$pages = $this->pages($dbcount,$limit,$url);
		$stats = '<div class="search_stats">viewing results '.$dispstart.'-'.$displimit.' of '.$dbcount.' for "'.$statstring.'" in '.$time.' seconds</div><br />';
		$results = $stats.'<br />'.$results.'<div class="search_pages">'.$pages.'</div><br />'.$stats;
		
		
		return $results;
	
	}
	
	function pages($count, $limit, $url){
	
		$amount = ceil($count/$limit);
		if($amount == 0){
			$amount = 1;
		}
	
		//Generate Page Links
		if(!isset($_GET['p'])){
			$p = 1;
			$prev = '<a href="'.$url.'&p=1"><< Prev</a>';
			if(2 > $amount){
				$next = $amount;
			}
			else
			{
				$next = 2;
			}
			
			$next = '<a href="'.$url.'&p='.$next.'">Next >></a>';
		}
		else
		{
			$p = $_GET['p'];
			
			if(($_GET['p'] - 1) < 1){
				$p = 1;
			}
			$pr = $p;
			if($p == 1){
				$pr = 2;
			}
			$prev = '<a href="'.$url.'&p='.($pr - 1).'"><< Prev</a> ';
			
			if(($_GET['p'] + 1) > $amount){
				$next = $amount;
			}
			else
			{
				$next = $_GET['p'] + 1;
			}
			
				$next = '<a href="'.$url.'&p='.$next.'">Next >></a> ';
			
		}
		
		$first = '<a href="'.$url.'&p=1">|<</a> ';
		$last = '<a href="'.$url.'&p='.$amount.'">>|</a>';

		
		//Build Page HTML
		$pages = $first.' '.$prev.' ';
		
		if($count > $limit){
			if(($p + 10) >= $amount && $amount >= 20){
				$k = $amount - 19;
				$j = 20;
				$l = $amount - 19;
			}
			elseif($p > 10){
				$k = $p - 10;
				$j = 10;
				$l = $p;
			}
			else
			{
				$k = 1;
				$j = 20;
				$l = $p;
			}
		}
		else
		{
			$k=1;
			$j=1;
			$l=$p;
		}
		
		for($i=$k; $i<($l + $j); $i++){
		
			if(($i * $limit) <= ($count + $limit)){
			
				if($i == $p){
					
					$pages .= '<b>'.$i.'</b> ';
			
				}
				else
				{
					
					$pages .= '<a href="'.$url.'&p='.$i.'">'.$i.'</a> ';
					
				}
				
			}
			elseif($count < $limit){
					
				$pages.= '<b>1</b> ';
			}
			
		}
		
		$pages .= $next.' '.$last;
		
		return $pages;
	}

}

?>