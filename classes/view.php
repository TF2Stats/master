<?php 
/**
 * view.php
 * handles views
 */

abstract class view 
{
	protected $params;
	protected $request;
	protected $template;
	protected $tab = '';
	protected $head = '';
	protected $title = '';
	protected $canonical = '';
	
	function get_tabs()
	{
		foreach(array('map','item','server','player','more','all_items','blog') as $tab)
			if($tab == $this->tab)
				$ret[$tab] = 'active';
			else
				$ret[$tab] = '';
				
		return $ret;
	}
	
	function __construct($request)
	{	
		$this->request = $request;
	}
	
	function prepare()
	{
		// do stuff
	}
	function additional_head()
	{
		if($this->canonical)
			$this->head .= sprintf('
	<link rel="canonical" href="%s" />', $this->canonical);
		return $this->head;
	}
	function render()
	{
		global $SITE;
		$SITE['tabs'] = $this->get_tabs();
		if($this->template && !$this->ajax)
			page::draw($this->template,$this->params);
		elseif($this->ajax)
			page::draw_standalone($this->template,$this->params);
	}
	function get_title()
	{
		return $this->title;
	}
	function get_canonical()
	{
		return $this->canonical;
	}
	
}

?>