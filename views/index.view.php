<?php 
require_once('classes/view.php');

class index_view extends view
{
	public function prepare()
	{
		global $db;
		
		$this->template="index";
		$this->title = "Welcome";

		$post = $db->query_first("SELECT post_content, post_title FROM tf2stats_wordpress.wp_posts WHERE post_status = 'publish' ORDER BY post_date DESC LIMIT 1");

		require_once('blog/wp-includes/plugin.php');
		require_once('blog/wp-includes/pomo/translations.php');
		require_once('blog/wp-includes/l10n.php');
		require_once('blog/wp-includes/formatting.php');
		
		$html = wpautop(mb_convert_encoding($post['post_content'],'UTF-8'));
		
		$this->title = 'Welcome';
		$this->params['post'] = $html;
		$this->params['title'] = $post['post_title'];
	}
}

?>