<?php 
class usercp_view extends view
{
	public function prepare()
	{
		$this->template = "usercp";
		$this->title = "User control panel";
		
		$admin=IsUserAdmin();
		
		if($admin)
		{
			global $db;
			
			if($this->request[0] == 'update_schema')
			{
				require_once('classes/backpack.php');
				backpack::update_schema();
				$this->params['success'] = 'Schema updated! Have a nice day <3';
			}
			else if($this->request[0] == 'purge_cache')
			{
				require_once('classes/cache.php');
				cache::clean();
				$this->params['success'] = 'Memcached cache purged! Have a nice day <3';
			}
			/*else if($this->request[0] == 'valve_maps')
			{
				global $list;
				$maps = explode("\n",$list);
				foreach($maps as $map)
				{
					
					list($m, $ext) = explode(".",trim($map));
					//echo $m;
					$db->query("UPDATE tf2_maps SET official=1 where name=%s",array($m));
				}
				$this->params['success'] = 'Maps valveified!';
			
			}*/
		}
		
		$this->params['admin'] = $admin;
	}
}

global $list;
$list = 'arena_badlands.bsp
arena_granary.bsp
arena_lumberyard.bsp
arena_nucleus.bsp
arena_offblast_final.bsp
arena_ravine.bsp
arena_sawmill.bsp
arena_watchtower.bsp
arena_well.bsp
cp_badlands.bsp
cp_coldfront.bsp
cp_degrootkeep.bsp
cp_dustbowl.bsp
cp_egypt_final.bsp
cp_fastlane.bsp
cp_freight_final1.bsp
cp_gorge.bsp
cp_granary.bsp
cp_gravelpit.bsp
cp_junction_final.bsp
cp_manor_event.bsp
cp_mountainlab.bsp
cp_steel.bsp
cp_well.bsp
cp_yukon_final.bsp
ctf_2fort.bsp
ctf_doublecross.bsp
ctf_sawmill.bsp
ctf_turbine.bsp
ctf_well.bsp
item_test.bsp
koth_harvest_event.bsp
koth_harvest_final.bsp
koth_nucleus.bsp
koth_sawmill.bsp
koth_viaduct.bsp
pl_badwater.bsp
pl_goldrush.bsp
pl_hoodoo_final.bsp
plr_hightower.bsp
plr_pipeline.bsp
pl_thundermountain.bsp
pl_upward.bsp
tc_hydro.bsp
tr_dustbowl.bsp
tr_target.bsp
';
?>