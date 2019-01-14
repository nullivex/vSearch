<?PHP
//------------------------------------------------------
//vMotion - Advanced Content Management Software
//	(C) 2007 vSoftware.org. All rights reserved.
//------------------------------------------------------

//--------------------------------------------------------------------------------------------------------
//NOTES ON API
//	--This is an example API 
//	--This API expects a valid MySQL connection 
//	--$search->results($query,$url,$page,$limit);
//		--$query = the string to be searched
//		--$url = used for paging needs to include urlencode($url);
//		--$page = current page of the search
//		--$limit = results per page default is 10;
//---------------------------------------------------------------------------------------------------------
//Vsoftware Search API
//--------------------------------------------------------    

//Load the search class
require('sources/search.php');
$search = new search;

//Now Lets a User vists without a query
if(!isset($_GET['keywords']) && !isset($_GET['crawl'])){

	echo '
		<form action="" method="get">
		<input type="hidden" name="search" value="true" />
		<input type="text" name="keywords" size="50" />
		<input type="submit" value="Search" />
		</form>
	';
	
}
elseif(isset($_GET['crawl']) && isset($_GET['code'])){

	if(!isset($config['crawl_pass'])){
		$config['crawl_pass'] = 'vsoftsearch';
	}

	$crawlcode = md5($config['crawl_pass']);
	if($_GET['code'] == $crawlcode){
	
		if(!isset($_GET['full'])){
			$_GET['full'] = false;
		}		
		
		$search->crawl($_GET['full']);
	
	}
	else
	{

		$template->parse('search','home');
			
	}
	
}
else
{

	if(strlen($_GET['keywords']) > 0){
		if(!isset($_GET['p'])){
			$_GET['p'] = '1';
		}
		
		$query = $_GET['keywords'];
		$page = $_GET['p'];
		
		$url = $config['site_url'].'/index.php?view=search&keywords='.urlencode($query);

		//Display Search Results
		$results = $search->results($query,$url,$page,$config['search_limit']);
		
		echo $results;
		
	}
	else
	{
		header('location:index.php?view=search');
	}
	
}

?>