<?php

namespace App\FeatureCommands;

use DB;
class Glacier
{
    public function create($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        return $response;
    }
    public function readall($param)
    {
        $result = [];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        $result[] = ['name' => 'manas', 'glacier_id' => '12', 'status' => 'running'];
        $result[] = ['name' => 'indra', 'glacier_id' => '13', 'status' => 'stopping'];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        return $response;
    }
    public function readone($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        return $response;
    }
    public function remove($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        return $response;
    }
    public function resync($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        return $response;
    }
    public function stop($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        $result = [];
        echo "This is a test";
        return $response;
    }
    public function kill($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        $result = [];
        echo "This is a test";
        return $response;
    }
}