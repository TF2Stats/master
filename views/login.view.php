<?php 
require_once('classes/view.php');
//var_dump($_GET);
class login_view extends view
{
	public function prepare()
	{
		global $session;
		$this->template='';
		
		if($session->valid())
		{
			$this->template='openid_success';
			return;
		}
			
		
		global $settings, $session;
		try {
		    if(!isset($_GET['openid_mode'])) {
		            $openid = new LightOpenID;
		            $openid->identity = $settings['openid']['provider'];
		            header('Location: ' . $openid->authUrl());
		    } elseif($_GET['openid_mode'] == 'cancel') {
		        $this->template='openid_error';
		    } else {
		        $openid = new LightOpenID;
		        if($openid->validate())
		        {
		        	
		        	$identity = $openid->identity;
		        	$session->openid_login($identity);
		        	//echo $identity;
		        	//var_dump($session);
		        	$this->template='openid_success';
		        	global $SITE;
		        	$SITE['head'] .= '<meta http-equiv="refresh" content="3;url=http://tf2stats.net">';
		        } else
		        	$this->template='openid_error';
		    }
		} catch(ErrorException $e) {
		    $this->template='openid_error';
		}
	}
}

?>