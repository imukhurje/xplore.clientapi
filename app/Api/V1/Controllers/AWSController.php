<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Validator;
use Config;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;

use Aws\Credentials\CredentialProvider;

use Aws\Sdk;
use Aws\S3\S3Client;

use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;

use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;

use Aws\CostandUsageReportService\CostandUsageReportServiceClient;
use Aws\CostandUsageReportService\Exception\CostandUsageReportServiceException;

use Aws\CloudWatch\CloudWatchClient;
use Aws\CloudWatch\Exception\CloudWatchException;


class AWSController extends Controller
{
    use Helpers;

    public function command(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $param = $request->all();
        $awsclass = $param['awsclass'];
        $awscommand = $param['awscommand'];
        //$awskey = $param['awskey'];
        //$awssecret = $param['awssecret'];
        $awsregion = $param['awsregion'];
        $client = [];

        $provider = CredentialProvider::instanceProfile();
        $memoizedProvider = CredentialProvider::memoize($provider);
        $creds = [
                    'credentials' => $memoizedProvider,
                    'version' => 'latest',
                    'region' => $awsregion,
                 ];
        

        switch($awsclass){
            case 'Ec2Client':   $client = Ec2Client::factory($creds);
                                break;
            case 'S3Client':   $client = S3Client::factory($creds);
                                break;
            case 'CloudFormationClient':   $client = CloudFormationClient::factory($creds);
                                break;
            case 'CostandUsageReportServiceClient':   $client = CostandUsageReportServiceClient::factory($creds);
                                break;
            case 'CloudWatchClient':   $client = CloudWatchClient::factory($creds);
                                break;                    
        }

        if(method_exists($client, '__call')){
            try {
                $result = call_user_func_array([$client,'__call'],[$awscommand,[$param['payload']]]);
            }
            catch (Ec2Exception $exc){
                $code = $exc->getAwsErrorCode();
                $msg = $exc->getAwsErrorMessage();
                $return_json = ['error_code' => $code, 'error_msg' => $msg, 'result' => []];
                return $return_json;
            }
            catch (S3Exception $exc){
                $code = $exc->getAwsErrorCode();
                $msg = $exc->getAwsErrorMessage();
                $return_json = ['error_code' => $code, 'error_msg' => $msg, 'result' => []];
                return $return_json;
            }
            catch (CostandUsageReportServiceException $exc){
                $code = $exc->getAwsErrorCode();
                $msg = $exc->getAwsErrorMessage();
                $return_json = ['error_code' => $code, 'error_msg' => $msg, 'result' => []];
                return $return_json;
            }
            catch (CloudFormationException $exc){
                $code = $exc->getAwsErrorCode();
                $msg = $exc->getAwsErrorMessage();
                $return_json = ['error_code' => $code, 'error_msg' => $msg, 'result' => []];
                return $return_json;
            }
            catch (CloudWatchException $exc){
                $code = $exc->getAwsErrorCode();
                $msg = $exc->getAwsErrorMessage();
                $return_json = ['error_code' => $code, 'error_msg' => $msg, 'result' => []];
                return $return_json;
            }

            $return_json = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result[$param['field']]];
        } else {
            $return_json = ['error_code' => '1001', 'error_msg' => "Unknown method {$awsclass}::{$awscommand}", 'result' => []];
        }

        return $return_json;
    }
}