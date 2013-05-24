<?php 
require_once('classes/view.php');

class dead_view extends view
{
	public function prepare()
	{
		page::error("SERVER IS DEAD","YOU NO PRESS F5. <br /><br />YOU WAIT NOW.<br /><br />GOOD.
		<br /> <br /><br /><br />
		<a href=\"#\" id=\"refresh\">How about now?</a> 
		<audio src=\"/static/sound/heavy_no02.mp3\" id=\"no\" ></audio>
		<script type=\"text/javascript\">
		$(document).ready(function() {
			
			var refresh = $('#refresh');
			refresh.click(function() { var no = $('#no')[0]; no.play(); return false; });
		});
		</script>
		",array('image' => "heavy_yell"));
	}
}

?>