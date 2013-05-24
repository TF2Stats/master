<?php 
ini_set('display_errors',1);

if($_REQUEST['test'])
{
	$vtx = new vtx_file("../static/models/player/items/soldier/hat_first.sw.vtx");
}

class vtx_file
{
	public $bodyparts;
	
	function __construct($filename)
	{
		global $data;

		//require_once('scene_vvd.php');
		
		define('MAX_NUM_BONES_PER_VERT', 3);
		
		//$filename = "../static/models/$id.sw.vtx";
		//$filename = "../static/models/demo.mdl";
		
		$fp = fopen($filename, 'rb');
		$data = fread($fp, filesize($filename));
		
		if(!$data)
			die("Invalid VTX");
			
		$format = 
			'i1version/'.
			'i1vert_cache_size/'.
			'S1max_bones_per_strip/'.
			'S1max_bones_per_face/'.
			'i1max_bones_per_vert/'.
			'l1checksum/'.
			'i1num_lods/'.
			'i1material_replacement_list_offset/'.
			'i1bodypart_count/'.
			'i1bodypart_offset'
			;
		$header = unpack ($format, $data);
		
		if($header['version'] != 7)
			die("Unknown vtx version ".$header['version']);
		//echo "##\nHeader\n##\n\n";
		//if($_REQUEST['test'])
		//	var_dump($header);
		
		/* Body data */
		
		$bodypart_section = substr($data, $header['bodypart_offset']);
		$section_size = 8;
		//echo "\n\n##\nVertices\n##\n\n";
		for($x=0;$x<$header['bodypart_count'];$x++)
		{
			$format = 
				'i1model_count/'.
				'i1model_offset'
			;
			$bodypart = unpack ($format, $bodypart_section);
			$bodypart['offset'] = $header['bodypart_offset'] + $x*$section_size;
			$b = new vtx_bodypart($bodypart);
			$this->bodyparts[] = $b;
			$bodypart_section = substr($bodypart_section,$section_size);
			//var_dump($bodypart);
		}
		
		/* Vert number table */
		
		$vertnum_section = substr($data, $bodypart['']);
	}
}

class vtx_bodypart
{
	private $model_count;
	private $model_offset;
	private $offset;
	public $models;
	
	function __construct($unpacked)
	{
		foreach($unpacked as $key => $val)
			$this->$key = $val;
			
		$this->populate_models();
	}
	
	function populate_models()
	{
		global $data;
		
		$model_section = substr($data, $this->model_offset+$this->offset);
		$section_length = 8; // TODO: CHECKME
		for($x=0;$x<$this->model_count;$x++)
		{
			$format = 
				'i1lod_count/'.
				'i1lod_offset'
			;
			$model = unpack ($format, $model_section);
			$model['offset'] = $this->model_offset+$this->offset + $x*$section_length;
			//if($x < 10 && $_REQUEST['test'])
			//	var_dump($model);
			$m = new vtx_model($model);
			$this->models[] = $m;
			//var_dump($model);
			$model_section = substr($model_section,$section_length);
		}
	}
}

class vtx_model
{
	private $lod_count;
	private $lod_offset;
	private $offset;
	public $lods;
	
	function __construct($unpacked)
	{
		foreach($unpacked as $key => $val)
			$this->$key = $val;
			
		$this->populate_lods();
	}
	
	function populate_lods()
	{
		global $data;
		
		$lod_section = substr($data, $this->lod_offset+$this->offset);
		$section_length = 12; // TODO: CHECKME
		for($x=0;$x<$this->lod_count;$x++)
		{
			$format = 
				'i1mesh_count/'.
				'i1mesh_offset/'.
				'f1switch_point'
			;
			$lod = unpack ($format, $lod_section);
			$lod['offset'] = $this->lod_offset+$this->offset + $x*$section_length;
			//if($x < 10 && $_REQUEST['test'])
			//	var_dump($lod);
			if($lod['mesh_count'] > 20)
				die("Sanity checks failed at LOD");
			$l = new vtx_lod($lod);
			$this->lods[] = $l;
			
			//var_dump($lod);
			$lod_section = substr($lod_section,$section_length);
		}
	}
}

class vtx_lod
{
	private $mesh_count;
	private $mesh_offset;
	private $offset;
	public $meshes;
	
	function __construct($unpacked)
	{
		foreach($unpacked as $key => $val)
			$this->$key = $val;
			
		$this->populate_meshes();
	}
	
	function populate_meshes()
	{
		global $data;
		
		$mesh_section = substr($data, $this->mesh_offset+$this->offset);
		
		$section_length = 5; // TODO: CHECKME
		for($x=0;$x<$this->mesh_count;$x++)
		{
			$format = 
				'i1stripgroup_count/'.
				'i1stripgroup_offset/'.
				'c1flags'
			;
			$mesh = unpack ($format, $mesh_section);
			$mesh['offset'] = $this->mesh_offset+$this->offset + $x*$section_length;
			if($mesh['stripgroup_count'] > 20)
			{
				echo "<pre>";
				var_dump($this->meshes);
				echo "\n\nOffending member ($x @ {$this->mesh_offset} + {$this->offset}):\n\n";
				var_dump($mesh);
				echo "</pre>";
				die("Sanity checks failed at stripgroups");
			}
			$m = new vtx_mesh($mesh);
			$this->meshes[] = $m;
			$mesh_section = substr($mesh_section,$section_length);
		}
	}
}

class vtx_mesh
{
	private $stripgroup_count;
	private $stripgroup_offset;
	private $flags;
	
	private $offset;
	public $stripgroups;
	
	function __construct($unpacked)
	{
		foreach($unpacked as $key => $val)
			$this->$key = $val;
			
		$this->populate_stripgroups();
	}
	
	function populate_stripgroups()
	{
		global $data;
		
		$stripgroup_section = substr($data, $this->stripgroup_offset+$this->offset);
		$section_length = 5; // TODO: CHECKME
		for($x=0;$x<$this->stripgroup_count;$x++)
		{
			$format = 
				'i1vertex_count/'.
				'i1vertex_offset/'.
				'i1index_count/'.
				'i1index_offset/'.
				'i1strip_count/'.
				'i1strip_offset/'.
				'c1flags'
			;
			$stripgroup = unpack ($format, $stripgroup_section);
			$stripgroup['offset'] = $this->stripgroup_offset+$this->offset + $x*$section_length;
			if($x < 10 && $_REQUEST['test'])
				var_dump($stripgroup);
			$s = new vtx_stripgroup($stripgroup);
			$this->stripgroups[] = $s;
			$stripgroup_section = substr($stripgroup_section,$section_length);
		}
	}
}

class vtx_stripgroup
{
	private $vertex_count;
	private $vertex_offset;
	private $index_count;
	private $index_offset;
	private $strip_count;
	private $strip_offset;
	private $flags;
	
	private $offset;
	private $indexes;
	private $vertices;
	private $vertex_order;
	
	public $stripgroup_data;
	
	function __construct($unpacked)
	{
		foreach($unpacked as $key => $val)
			$this->$key = $val;
			
		$this->populate_vertexs();
		$this->populate_indexs();
		$this->build_vertex_indices();
	}
	
	function build_vertex_indices()
	{
		global $vvd;
		if(!$_REQUEST['test'])
			$vertex_data = $vvd->get_vertex_data();
		foreach($this->indexes as $i)
			$this->vertex_order[] = $this->vertices[$i['index']]['vertex_id'];
		
		$x=0;
		foreach($this->vertex_order as $i)
		{
			$v = $vertex_data[$i];
			

			$positions[] = $v['vec_position1'];
			$positions[] = $v['vec_position2'];
			$positions[] = $v['vec_position3'];
			
			$normals[] = $v['vec_normal1'];
			$normals[] = $v['vec_normal2'];
			$normals[] = $v['vec_normal3'];
			
			$uv[] = $v['vec_tex_coord1'];
			$uv[] = 1-$v['vec_tex_coord2'];
			
			$indices[] = $x++;
		}
		$this->stripgroup_data = array('positions' => $positions, 'normals' => $normals, 'uv' => $uv, 'indices' => $indices);
		
		//echo json_encode(array('positions' => $positions, 'normals' => $normals, 'uv' => $uv, 'indices' => $indices));
	}
	
	function populate_vertexs()
	{
		global $data;
		
		$vertex_section = substr($data, $this->vertex_offset+$this->offset);
		$section_length = 9; // TODO: CHECKME
		for($x=0;$x<$this->vertex_count;$x++)
		{
			$format = 
				'c'.(MAX_NUM_BONES_PER_VERT).'bone_weight_index/'.
				'c1bone_count/'.
				'Svertex_id'
			;
			$vertex = unpack ($format, $vertex_section);
			$vertex['offset'] = $this->vertex_offset+$this->offset + $x*$section_length;
			//if($x < 10)
			//	var_dump($vertex);
			//$m = new vtx_vertex($vertex);
			$this->vertices[] = $vertex;
			$vertex_section = substr($vertex_section,$section_length);
		}
	}
	
	function populate_indexs()
	{
		global $data;
		
		$index_section = substr($data, $this->index_offset+$this->offset);
		$section_length = 2; // TODO: CHECKME
		for($x=0;$x<$this->index_count;$x++)
		{
			$format = 
				'Sindex'
			;
			$index = unpack ($format, $index_section);
			$index['offset'] = $this->index_offset+$this->offset + $x*$section_length;
			if($x < 10 && $_REQUEST['test'])
				var_dump($index);
			//$m = new vtx_index($index);
			$this->indexes[] = $index;
			$index_section = substr($index_section,$section_length);
		}
	}
}
?>
