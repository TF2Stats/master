<?php 
require_once('classes/player.php');
require_once('classes/backpack.php');
@include('cache/valve_employees.php');

class player_view extends view
{
	public function prepare()
	{
		global $METRICS, $CLASS_METRICS, $CLASSES, $CLASS_SLOTS, $VALVE_EMPLOYEES;
		
		$this->template="player_stats";
		$this->tab="player";
		
		//backpack::update_schema();
		if(is_id64($this->request[0]))
			$player_id = $this->request[0];
		else
		{
			$player_id = get_id64($this->request[0]);
			if(!$player_id)
				page::error("I will eat your search results, I'll eat them up!","Thats right nancy boy, I've got better things
				to do than look for some sissy boy stats page, there's a war going on! The only path to victory is through
				pain and bloodshed. So man up girly boy, and put the right thing in the box next time.",array('image' => "soldier"));
		}
		
		$player = new player($player_id, true);
		$player->preload_stats(true);
		$backpack = new backpack($player, true);

		cache::multi_run();

		$stats = $player->get_stats();
		
		if($player->info['custom_url'])
			$this->canonical = sprintf('http://tf2stats.net/player/%s/',$player->info['custom_url']);
		else
			$this->canonical = sprintf('http://tf2stats.net/player/%s/',$player->id64());

		//if($player_id != "76561198003273729")
		$player->log_view();
		
		foreach($CLASSES as $c)
		{
			// Build sorted class array
			$classes[$stats[$c]['accum']['iplaytime']] = $c;
			
			foreach($METRICS as $m=>$l)
			{
				// Determine peak class values
				if($stats[$c]['max'][$m] > $stats['all']['max'][$m]['value'])
					$stats['all']['max'][$m] = array (	'value' => $stats[$c]['max'][$m],
													'class' => $c);

				if($stats[$c]['accum'][$m] > $stats['all']['best'][$m])
					$stats['all']['best'][$m] = $stats[$c]['accum'][$m];
					
				$stats['all']['accum'][$m] += $stats[$c]['accum'][$m];
			}
		}
		krsort($classes);
		
		$this->params['classes'] = $classes;
		$this->params['metrics'] = $METRICS;
		$this->params['class_metrics'] = $CLASS_METRICS;
		
		$this->params['backpack'] = $backpack;
		$this->params['equipped'] = $backpack->equipped;
		$this->params['player'] = $player;
		$this->params['stats'] = $stats;
		
		$this->params['slots'] = $CLASS_SLOTS;
		
		//var_dump($backpack->equipped['soldier']['primary']);
		
		//var_dump($backpack->equipped);
		
		//var_dump($backpack->items);
		//echo decbin(1082130432);
		//$up = unpack('f',1082130432);
		//var_dump($up);
		for($x=1;$x<=1050;$x++)
			$fullbp[$x] = false;
		$invalids = array_slice($fullbp,1000,50);
		$ix=0;
		$maxpage = 300;
		
		if( is_array( $backpack->items ) )
		{
			foreach($backpack->items as $i)
			{
				unset($tail);
				if($fullbp[$i['position']] || !$i['position'])
					$invalids[$ix++] = $i;
				else
					$fullbp[$i['position']] = $i;
				if($i['position'] > $maxpage)
					$maxpage = $i['position'];
			}
		}
		
		for($x=0;$x<$maxpage;$x+=50)
			$bpp[] = array('items' => array_slice($fullbp,$x,50));

		if($ix > 0)
			$bpp[] = array('items' => $invalids);
		
		$this->params['bpp'] = $bpp;
		$this->title = htmlspecialchars($player->info['name']);
		
		$this->params['player_name'] = $this->title;
		
		if( $player_id === '76561197972495328' ) // FireSlash
		{
			$this->params['player_name'] .= ' — <span style="color:#4d8bd0">TF2Stats Creator</span>';
		}
		else if( $player_id === '76561197972494985' ) // xPaw
		{
			$this->params['player_name'] = '<a href="//xpaw.ru" target="_blank" style="text-decoration:none">' . $this->title . '</a> — <span style="color:#d0514d">TF2Stats Maintainer</span>';
		}
		
		$this->params['valve_employee'] = in_array( $player_id, $VALVE_EMPLOYEES );
	}
}
?>