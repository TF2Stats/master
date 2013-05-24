<?php 
global $LOCAL_CACHE;

class cache
{
	public static function get($url, $age=false, $cache_only = false)
	{
		global $db, $settings, $CACHE, $cache_stats;
		
		if(!$url)
			return;
		
		if($CACHE[$url])
			return $CACHE['url'];
		
		if(!$age)
			$age = $settings['cache']['length'];

		$cache = $db->query_first("SELECT data, time FROM tf2stats_cache WHERE time > %s AND url = %s",
			array(time() - $age, $url));
		
		if( $cache && strlen($cache['data']) > 1)
		{
			cache::log( sprintf( 'SQL cache HIT for %s (age: %s, max: %s)', $url, time() - $cache['time'], $age ) );
			return $cache['data'];
		}
		else
			cache::purge($url);

		cache::log( sprintf( 'SQL cache MISS for %s (age: %s, max: %s)', $url, $cache['time'], $age ) );

		$cache_stats['sql']['misses'] += 1;

		if($cache_only)
			return false;

		$context = stream_context_create(array( 
		    'http' => array( 
		        'timeout' => 5,
				'header'=>'Connection: close'
		        ) 
		    ) 
		);
		$apitime = microtime(true);
		$contents =  file_get_contents($url, false, $context);
		$apitime = number_format(microtime(true) - $apitime, 3);
		cache::log( sprintf( "Single API request took %s sec", $apitime ) );

		if($contents)
		{
			cache::put($url, $contents);
			return $contents;
		}
		return false;
	}

	function put($url, $contents)
	{
		global $db;

		if($contents)
		{
			$db->query("INSERT INTO tf2stats_cache (data, url, time) VALUES(%s, %s, %s)",
				array($contents, $url, time()));
			$LOCAL_CACHE[$url] = $contents;
			return $contents;
		}
	}

	public static function purge($url)
	{
		global $db;

		$db->query("DELETE FROM tf2stats_cache WHERE url=%s",array($url));
	}

	public static function write($name, $contents)
	{
		global $settings;
		file_put_contents($settings['cache']['folder'].$name,$contents);
	}

	public static function read($name)
	{
		global $settings;
		return file_get_contents($settings['cache']['folder'].$name);
	}

	public static function inc($name)
	{
		global $settings;
		include($settings['cache']['folder'].$name);
	}

	public static function age($name)
	{
		global $settings;
		return time() - @filemtime($settings['cache']['folder'].$name);
	}

	public static function date($name)
	{
		global $settings;
		return @filemtime($settings['cache']['folder'].$name);
	}

	public static function clean($type)
	{
		global $settings;
		switch($type)
		{
			case 'backpack':
				foreach(glob($settings['cache']['folder'].'backpack.*.php') as $file)
     				unlink($file);
     			break;
				
		}
	}

	public static function get_multi($requests, $age = false)
	{
		$dirty_urls = array();
		$result = array();

		foreach($requests as $r)
		{
			$contents = cache::get($r['url'], $r['age'], true);
			if($contents !== false)
				if( $r['func'] )
					call_user_func_array($r['func'], array( $contents ) );
				else
					$result[$r['url']] = $contents;
			else
				$dirty_urls[] = $r;
		}

		if(!$dirty_urls)
		{
			cache::log( "All multi requsts in cache." );
			return $result;
		}

		$result = cache::curl_multi_get($dirty_urls);

		return $result;
	}

	public static function curl_multi_get($requests)
	{
		$apitime = microtime(true);

		$jobs = array();
		$multi_handle = curl_multi_init();
		$result = array();

		foreach( $requests as $request )
		{
			$job = curl_init();
			$curl_opt = array(CURLOPT_URL => $request['url'], CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_AUTOREFERER => true, CURLOPT_TIMEOUT => 5, CURLOPT_MAXREDIRS => 1, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.162 Safari/535.19');
			curl_setopt_array($job, $curl_opt);
			curl_multi_add_handle($multi_handle, $job);

			$request['handle'] = $job;

			$jobs[] = $request;
		}

		$active = null;
		do
		{
			$last = $active;
			
			curl_multi_select( $multi_handle );
			curl_multi_exec( $multi_handle, $active );
			
			//while(($wtf = curl_multi_select( $multi_handle )) == 0);
			//while(($execrun = curl_multi_exec($multi_handle, $active)) == CURLM_CALL_MULTI_PERFORM);
			
			if( $last !== $active && $active !== null )
			{
				while( $done = curl_multi_info_read( $multi_handle ) )
				{
					if( $done['result'] != CURLE_OK )
						cache::log("Got bad CURL response: " . $done['result']);

					foreach( $jobs as $job )
					{
						if( $done['handle'] === $job['handle'] )
						{
							$content = curl_multi_getcontent( $done['handle'] );
							if($content == null)
							{
								//$content = file_get_contents($job['url'], false, stream_context_create( array('http' => array( 'timeout' => 1 ) ) ) );
								$content = file_get_contents($job['url']);
								cache::log("CURL RESULT EMPTY. Falling back to file_get_contents for url: ".$job['url']);
							}

							cache::log( sprintf( "Got multi result for %s in %s sec, len %s", $job['url'], number_format(microtime(true) - $apitime, 3), strlen( $content ) ) );

							$cbtime = microtime(true);
							if( $job['func'] )
								call_user_func_array($job['func'], array( $content ) );

							cache::log( sprintf( "Ran callback for URL in %s sec", number_format(microtime(true) - $cbtime, 3) ) );
							$result[$job['url']] = $content;
							cache::put($job['url'], $content);
						}
					}
					curl_multi_remove_handle( $multi_handle, $done['handle'] );
					curl_close( $done['handle'] );
				}
			}
			else
			{
				cache::log( "last == active" );
				//curl_multi_select( $multi_handle );
			}
		} while( $active > 0 );

		curl_multi_close($multi_handle);

		$apitime = number_format(microtime(true) - $apitime, 3);
		cache::log( sprintf( "Multi API request took %s sec", $apitime ) );

		return $result;
	}

	public static function register_multi_url($url, $func, $age = false)
	{
		global $MULTI_REQUESTS;
		$MULTI_REQUESTS[] = array( 'url' => $url, 'func' => $func, 'age' => $age );
		cache::log( sprintf( "Registering multi url: %s", $url ) );
	}

	public static function multi_run()
	{
		global $MULTI_REQUESTS;



		if( count( $MULTI_REQUESTS ) > 0 )
		{
			cache::log( sprintf( "multi_run on %s urls.", count($MULTI_REQUESTS) ) );
			return cache::curl_multi_get($MULTI_REQUESTS);
		}
	}

	public static function log($msg)
	{
		global $CACHE_LOG, $settings;
		$CACHE_LOG .= "\n".str_replace($settings['api_key'],'API_KEY',$msg);
	}
}

?>