<?php 
require_once('classes/view.php');
//var_dump($_GET);
class logout_view extends view
{
	public function prepare()
	{
		global $session;
		$this->template='';
		
		if($session->valid())
		{
			$session->delete();
		}
		header('Location: /');
	}
}

?>