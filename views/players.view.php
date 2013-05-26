<?php
class players_view extends view
{
	public function prepare()
	{
		global $settings;

		$this->template="players";
		$this->tab="player";
		$this->title = "Player search";
		
		if($this->request['search'])
		{
			// Split out URLs! :x
			$str = trim($this->request['search']);

			$str_array = explode('/',$str);
			$sbit = $str_array[count($str_array)-1];
			if(!$sbit)
				$sbit = $str_array[count($str_array)-2];
			header('Location: /player/'.$sbit);
		} else {
			@include('cache/valve_employees.php');
			
			global $VALVE_EMPLOYEES;


			$views = cache::Memcached()->get('player_views');
			if( $views === false )
			{
				$json = file_get_contents($settings['cache']['folder'].'player_views.json');
				$views = json_decode($json, true);
				cache::Memcached()->set('player_views', $views, time() + 60*15);
			}

			$this->params['views'] = $views;
			$this->params['valve_employees'] = $VALVE_EMPLOYEES;
		}
		
	}
}
?>