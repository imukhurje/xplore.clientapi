<?php
namespace App; // Or App\Yourfolder
class CloudHelper
{
	public function call_cloud_api_command($jsonData){
		$response = $this->call_cloud_api_command_internal($jsonData);
        if(is_object($response)){
            $response = get_object_vars($response);
        }
        return $response;
        
	}
	public function call_cloud_api_command_internal($jsonData)
	{
		$command = $jsonData['command'];
		$feature = $jsonData['feature'];
		$class_name = '\\App\\FeatureCommands\\' . $feature;
		$class = new $class_name();

		$return_json = ['error_code' => 100, 'err_msg' => "Class - {$class_name} : Command - {$command} not found."];
		if(method_exists($class,$command)){
			$return_json = call_user_func_array([$class,$command],[$jsonData]);
    		
		}
		return $return_json;
     
	}
	
}