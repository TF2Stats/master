<?php
class players_view extends view
{
	public function prepare()
	{
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
			require_once('cache/valve_employees.php');
			
			global $VALVE_EMPLOYEES;
			
			$json = cache::read('player_views.json');
			$views = json_decode($json, true);
			$this->params['views'] = $views;
			$this->params['valve_employees'] = $VALVE_EMPLOYEES;
		}
		
	}
}
?>