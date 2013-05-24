<?php 
/* Class page
 * 
 * Handles general page structure and output, as well as
 * error pages. Uses Dwoo for templates.
 * 
 */
require_once('libs/openid/openid.php');

global $view_rewrite;
$view_rewrite = array (
	'top_maps' => 'map_list',
	'map_info' => 'map',
	'player_stats' => 'player'
);

class page
{
	protected $tabs;
	
	public static function load($request)
	{
		global $settings,$SITE; 
		
		require_once('classes/player.php');
		
		// Parse page request
		if($request)
		{		
			// Split off ? args
			$request_args = explode('?',$request);
			if($request_args[1])
			{
				$args = explode('&',$request_args[1]);
				foreach($args as $arg)
				{
					$bits = explode('=',$arg);
					$params[$bits[0]] = $bits[1];
				}
			}
			
			$request_bits = explode('/',$request_args[0]);
			$view = array_shift($request_bits);
			for($x=0;$x<count($request_bits);$x++)
				$params[$x] = $request_bits[$x];
		} else
			$view = 'index';
		
		global $view_rewrite;
		if(array_key_exists($view, $view_rewrite))
			$view = $view_rewrite[$view];
		
		if( file_exists( 'views/'.$view.'.view.php' ) )
		{
			include('views/'.$view.'.view.php');
		}
		
		$view.='_view';
		if(!class_exists($view))
		{
			@include('views/404.view.php');
			if(!class_exists('404_view'))
			{
				header( $_SERVER[ 'SERVER_PROTOCOL' ] . " 404 Not Found" ); 
				
				page::error("Now where'd I put that...","It seems everything needed to draw any kind of page
				has gone missing. We suspect spies, but rumor is Heavy has been looking for some new sandvich
				building materials.");
			}
		}
		
		$v = new $view($params);
		$v->prepare();
		$SITE['head'] .= $v->additional_head();
		global $user;
		if($params['creeper']) // || ($user && in_array($user->id(), array(-10))))
		{
			$SITE['head'] .= '
			<script src="/static/js/af/proto.js"></script> 
			<script src="/static/js/af/box2d.js"></script>
			<script src="/static/js/af/common.js"></script>  
			';
			//$SITE['af'] = '';
		}
		//if(($user && in_array($user->id(), array(1)))) $SITE['head'] = '';
		$SITE['title'] = sprintf("%s - TF2Stats.net",$v->get_title());
		$v->render();
		
	}	
	public static function draw($template,$parameters)
	{
		global $SITE, $settings, $dwoo, $session, $user;
		$params[$template] = $parameters;
		$params['site'] = $SITE;
		$params['settings'] = $settings;
		// login box
		if($session->valid())
		{
			$params['user'] =  $user->get_info();
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'user_box.htm',$params);
		}else
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'guest_box.htm',$params);
		
		
		
		$params['site']['content'] = $dwoo->get($settings['template_folder'].$template.'.htm',$params);
		
		echo $dwoo->get($settings['template_folder'].'generic.htm',$params);
	}
	public static function draw_standalone($template,$parameters)
	{
		global $SITE, $settings, $dwoo, $session, $user;
		$params[$template] = $parameters;
		$params['site'] = $SITE;
		
		
		echo $dwoo->get($settings['template_folder'].$template.'.htm',$params);
		
	}
	public static function draw_direct($html)
	{
		global $SITE, $settings, $dwoo, $session, $user;

		$params['site'] = $SITE;
		$params['settings'] = $settings;
		// login box
		if($session->valid())
		{
			$params['user'] =  $user->get_info();
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'user_box.htm',$params);
		}else
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'guest_box.htm',$params);
		
		
		
		$params['site']['content'] = $html;
		
		echo $dwoo->get($settings['template_folder'].'generic.htm',$params);
	}
	
	public static function draw_header()
	{
		global $SITE, $settings, $dwoo, $session, $user;
		
		require_once('classes/player.php');
		
		$params['site'] = $SITE;
		$params['settings'] = $settings;
		// login box
		if($session && $session->valid())
		{
			$params['user'] =  $user->get_info();
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'user_box.htm',$params);
		}else
			$params['site']['user_box'] = $dwoo->get($settings['template_folder'].'guest_box.htm',$params);
		
		
		
		$params['site']['content'] = $html;
		
		echo $dwoo->get($settings['template_folder'].'header.htm',$params);
	}
	
	public static function draw_footer()
	{
		global $SITE, $settings, $dwoo, $session, $user;

		$params['site'] = $SITE;
		$params['settings'] = $settings;
		
		echo $dwoo->get($settings['template_folder'].'footer.htm',$params);
	}
	
	
	public static function error($title, $message, $args=array('image' => 'heavy_shotgun'))
	{
		global $settings;
		
		$images = array(
			'heavy_shoot' => array(
				'img_width' => 351,
				'img' => $settings['images_folder'].'heavy render.png',
				'img_alt' => "CRY SOME MORE!"
			),
			'heavy_yell' => array(
				'img_width' => 375,
				'img' => $settings['images_folder'].'heavy_whotouched.png',
				'img_alt' => 'WHO TOUCHED MY GUN!?'
			),
			'heavy_shotgun' => array (
				'img_width' => 343,
				'img' => $settings['images_folder'].'Heavy_with_shotty.png',
				'img_alt' => 'ALL OF YOU ARE DEAD!'
			),
			'sentry' => array (
				'img_width' => 300,
				'img' => $settings['images_folder'].'Sentry-lvl-1.png',
				'img_alt' => 'BEEP! BEEP! BEEP!',
			),
			'spy' => array (
				'img_width' => 319,
				'img' => $settings['images_folder'].'tf2_spy1s.png',
				'img_alt' => 'Ahem. Gentlemen.'
			),
			'soldier' => array (
				'img_width' => 261,
				'img' => $settings['images_folder'].'tf2soldier.png',
				'img_alt' => 'MAGGOTS!'
			)
		);
		
		$params = array(
			'title' => $title,
			'message' => $message
		);
		
		page::draw('error',array_merge($params,$images[$args['image']]));
		
		if( IsUserAdmin( ) )
		{
			global $CACHE_LOG, $runtime;
			
			printf( "<pre>%s\nTotal run time: %s</pre>", $CACHE_LOG, number_format( microtime(true) - $runtime, 3 ) );
		}
		
		exit();
	}
}

?>