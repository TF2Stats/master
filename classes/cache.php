<?php 
global $LOCAL_CACHE;

class cache
{
	public static $__LinkToFunc = Array();
	public static $__TempResult = Array();
	public static $__ApiTime;
	public static $__Memcached = NULL;

	/**
	 * @return Memcached
	 */
	public function Memcached()
	{
		if( self::$__Memcached === NULL )
		{
			self::$__Memcached = new Memcached();
			self::$__Memcached->addServer('localhost', 11211);
		}

		return self::$__Memcached;
	}


	public static function get($url, $age=false, $cache_only = false)
	{
		global $db, $settings, $CACHE, $cache_stats;

		$key = sprintf("URL_".md5($url) );
		$contents = self::Memcached()->get($key);

		if( $contents !== false )
		{
			cache::log( sprintf( 'Memcached HIT for %s', $key ) );
			return $contents;
		}

		cache::log( sprintf( 'Memcached MISS for %s (result: %s)', $key, self::Memcached()->getResultCode() ) );

		$cache_stats['sql']['misses'] += 1;

		if($cache_only)
		{
			return false;
		}
		
		$apitime = microtime(true);
		
		$rc = new RollingCurl();
		$rc->request( $url );
		$rc->window_size = 1;
		$contents = $rc->execute();
		
		$apitime = number_format(microtime(true) - $apitime, 3);
		
		cache::log( sprintf( "Single API request took %s sec", $apitime ) );

		if($contents)
		{
			if(!$age)
			{
				$age = $settings['cache']['length'];
			}
			
			self::Memcached()->set($key, $contents, time() + $age);
			
			return $contents;
		}
		
		return false;
	}

	function put($url, $contents)
	{
		global $settings;
		$key = sprintf("URL_".md5($url) );
		$age = $settings['cache']['length'];

		self::Memcached()->set($key, $contents, time() + $age);
	}

	public static function purge($url)
	{
		$key = sprintf("URL_".md5($url) );
		self::Memcached()->delete( $key );
	}

	public static function write($name, $contents)
	{
		global $settings;
		$age = $settings['cache']['length'];

		$key = "VAR_".$name;
		self::Memcached()->set($key, $contents, time() + $age);
	}

	public static function read($name)
	{
		global $settings;
		$key = "VAR_".$name;
		return self::Memcached()->get($key);
	}

	public static function inc($name)
	{
		global $settings;

		$name = $settings['cache']['folder'].$name;

		if( file_exists( $name ) )
		{
			include($name);
		}
	}

	public static function age($name)
	{
		debug_print_backtrace();
		die("NO.");
	}

	public static function date($name)
	{
		global $settings;
		return @filemtime($settings['cache']['folder'].$name);
	}

	public static function clean()
	{
		self::Memcached()->flush();
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
		cache :: $__LinkToFunc = array();
		cache :: $__TempResult = array();
		cache :: $__ApiTime = microtime(true);

		$rc = new RollingCurl( function( $response, $info, $request )
		{
			$url = $request->url;
			
			if( $response == null )
			{
				cache::log("CURL RESULT EMPTY for url: ".$url . " with HTTP code " . $info[ 'http_code' ]);
				
				/*$response = file_get_contents($url);
				
				if( $response == false )
				{
					cache::log("FILE_GET_CONTENTS failed for url: ".$url);
				}*/
			}
			
			cache::log( sprintf( "Got multi result for %s in %s sec, len %s", $url, number_format(microtime(true) - cache :: $__ApiTime, 3), strlen( $response ) ) );
			
			$cbtime = microtime(true);
			
			if( cache :: $__LinkToFunc[ $url ] )
				call_user_func_array( cache :: $__LinkToFunc[ $url ], array( $response ) );
			
			cache::log( sprintf( "Ran callback for URL in %s sec", number_format(microtime(true) - $cbtime, 3) ) );
			
			cache :: $__TempResult[ $url ] = $response;
			cache::put( $url, $response );
		});
		
		foreach( $requests as $request )
		{
			cache :: $__LinkToFunc[ $request['url'] ] = $request['func'];
			
			$rc->request( $request['url'] );
		}
		
		$rc->window_size = count($requests);
		$rc->execute();
		
		cache::log( sprintf( "Multi API request took %s sec", number_format(microtime(true) - cache :: $__ApiTime, 3) ) );

		return cache :: $__TempResult;
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





/**
 * Class that represent a single curl request
 */
class RollingCurlRequest {
    public $url = false;
    public $method = 'GET';
    public $post_data = null;
    public $headers = null;
    public $options = null;

    /**
     * @param string $url
     * @param string $method
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return void
     */
    function __construct($url, $method = "GET", $post_data = null, $headers = null, $options = null) {
        $this->url = $url;
        $this->method = $method;
        $this->post_data = $post_data;
        $this->headers = $headers;
        $this->options = $options;
    }

    /**
     * @return void
     */
    public function __destruct() {
        unset($this->url, $this->method, $this->post_data, $this->headers, $this->options);
    }
}

/**
 * RollingCurl custom exception
 */
class RollingCurlException extends Exception {
}

/**
 * Class that holds a rolling queue of curl requests.
 *
 * @throws RollingCurlException
 */
class RollingCurl {
    /**
     * @var int
     *
     * Window size is the max number of simultaneous connections allowed.
     *
     * REMEMBER TO RESPECT THE SERVERS:
     * Sending too many requests at one time can easily be perceived
     * as a DOS attack. Increase this window_size if you are making requests
     * to multiple servers or have permission from the receving server admins.
     */
    private $window_size = 5;

    /**
     * @var float
     *
     * Timeout is the timeout used for curl_multi_select.
     */
    private $timeout = 10;

    /**
     * @var string|array
     *
     * Callback function to be applied to each result.
     */
    private $callback;

    /**
     * @var array
     *
     * Set your base options that you want to be used with EVERY request.
     */
    protected $options = array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30
    );

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var Request[]
     *
     * The request queue
     */
    private $requests = array();

    /**
     * @var RequestMap[]
     *
     * Maps handles to request indexes
     */
    private $requestMap = array();

    /**
     * @param  $callback
     * Callback function to be applied to each result.
     *
     * Can be specified as 'my_callback_function'
     * or array($object, 'my_callback_method').
     *
     * Function should take three parameters: $response, $info, $request.
     * $response is response body, $info is additional curl info.
     * $request is the original request
     *
     * @return void
     */
    function __construct($callback = null) {
        $this->callback = $callback;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return (isset($this->{$name})) ? $this->{$name} : null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function __set($name, $value) {
        // append the base options & headers
        if ($name == "options" || $name == "headers") {
            $this->{$name} = $value + $this->{$name};
        } else {
            $this->{$name} = $value;
        }
        return true;
    }

    /**
     * Add a request to the request queue
     *
     * @param Request $request
     * @return bool
     */
    public function add($request) {
        $this->requests[] = $request;
        return true;
    }

    /**
     * Create new Request and add it to the request queue
     *
     * @param string $url
     * @param string $method
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function request($url, $method = "GET", $post_data = null, $headers = null, $options = null) {
        $this->requests[] = new RollingCurlRequest($url, $method, $post_data, $headers, $options);
        return true;
    }

    /**
     * Perform GET request
     *
     * @param string $url
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function get($url, $headers = null, $options = null) {
        return $this->request($url, "GET", null, $headers, $options);
    }

    /**
     * Perform POST request
     *
     * @param string $url
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function post($url, $post_data = null, $headers = null, $options = null) {
        return $this->request($url, "POST", $post_data, $headers, $options);
    }

    /**
     * Execute processing
     *
     * @param int $window_size Max number of simultaneous connections
     * @return string|bool
     */
    public function execute($window_size = null) {
        // rolling curl window must always be greater than 1
        if (sizeof($this->requests) == 1) {
            return $this->single_curl();
        } else {
            // start the rolling curl. window_size is the max number of simultaneous connections
            return $this->rolling_curl($window_size);
        }
    }

    /**
     * Performs a single curl request
     *
     * @access private
     * @return string
     */
    private function single_curl() {
        $ch = curl_init();
        $request = array_shift($this->requests);
        $options = $this->get_options($request);
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        // it's not neccesary to set a callback for one-off requests
        if ($this->callback) {
            $callback = $this->callback;
            if (is_callable($this->callback)) {
                call_user_func($callback, $output, $info, $request);
            }
        }
        else
            return $output;
        return true;
    }

    /**
     * Performs multiple curl requests
     *
     * @access private
     * @throws RollingCurlException
     * @param int $window_size Max number of simultaneous connections
     * @return bool
     */
    private function rolling_curl($window_size = null) {
        if ($window_size)
            $this->window_size = $window_size;

        // make sure the rolling window isn't greater than the # of urls
        if (sizeof($this->requests) < $this->window_size)
            $this->window_size = sizeof($this->requests);

        if ($this->window_size < 2) {
            throw new RollingCurlException("Window size must be greater than 1");
        }

        $master = curl_multi_init();

        // start the first batch of requests
        for ($i = 0; $i < $this->window_size; $i++) {
            $ch = curl_init();

            $options = $this->get_options($this->requests[$i]);

            curl_setopt_array($ch, $options);
            curl_multi_add_handle($master, $ch);

            // Add to our request Maps
            $key = (string) $ch;
            $this->requestMap[$key] = $i;
        }

        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($execrun != CURLM_OK)
                break;
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($master)) {
                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);

                // send the return values to the callback function.
                $callback = $this->callback;
                if (is_callable($callback)) {
                    $key = (string) $done['handle'];
                    $request = $this->requests[$this->requestMap[$key]];
                    unset($this->requestMap[$key]);
                    call_user_func($callback, $output, $info, $request);
                }

                // start a new request (it's important to do this before removing the old one)
                if ($i < sizeof($this->requests) && isset($this->requests[$i]) && $i < count($this->requests)) {
                    $ch = curl_init();
                    $options = $this->get_options($this->requests[$i]);
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);

                    // Add to our request Maps
                    $key = (string) $ch;
                    $this->requestMap[$key] = $i;
                    $i++;
                }

                // remove the curl handle that just completed
                curl_multi_remove_handle($master, $done['handle']);

            }

            // Block for data in / output; error handling is done by curl_multi_exec
            if ($running)
                curl_multi_select($master, $this->timeout);

        } while ($running);
        curl_multi_close($master);
        return true;
    }


    /**
     * Helper function to set up a new request by setting the appropriate options
     *
     * @access private
     * @param Request $request
     * @return array
     */
    private function get_options($request) {
        // options for this entire curl object
        $options = $this->__get('options');
		$options[CURLOPT_FOLLOWLOCATION] = 1;
        $options[CURLOPT_AUTOREFERER] = 1;
        $options[CURLOPT_MAXREDIRS] = 1;
		$options[CURLOPT_HEADER] = 0;
		$options[CURLOPT_TIMEOUT] = 10;
		$options[CURLOPT_ENCODING ] = "gzip";
		$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.43 Safari/536.11';

        // append custom options for this specific request
        if ($request->options) {
            $options = $request->options + $options;
        }

        // set the request URL
        $options[CURLOPT_URL] = $request->url;

        // posting data w/ this request?
        if ($request->post_data) {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $request->post_data;
        }

        return $options;
    }

    /**
     * @return void
     */
    public function __destruct() {
        unset($this->window_size, $this->callback, $this->options, $this->headers, $this->requests);
    }
}
?>