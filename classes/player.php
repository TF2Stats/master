<?php 

require_once('cache.php');

class player
{
	private $id64;
	private $id;
	private $user_info;
	public $info;
	private $error;
	
	function __construct($id64, $multi=false)
	{
		global $settings, $db;

		$this->id64 = $id64;

		$info = $db->query_first("SELECT * FROM tf2_players WHERE id64 = %s",
			array($id64));

		$this->info = $info;

		if( !$info || ( $info['time'] < time() - $settings['cache']['player'] ) )
		{
			if($multi)
				cache::register_multi_url(
					sprintf( 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0001/?key=%s&steamids=%s', $settings['api_key'], $id64 ),
					array( $this, 'multi_initialize' ),
					$settings['cache']['player']
				);
			else
			{
				//$json = cache::get(sprintf('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0001/?key=%s&steamids=%s',$settings['api_key'],$id64));
				$this->initialize( $this->id64 );
			}
		}
		else
			$this->initialize( $this->id64 );
	}

	public function multi_initialize($json)
	{
		$this->initialize($this->id64, $json);
	}

	function initialize($id64, $json = false)
	{
		global $settings, $db;
		$this->id64 = $id64;

		$info = $this->info;

		if(!$info || ($info['time'] < time() - $settings['cache']['player']))
		{
			try {
				if(!$json)
					$json = cache::get(sprintf('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0001/?key=%s&steamids=%s',$settings['api_key'],$id64));
				$info = json_decode($json, true);
				$p = $info['response']['players']['player'][0];
				if($p)
				{

					$db->query("INSERT INTO tf2_players (id64, name, avatar, time) VALUES(%s, %s, %s, %s)
								ON DUPLICATE KEY UPDATE name=%s, avatar=%s, time=%s",
						array($id64,$p['personaname'],$p['avatar'],time()
						,$p['personaname'],$p['avatar'],time()));
					$this->info = array (
						'name' => $p['personaname'],
						'avatar' => $p['avatar'],
						'avatar_full' => str_replace('.jpg','_full.jpg',$p['avatar'])
					);
					// TODO: lazy hack.
					$this->info = $db->query_first("SELECT * FROM tf2_players WHERE id64 = %s",
						array($id64));
				} else
					$this->error = 'Unknown profile';
			} catch(Exception $e) {
				page::error("IT NO WORK", "Steam community appears to be down at the moment. Heavy is not pleased. Try again in a few minutes.", array('image' => 'heavy_yell'));
			}

		} else
			$this->info = $info;

		// Add full and small avatars
		$this->info['avatar_full'] = str_replace('.jpg','_full.jpg',$this->info['avatar']);
		$this->info['avatar_medium'] = str_replace('.jpg','_medium.jpg',$this->info['avatar']);
	}

	function log_view()
	{
		global $db;
		$db->query("UPDATE tf2_players SET views = views + 1, views_today = views_today + 1 WHERE id = %s",array($this->id()));
	}
	function id()
	{
		return $this->info['id'];
	}
	function id64()
	{
		return $this->id64;
	}
	function get_info()
	{
		return $this->info;
	}

	function preload_stats( $multi = false )
	{
		global $settings;

		$url = sprintf('http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=440&key=%s&steamid=%s', $settings['api_key'],$this->id64);
		$stats_json = cache::get( $url, false, $multi );
		if($stats_json)
			$this->multi_stats( $stats_json );
		else
			cache::register_multi_url( $url, array($this, 'multi_stats') );
	}

	function multi_stats($json)
	{
		try {
			$rgResult = json_decode($json, true);
			//var_dump($rgResult);
			//die();
		} catch(Exception $e) {
			page::error("Steam community error", "Steam appears to be having some trouble at the moment. Heavy is not pleased. Try again in a few minutes.", array('image' => 'heavy_yell'));
		}

		$stats = array();
		if( !empty( $rgResult['playerstats']['stats'] ) )
		{
			foreach($rgResult['playerstats']['stats'] as $item)
			{
				//var_dump($item);
				//printf("API: %s Value: %s <br />",$item['name'], $item['value']);
				$keys = explode('.', $item['name'] );
				$a = &$stats;
				foreach($keys as $k)
					$a = &$a[strtolower($k)];

				$a = intval($item['value']);
			}
		}

		$this->stats = $stats;
	}

	function get_stats()
	{
		if( !$this->stats )
			$this->preload_stats();
		return $this->stats;
	}
}
$CLASSES = array (
	'scout', 'pyro', 'heavy','engineer','sniper', 'spy', 'medic', 'soldier', 'demoman'
);

$METRICS = array (
	'ibuildingsdestroyed' => 'Buildings Destroyed',
	'idamagedealt' => 'Damage Dealt',
	'idominations' => 'Dominations',
	'ikillassists' => 'Kill Assists',
	'inumberofkills' => 'Number of Kills',
	//'inumdeaths' => 'Number of Deaths',
	//'inuminvulnerable' => 'Number of Ubercharges',
	'iplaytime' => false, //'Play Time',
	'ipointcaptures' => 'Points Captured',
	'ipointdefenses' => 'Points Defended',
	'ipointsscored' => 'Points Scored',
	'irevenge' => 'Revenges'
);
$CLASS_METRICS = array (
	'scout' => array(

	),
	'pyro' => array(
		'metrics' => array (
			'ifiredamage' => 'Fire Damage'
		)
	),
	'heavy' => array (

	),
	'engineer' => array (
		'metrics' => array(
			'ibuildingsbuilt' => 'Buildings Built',
			'inumteleports' => 'Number of teleports',
			//'isentrykills' => 'Sentry kills'
		)
	),
	'sniper' => array (
		'metrics' => array(
			'iheadshots' => 'Headshots'
		)
	),
	'spy' => array (
		'metrics' => array(
			'ibackstabs' => 'Backstabs',
			'ihealthpointsleached' => 'Health Stolen',
			'iheadshots' => 'Headshots'
		)
	),
	'medic' => array (
		'metrics' => array(
			'inuminvulnerable' => 'Ubercharges deployed',
			'ihealthpointshealed' => 'Healing done'
		)
	),
);
?>