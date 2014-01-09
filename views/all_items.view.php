<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class all_items_view extends view
{
	public function prepare()
	{
		global $schema;
		$this->template="all_items";
		$this->tab = "all_items";
		$this->title = "All TF2 Items";
		
		$this->head = '<script>(function($){$.fn.unveil=function(threshold,callback){var $w=$(window),th=threshold||0,retina=window.devicePixelRatio>1,attrib=retina?"data-src-retina":"data-src",images=this,loaded;this.one("unveil",function(){var source=this.getAttribute(attrib);source=source||this.getAttribute("data-src");if(source){this.setAttribute("src",source);if(typeof callback==="function")callback.call(this);}});function unveil(){var inview=images.filter(function(){var $e=$(this),wt=$w.scrollTop(),wb=wt+$w.height(),et=$e.offset().top,eb=et+$e.height();return eb>=wt-th&&et<=wb+th;});loaded=inview.trigger("unveil");images=images.not(loaded);}$w.scroll(unveil);$w.resize(unveil);unveil();return this;};})(window.jQuery);$(document).ready(function(){$("img").unveil(166);});</script>';
		
		$b = new backpack(false);
		
		foreach($schema['items'] as $i)
		{
			$items[] = $b->get_item($i);
		}
		$this->params['items'] = $items;
	}
}

?>