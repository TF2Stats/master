<?php
global $settings;

$settings['base_dir'] = '/var/www/html/';
$settings['template_folder'] = $settings['base_dir'] . 'templates/';
$settings['static_folder'] = '/assets/';
$settings['images_folder'] = $settings['static_folder'].'images/';
$settings['admins'] = array(1);
$settings['api_key'] = '';

$settings['db']['url'] = 'localhost';
$settings['db']['user'] = '';
$settings['db']['pass'] = '';
$settings['db']['db'] = 'tf2stats';

$settings['openid']['provider'] = 'http://steamcommunity.com/openid';

$settings['session']['length'] = 60*60*24*90;

$settings['map_type_lookup'] = Array(
	'achievement'	=> 'Achievement',
	'artpass'		=> 'Valve artpass entry',
	'arena'			=> 'Arena',
	'cp'			=> 'Control Point',
	'ctf'			=> 'Capture the Flag',
	'sd'			=> 'Special Delivery',
	'mvm'			=> 'Mann vs. Machine',
	'koth'			=> 'King of the Hill',
	'ph'			=> 'Prop Hunt',
	'pl'			=> 'Payload',
	'plr'			=> 'Payload Race',
	'tc'			=> 'Territory Control',
	'db'			=> 'Dodgeball',
	'tfdb'			=> 'Dodgeball',
	'tr'			=> 'Training',
	'trade'			=> 'Trade',
	'vsh'			=> 'VS Saxton Hale',
	'itemtest'      => 'Item Test',
	'surf'          => 'Surf'
);

$settings['server']['rule_length'] = 60*60;
$settings['server']['rule_ignore'] = array(	'tv_relaypassword' => 'ALL',
	'deathmatch' => 'ALL',
	'sv_voiceenable' => 1,
	'tf_overtime_nag' => 0,
	'tf_playergib' => 1,
	'sv_gravity' => 800,
	'mp_friendlyfire' => 0,
	'tf_gamemode_payload' => 'ALL',
	'mp_footsteps' => 1,
	'tf_gamemode_ctf' => 'ALL',
	'mp_maxrounds' => 0,
	'sv_pausable' => 0,
	'tv_enable' => 'ALL',
	'tf_teamtalk' => 0,
	'tf_arena_use_queue' => 1,
	'tv_password' => 'ALL',
	'mp_autoteambalance' => 0,
	'mp_stalemate_enable' => 0,
	'coop' => 'ALL',
	'tf_weapon_criticals' => 1,
	'tf_use_fixed_weaponspreads' => 0,
	'tf_gamemode_cp' => 'ALL',
	'sv_cheats' => 0,
	'sv_password' => 'ALL',
	'tf_ctf_bonus_time' => 3,
	'tf_birthday' => 0,
	'tf_gamemode_arena' => 'ALL',
	'mp_windifference' => 0,
	'sv_footsteps' => 1,
	'mp_highlander' => 0,
	'sv_noclipaccelerate' => 5,
	'tf_bot_count' => 0,
	'mp_falldamage' => 0,
	'tf_arena_round_time' => 0,
	'mp_autocrosshair' => 1,
	'mp_forcerespawn' => 1,
	'mp_weaponstay' => 0,
	'sv_rollspeed' => 200,
	'sv_accelerate' => 10,
	'mp_fadetoblack' => 'ALL',
	'mp_tournament_stopwatch' => 0,
	'sv_stepsize' => 'ALL',
	'mp_match_end_at_timelimit' => '0',
	'tf_allow_player_use' => 'ALL',
	'tf_maxspeed' => 400,
	'r_AirboatViewDampenFreq' => 'ALL',
	'r_AirboatViewDampen' => 'ALL',
	'r_AirboatViewDampenDamp' => 'ALL',
	'r_AirboatViewZHeight' => 'ALL',
	'r_JeepViewZHeight' => 'ALL',
	'r_JeepViewDampenDamp' => 'ALL',
	'r_JeepViewDampenFreq' => 'ALL',
	'r_VehicleViewDampen' => 'ALL',
	'sv_specspeed' => 'ALL',
	'mp_allowNPCs' => 'ALL',
	'mp_fraglimit' => 0,
	'mp_forceautoteam' => 0,
	'mp_teamplay' => 'ALL',
	'sv_specnoclip' => 1,
	'sv_maxspeed' => 320,
	'sv_waterfriction' => 'ALL',
	'tf_arena_max_streak' => 0,
	'mp_teams_unbalance_limit' => 0,
	'sv_bounce' => 0,
	'mp_stalemate_meleeonly' => 0,
	'decalfrequency' => 'ALL',
	'sv_airaccelerate' => 10,
	'tf_force_holidays_off' => 0,
	'tf_damage_disablespread' => 0,
	'mp_disable_respawn_times' => 0,
	'tf_arena_first_blood' => 1,
	'tf_arena_override_cap_enable_time' => -1,
	'sv_rollangle' => 'ALL',
	'tf_arena_force_class' => 0,
	'sv_friction' => 4,
	'mp_flashlight' => 'ALL',
	'mp_tournament' => 0,
	'sv_noclipspeed' => 'ALL',
	'sv_wateraccelerate' => 'ALL',
	'sv_specaccelerate' => 'ALL',
	'mp_teamlist' => 'ALL',
	'mp_respawnwavetime' => 10,
	'tf_arena_preround_time' => 'ALL',
	'sv_stopspeed' => 'ALL'
);

$settings['cache']['length'] = 60*60;
$settings['cache']['player'] = 60*60*12;
$settings['cache']['backpack'] = 60*30;
$settings['cache']['folder'] = '/tmp/';

$settings['upload']['folder']['maps'] = $settings['base_dir'] . 'images/maps/original/';
$settings['upload']['folder']['items'] = $settings['base_dir'] . 'images/items/original/';
$settings['upload']['folder']['effects'] = $settings['base_dir'] . 'images/effects/original/';
$settings['upload']['resized']['maps'] = $settings['base_dir'] . 'images/maps/sized/';
$settings['upload']['resized']['items'] = $settings['base_dir'] . 'images/items/sized/';
$settings['upload']['resized']['effects'] = $settings['base_dir'] . 'images/effects/sized/';
$settings['upload']['resized_ext']['maps'] = '/images/maps/sized/';
$settings['upload']['original_ext']['maps'] = '/images/maps/original/';
$settings['upload']['resized_ext']['items'] = '/images/items/sized/';
$settings['upload']['resized_ext']['effects'] = '/images/effects/sized/';
$settings['upload']['allowed_images'] = array("jpg","jpeg","gif","png");
$settings['upload']['filter_url'] = '/i/';

/*$settings['items']['cosmetic'] = array (
										160, 
										161, 
										169,
										266,
										294, // Poker night pistol (lugermorph)
										297, // Poker night watch (Enthusiast's timepiece) 
										297 // Poker night minigun (Iron curtain)
);*/

$settings['items']['ignore'] = array (
	122
);

$SITE = array (
	'title' => 'TF2Stats'
);

?>