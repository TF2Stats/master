<?php 
class session
{
	private $data = false;
	private $id64 = -1;
	
	function __construct()
	{
		$this->check_session();
	}
	private function check_session($session_id = false)
	{
		global $db, $settings, $user;
		
		if(!$session_id)
			$session_id = $_COOKIE['sid'];
		$ip = $_SERVER['REMOTE_ADDR'];
		
		$info = $db->query_first("SELECT id, data FROM tf2stats_sessions WHERE time > %s AND ip = %s AND id = %s ",
			array(time() - $settings['session']['length'], $ip, $session_id));
		if($info)
		{
			$this->data = unserialize($info['data']);
			$this->id64 = $this->data['id64'];
			setcookie("sid", $info['id'], time()+$settings['session']['length'], '/');
			if(!$user)
				$user = new player($this->id64());
		} else {
			// purge session
			setcookie("sid", '', time()-$settings['session']['length'], '/');
		}
	}
	function openid_login($identity)
	{
		global $db, $settings;
		
		ereg('http://steamcommunity.com/openid/id/([0-9]+)',$identity, $regs);
		$id64 = $regs[1];
		if(!$id64)
			die("INVALID IDENTITY: ".$identity);
			
		//$sid = md5(time().$id64);
		$data = array('id64' => $id64);
		$ip = $_SERVER['REMOTE_ADDR'];
		$sid = MD5( uniqid( 'tf2stats' ) . $id64 . $ip . $_SERVER[ 'HTTP_USER_AGENT' ] );
		//echo $ip;
		
		
		$db->query("INSERT INTO tf2stats_sessions (id, ip, time, data) VALUES(%s, %s, %s, %s)",
			array($sid,$ip, time(), serialize($data)));
		setcookie("sid", $sid, time()+$settings['session']['length'], '/');
		$this->check_session($sid);
	}
	
	function valid()
	{
		return ($this->id64 > 0);
	}
	function delete()
	{
		global $db;
		$db->query("DELETE FROM tf2stats_sessions WHERE id=%s",array($_COOKIE['sid']));
		unset($this->data);
		$this->id64=fase;
	}
	
	function get($var)
	{
		return $this->data[$var];
	}
	function id64()
	{
		return $this->id64;
	}
	function id()
	{
		return $this->data['id'];
	}

}

?>