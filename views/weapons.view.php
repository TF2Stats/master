<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');

global $base_weapons, $slot_fix;

/*
 * This is pretty messy, so lets put some comments in!
 */

// This array controls the base weapons, and their cosmetic variants.
// 'base' is the core weapon, and 'adds' are the cosmetic variants we want to add in
// NOTE: Add the base as an 'add' as well, since community and valve weapons will
// use the original ID, unlike renamed ones!
// ALSO
// the order used here will reflect the order on the site!
// That includes slots shown, so don't forget to add them
// in even if there are no items to combine!
$base_weapons = array(
	'Scout' => array (
		'primary' => array (
			'base' => 13,
			'add' => array ( 200, 13 )
		),
		'secondary' => array (
			'base' => 23,
			'add' => array ( 209, 23 ) // Poker night: 294 max: 160
		),
		'melee' => array (
			'base' => 0,
			'add' => array ( 190, 0 )
		)
	),
	'Soldier' => array (
		'primary' => array (
			'base' => 18,
			'add' => array ( 205, 18 )
		),
		'secondary' => array (
			'base' => 10,
			'add' => array ( 199, 10 )
		),
		'melee' => array (
			'base' => 6,
			'add' => array ( 196, 6 ) // pan: 264
		)
	),
	'Pyro' => array (
		'primary' => array (
			'base' => 21,
			'add' => array ( 208, 21 )
		),
		'secondary' => array (
			'base' => 12,
			'add' => array ( 199, 12 )
		),
		'melee' => array (
			'base' => 2,
			'add' => array ( 192, 2 )
		)
	),
	'Demoman' => array (
		'secondary' => array (
			'base' => 19,
			'add' => array ( 19, 206 )
		),
		'primary' => array (
			'base' => 20,
			'add' => array ( 207, 20 )
		),
		'melee' => array (
			'base' => 1,
			'add' => array ( 1, 191 ) // pan: 264 headtaker: 266
		)
	),
	'Heavy' => array (
		'primary' => array (
			'base' => 15,
			'add' => array ( 202, 15 ) // curtain: 298
		),
		'secondary' => array (
			'base' => 11,
			'add' => array ( 199, 11 )
		),
		'melee' => array (
			'base' => 5,
			'add' => array ( 195, 5 )
		)
	),
	'Engineer' => array (
		'primary' => array (
			'base' => 9,
			'add' => array ( 199, 9 )
		),
		'secondary' => array (
			'base' => 22,
			'add' => array ( 209, 22 ) // 294, 160 lugers
		),
		'melee' => array (
			'base' => 7,
			'add' => array ( 197, 7 )
		)
	),
	'Medic' => array (
		'primary' => array (
			'base' => 17,
			'add' => array ( 204, 17 )
		),
		'secondary' => array (
			'base' => 29,
			'add' => array ( 211, 29 )
		),
		'melee' => array (
			'base' => 8,
			'add' => array ( 198, 8 )
		)
	),
	'Sniper' => array (
		'primary' => array (
			'base' => 14,
			'add' => array ( 201, 14 )
		),
		'secondary' => array (
			'base' => 16,
			'add' => array (203, 16 )
		),
		'melee' => array (
			'base' => 3,
			'add' => array ( 193, 3 )
		)
	),
	'Spy' => array (
		'secondary' => array (
			'base' => 24,
			'add' => array ( 210, 24 ) // 161 big kill
		),
		'pda2' => array (
			'base' => 30,
			'add' => array (212, 30 ) // 297 ttg watch
		),
		'melee' => array (
			'base' => 4,
			'add' => array ( 194, 4 )
		)
	),
);


class weapons_view extends view
{
	public function prepare()
	{
		global $schema, $base_weapons, $slot_order, $settings;
		$this->template="weapons";
		$this->tab = 'item';
		$this->title = 'Weapon statistics';
		$this->canonical = 'http://tf2stats.net/weapons/';
		$sort_table = array(
			'owned' => 'owned',
			'equipped' => 'equipped',
			'own_equip' => 'owned_equipped_key'
		);
		
		$b = new backpack(false);
		
		$sortkey = 'owned';
		if($this->request['sort'] && array_key_exists($this->request['sort'],$sort_table))
		{
			$sortkey = $sort_table[$this->request['sort']];
			$this->params['sort'][$this->request['sort']] = 'selected';
		} else 
			$this->params['sort']['owned'] = 'selected';

		$item_stats = cache::Memcached()->get('item_stats');
		if( $item_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'item_stats.json');
			$item_stats = json_decode($json, true);
			cache::Memcached()->set('item_stats', $item_stats, time() + 60*15);
		}
		
		// First. build a class->slot->items[] array for our stats
		foreach($item_stats['items'] as $defindex => $s)
		{
			// Skip tokens
			if($defindex > 5000)
				continue;
				
			$si = $schema['items'][$defindex];
			
			if(in_array($si['item_slot'],array('primary','secondary','melee','pda','pda2')))
			{
				$i = $b->get_item($si);
				$i['owned'] = ($s['unique_total'] / $item_stats['total_players']);
				//if($i['owned'] > 1)
				//	$i['owned'] = 1; 
				
				if( !empty( $i['used_by_classes'] ) )
				{
					foreach($i['used_by_classes'] as $c)
					{
						$ci = class_to_int($c);
						$i['equipped'] = ($s['equipped'][$ci] / $item_stats['total_players']);
						$i['owned_equipped'] = ($s['equipped'][$ci] / $s['unique_total']);
						$i['owned_equipped_key'] = $i['owned_equipped'] * 1000;
						$key = $i[$sortkey];
						while($weapons[$c][$si['item_slot']][(string)$key] > 0)
							$key+=0.001;
						$weapons[$c][$si['item_slot']][(string)$key] = $i;
						$weapon_totals[$c][$si['item_slot']] += $i['equipped'];

					}
				}
			}
		}
		// Now go through and combine the cosmetics defined above, as well as determine
		// stats for the 'base' weapon since valve's solution is to leave the slot
		// blank if we're using one
		foreach($base_weapons as $class => $slots)
			foreach($slots as $slot => $s)
			{
				$si = $schema['items'][ $s['base']];
				$i = $b->get_item($si);
				//$i['owned'] = ($s['total'] / $item_stats['total_players']);
				//if($i['owned'] > 1)
				//	$i['owned'] = 1; 
				//$i['equipped'] = ($s['total_equipped'] / $item_stats['total_players']); 
				//$i['owned_equipped'] = ($s['total_equipped'] / $s['total']); 
				$i['equipped'] = 1 - $weapon_totals[$class][$slot];
				$i['owned'] = 1;
				//echo $i['equipped'];
				if(!$s['add'])
					continue;
				foreach($s['add'] as $a)
				{
					foreach($weapons[$class] as $csl => $t)
						foreach($weapons[$class][$csl] as $k => $w)
							if($w['defindex'] == $a)
							{
								$sl = $csl;
								$other = $w;
								$key = $k;
								break;
							}
					$i['equipped'] += $other['equipped'];
					// Fix that silly rounding error causing the
					// pipe launcher to be used by 100.01%
					if($i['equipped'] > 1)
						$i['equipped'] = 1;
					$weapons[$class][$sl][$key]['hide'] = true;

				}
				
				$i['owned_equipped'] = $i['equipped'];
				$i['owned_equipped_key'] = $i['owned_equipped'] * 1000;
				$key = $i[$sortkey];
				while($weapons[$class][$slot][(string)$key])
					$key+=0.001;
				
				$weapons[$class][$slot][(string)$key] = $i;
				unset($key);
			}

			

		// finally, sort the results
		// Instead of wasting time sorting arrays and subarrays we can just
		// reconstruct a new array in the order of the slot array above
		// and only sort the bits that really need it.
		foreach($base_weapons as $class => $slots)
			foreach($slots as $slot => $info)
			{
				$out[$class][$slot] = $weapons[$class][$slot];
				krsort($out[$class][$slot]);
			}
			
		$this->params['profiles'] = $item_stats['total_players'];
		$this->params['time'] = cache::date('item_stats.json');
		$this->params['weapons'] = $out;
	}
}

?>