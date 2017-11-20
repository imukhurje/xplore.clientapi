<?php

namespace App\FeatureCommands;

use DB;
use Aws\Credentials\CredentialProvider;
use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
class virtual_machines
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
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        //return $response;
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        $provider = CredentialProvider::instanceProfile();
        $memoizedProvider = CredentialProvider::memoize($provider);
        $creds = ['credentials' => $memoizedProvider, 'version' => 'latest', 'region' => $param['region_id']];
        $client = Ec2Client::factory($creds);
        $result = $client->DescribeInstances();
        $reservations = $result['Reservations'];
        $response_result = [];
        foreach ($reservations as $reservation) {
            $instances = $reservation['Instances'];
            foreach ($instances as $instance) {
                $instanceName = '';
                foreach ($instance['Tags'] as $tag) {
                    if ($tag['Key'] == 'Name') {
                        $instanceName = $tag['Value'];
                    }
                }
                $response_result[] = ['name' => $instanceName];
            }
        }
        $response = ['error_code' => '0', 'error_msg' => '', 'result' => $response_result];
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
    public function bulkadd($param)
    {
        $result = [];
        $response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result];
        // TODO: write your code below and setup $result to whatever you want to send back
        // Note: $result should be a valid JSON
        $result = [];
        echo "This is a test";
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