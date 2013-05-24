<?php 
define('MAX_LOD_LEVELS', 8);



//$out = array( 'positions' => $positions, 'normals' => $normals, 'uv' => $uv, 'indices' =>$indices);
//echo json_encode($out);

class vvd_file
{
	private $vertex_data;
	
	function __construct($filename)
	{

		//$filename = "../static/models/$id.vvd";
		//$filename = "../static/models/demo.mdl";
		
		$fp = fopen($filename, 'rb');
		$data = fread($fp, filesize($filename));
		
		if(!$data)
			die("Invalid VVD");
			
		$format = 
			'i1id/'.
			'i1version/'.
			'l1checksum/'.
			'i1num_lods/'.
			'i'.MAX_LOD_LEVELS.'num_lod_vertexes/'.
			'i1num_fixups/'.
			'i1fixup_table_start/'.
			'i1vertex_data_start/'.
			'i1tangent_data_start'
			;
		$header = unpack ($format, $data);
		
		if($header['version'] != 4)
			die("Unknown vvd version ".$header['version']);
		//echo "##\nHeader\n##\n\n";
		//var_dump($header);
		
		/* Vertex data */
		
		$vertex_section = substr($data, $header['vertex_data_start']);
		
		//echo "\n\n##\nVertices\n##\n\n";
		for($x=0;$x<$header['num_lod_vertexes1'];$x++)
		{
			$format = 
				'f4bone_weight/'.
				'f3vec_position/'.
				'f3vec_normal/'.
				'f2vec_tex_coord'
			;
			$vertex = unpack ($format, $vertex_section);
			//if($x < 10)
			//	var_dump($vertex);
			$this->vertex_data[] = $vertex;
			$vertex_section = substr($vertex_section,48);
		}
		
	}	
	function get_vertex_data()
	{
		return $this->vertex_data;
	}
}
?>