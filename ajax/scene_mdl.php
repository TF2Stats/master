<?php 
require_once('scene_vtx.php');
require_once('scene_vvd.php');
$id = str_replace('.','',$_REQUEST['id']);

$mdl = new mdl_file( "../static/$id.mdl");
if($_REQUEST['data'] == 'model')
	echo json_encode($mdl->get_model_data());
elseif($_REQUEST['data'] == 'textures')
	echo json_encode(
		array (
				'textures' => $mdl->get_textures(),
				'bone' => $mdl->get_attachment_bone(),
				'bones' => $mdl->get_bones(),
				'hull_position' => $mdl->get_hull_position()
			)
		);

class mdl_file
{
	private $header;
	private $textures;
	private $bones;
	
	function __construct($filename)
	{
		global $data;
		//$filename =;
		//$filename = "../static/models/demo.mdl";
		
		if(!file_exists($filename))
			die("Invalid MDL");
		
		$fp = fopen($filename, 'rb');
		$data = fread($fp, filesize($filename));
			
		if(!$data)
			die("Invalid MDL");
		
		$format = 
			'i1id/'.
			'i1version/'.
			'i1unknown1/'.
			'a64name/'.
			'i1data_length/'.
			'f3eye_position/'. // vectors!; 
			'f3illum_position/'.
			'f3hull_min/'.
			'f3hull_max/'.
			'f3view_bbmin/'.
			'f3view_bbmax/'.
			'i1flags/'.
			'i1bone_count/'.
			'i1bone_offset/'.
			'i1bone_controller_count/'.
			'i1bone_controller_offset/'.
			'i1hitbox_count/'.
			'i1hitbox_offset/'.
			'i1localanim_count/'.
			'i1localanim_offset/'.
			'i1localseq_count/'.
			'i1localseq_offset/'.
			'i1activitylistversion/'.
			'i1eventsindexed/'.
			'i1texture_count/'.
			'i1texture_offset/'.
			'i1texturedir_count/'.
			'i1texturedir_offset/'.
			'i1skinreference_count/'.
			'i1skinfamily_count/'.
			'i1skinreference_index/'.
			'i1bodypart_count/'.
			'i1bodypart_index/'.
			'i1attachment_count/'.
			'i1attachment_offset/'.
			'i1localnode_count/'.
			'i1localnode_index/'.
			'i1localnode_name_index/'.
			'i1flexdesc_count/'.	
			'i1flexdesc_index/'.
			'i1flexcontroller_count/'.
			'i1flexcontroller_index/'.	
			'i1flexrules_count/'.
			'i1flexrules_index/'.
			'i1ikchain_count/'.
			'i1ikchain_index/'.
			'i1mouths_count/'.
			'i1mouths_index/'.
			'i1localposeparam_count/'.
			'i1localposeparam_index/'.
			'i1surfaceprop_index/'.
			'i1keyvalue_index/'.
			'i1keyvalue_count/'.
			'i1iklock_count/'.
			'i1iklock_index/'.
			'f1mass/'.
			'i1contents/'.
			'i1includemodel_count/'.
			'i1includemodel_index/'.
			'i1virtualModel/'.
			'i1animblocks_name_index/'.
			'i1animblocks_count/'.
			'i1animblocks_index/'.
			'i1animblockModel/'.
			'i1bonetablename_index/'.
			'i1vertex_base/'.
			'i1offset_base/'.
			'c1directionaldotproduct/'.
			'c1rootLod/'.
			'c1numAllowedRootLods/'.
			'c1unused/'.
			'i1unused/'.
			'i1flexcontrollerui_count/'.
			'i1flexcontrollerui_index/'.
			'i1studiohr2index/'.
			'i1unused'
			;
		$header = unpack ($format, $data);
		$this->header = $header;

		if($header['version'] < 48 || $header['version'] > 49)
			die("Unknown MDL version ".$header['version']);
		
		/* Textures */
		
		$texture_section = substr($data, $header['texture_offset']);

		for($x=0;$x<$header['texture_count'];$x++)
		{
			$format = 
				'i1name_offset/'.
				'i1flags/'.
				'i2unused/'.
				'i1material/'.
				'i1client_material/'.
				'i10unused'	
			;
			$texture = unpack ($format, $texture_section);
			$texture['name'] = null_terminated_string(substr($texture_section,$texture['name_offset']));
			$texture['name_fixed'] = case_correct_texture($texture['name']);
			//var_dump($texture);
			$texture_section = substr($texture_section,64);
			$this->textures[] = $texture;
		}
		
		/* bones */
		
		$bones = array();
		
		$bone_section = substr($data, $header['bone_offset']);
		$section_size = 216; // 67?
		for($x=0;$x<$header['bone_count'];$x++)
		{
			$format = 
				'i1name_offset/'.
				'i1parent/'.
				'i6bone_controller/'.
				'f3position'
				
			;
			$bone = unpack ($format, $bone_section);
			$bone['name'] = null_terminated_string(substr($bone_section,$bone['name_offset']));
			$bone['offset'] = $header['bone_offset'] + $x*$section_size;
			//var_dump($bone);
			//$b = new bone($bone);
			//$b->populate_models();
			$this->bones[] = $bone;
			
			$bone_section = substr($bone_section,$section_size);
		}
		
		
		
		//var_dump($this->bodyparts);
		//var_dump($this->textures);*/
	}
	function get_textures()
	{
		return $this->textures;
	}
	function get_model_data()
	{
		global $vvd, $vtx, $id;
		$vvd = new vvd_file("../static/$id.vvd");
		$vtx = new vtx_file("../static/$id.sw.vtx");
		//var_dump($vtx->bodyparts);
		return $vtx->bodyparts[0]->models[0]->lods[0]->meshes[0]->stripgroups[0]->stripgroup_data;
	}
	function get_bones()
	{
		return $this->bones;
	}
	function get_bone($name)
	{
		foreach($this->bones as $b)
			if($b['name'] == $name)
				return $b;
		return false;
		//return $this->bones[0];
	}
	function get_attachment_bone()
	{
		$pos = array ('position1' => 0, 'position2' => 0, 'position3' => 0);
		for($x=0;$x<count($this->bones);$x++)
			if(stripos($this->bones[$x]['name'],'jiggle') === false)
			{
				return $this->bones[$x];
				$pos['position1'] += $this->bones[$x]['position1'];
				$pos['position2'] += $this->bones[$x]['position2'];
				$pos['position3'] += $this->bones[$x]['position3'];
			}
		return $pos;
		
		
		$bone = $this->get_bone('bip_head');
		if($bone) return $bone;
		
		$bone = $this->get_bone('prp_helmet');
		if($bone) return $bone;
		
		$bone = $this->get_bone('prp_hat');
		if($bone) return $bone;
		
		$bone = $this->get_bone('bip_neck');
		if($bone) return $bone;
		
		$bone = $this->get_bone('bip_pelvis');
		if($bone) return $bone;
		
		$bone = $this->bones[1];
		if($bone) return $bone;
		
		
		
		$bone = $this->bones[0];
		if($bone) return $bone;
		
		return 0;
	}
	
	function get_hull_position()
	{
		$x = ($this->header['hull_min1'] + $this->header['hull_max1']) / 2;
		$y = ($this->header['hull_min2'] + $this->header['hull_max2']) / 2;
		$z = ($this->header['hull_min3'] + $this->header['hull_max3']) / 2;
		return array('position1' => $x, 'position2' => $y, 'position3' => $z);
	}
}



/* Stuff */

class bodypart
{
	private $name_offset;
	private $model_count;
	private $model_index;
	private $base;
	private $name;
	private $offset;
	private $models;
	
	function __construct($data)
	{
		foreach($data as $key => $val)
			$this->$key = $val;
 	}
 	
 	function populate_models()
 	{
 		global $data;
		$models = array();

		$model_section = substr($data, $this->model_index+$this->offset);
		for($x=0;$x<$this->model_count;$x++)
		{
			$format_length = 148;
			$format = 
				'a64name/'.
				'i1type/'.
				'f1boundingradius/'.
				'i1mesh_count/'.
				'i1mesh_index/'.
				'i1tangents_index/'.
				'i1attachment_count/'.
				'i1attachment_index/'.
				'i1eyeball_count/'.
				'i1eyeball_index/'.
				'i8unused'
			;
			$model = unpack ($format, $model_section);
			$model['name'] = null_terminated_string(substr($model_section,$model['name_offset']));
			$model['offset'] = $this->model_index+$this->offset + $x * $format_length;
			$m = new model($model);
			$m->populate_meshes();
			$this->models[] = $m;
	
			$model_section = substr($model_section,$format_length);
		}
 	}
}

class model
{
	private $mesh_count;
	private $mesh_index;
	private $name;
	private $type;
	private $offset;
	
	function __construct($params)
	{
		foreach($params as $key => $val)
			$this->$key = $val;
	}
	
	function populate_meshes()
	{
		/*global $data;
		$meshs = array();

		$mesh_section = substr($data, $this->mesh_index+$this->offset);
		for($x=0;$x<$this->mesh_count;$x++)
		{
			$format_length = 148;
			$format = 
				'f3positioin/'.
				'f3normal/'.
				'f4tangents/'.
				'f2texcoord/'.
				''
			;
			$mesh = unpack ($format, $mesh_section);
			$mesh['name'] = null_terminated_string(substr($mesh_section,$mesh['name_offset']));
			$mesh['offset'] = $this->mesh_index+$this->offset + $x * $format_length;
			var_dump($mesh);
			//$m = new mesh($mesh);
			//$m->populate_meshes();
			
			$meshs[] = $m;
	
			$mesh_section = substr($mesh_section,$format_length);
		}*/
 	}
}

function null_terminated_string($str)
{
	$end = strpos($str, chr(0));
	if($end === false)
		return 'NO TERMINATION FOR OFFSET.';
	return substr($str,0,$end);
}
/*
function case_correct_texture($old_file)
{
	$old_file = str_replace('\/','/',$old_file); // Fix slashes
	$dir_bits = explode('/',$old_file);
	$filename = $dir_bits[count($dir_bits)-1];
	unset($dir_bits[count($dir_bits)-1]);
	$dir = implode('/',$dir_bits);
	$d = dir(sprintf('../static/%s',$dir));
	while($test_file = $d->read())
	{
		$test_bits = explode('.',$test_file);
		$test = $test_bits[0];
		if(strcasecmp($test,$filename) === 0)
			return sprintf('%s/%s',$dir,$filename);
	}
	return $out;
}*/
function case_correct_texture($old_file)
{
	$old_file = str_replace('\/','/',$old_file); // Fix slashes
	$dir_bits = explode('/',$old_file);
	$filename = $dir_bits[count($dir_bits)-1];
	unset($dir_bits[count($dir_bits)-1]);
	$dir = implode('/',$dir_bits);
	$d = dir('../static/models/textures');
	while($test_file = $d->read())
	{
		$test_bits = explode('.',$test_file);
		$test = $test_bits[0];
		if(strcasecmp($test,$filename) === 0)
			return $test.'.png';
	}
	return $out;
}

?>