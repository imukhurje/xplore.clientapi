<?php

namespace App\FeatureCommands;

use DB;
class Lambda
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
        $result = [['name' => "Aslam", "EmployeeID" => "1127"], ['name' => "Manas", "EmployeeID" => "1129"]];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
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
    public function start($param)
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