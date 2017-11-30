<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Validator;
use Config;
use App\User;
use App\CloudHelper;
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
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;

class XCloudNodeVisitor extends NodeVisitorAbstract
{
    public $visited_nodes = [];
    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassMethod) {
                $prettyPrinter = new PrettyPrinter\Standard();
                $body = $prettyPrinter->prettyPrintFile($node->stmts);
                $this->visited_nodes[] = ['name' => $node->name, 'body' => $body];
        }
    }   
}
class XCloudNodeReplacer extends NodeVisitorAbstract
{
    public $function_name = '';
    public $function_stmts = [];
    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassMethod && $node->name == $this->function_name) {
            $node->stmts = $this->function_stmts;
        }
    }   
}

class XCloudNodeAdder extends NodeVisitorAbstract
{
    public $feature_name = '';
    public $function = [];
    public function enterNode(Node $node) {
        if (isset($node->name) && $node->name == $this->feature_name) {
            $node->stmts[] = $this->function;
            //$node->stmts = (object) $node->stmts;
        }
    }   
}


class XCloudNodeStmtExtractor extends NodeVisitorAbstract
{
    public $function_name = '';
    public $function_stmts = [];
    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassMethod && $node->name == $this->function_name) {
            $this->function_stmts = $node->stmts;
        }
    }   
}

class XCloudNodeFunctionExtractor extends NodeVisitorAbstract
{
    public $function_name = '';
    public $function = [];
    public function enterNode(Node $node) {
        if ($node instanceof Node\Stmt\ClassMethod && $node->name == $this->function_name) {
            $this->function = $node;
        }
    }   
}



class AWSController extends Controller
{
    use Helpers;

    public function feature(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $jsonData = $request->all();
        $tempJson = json_encode($jsonData);
        $tempJson = str_replace( "&#39;","'", $tempJson);
        $jsonData = json_decode($tempJson);
        $jsonData = get_object_vars($jsonData);
        //return $jsonData;
        $cloud = new CloudHelper();
        $result = $cloud->call_cloud_api_command($jsonData);
        return $result;         
    }

    public function addFeature($feature_file, $feature_name)
    {
        $result = [ 'error_code' => '0', 'error_msg' => '', 'result' => []];
        $body = $this->customFeatureTemplate();
        $res = file_put_contents($feature_file,$body);
        if(!$res) {
            $result = [ 'error_code' => 'XCFeatureFileWriteError', 'error_msg' => 'ERROR: Could not save feature code.', 'result' => ['body' => $body]];
        }
        return $result;
    }


    public function saveFeature($feature_file, $feature_name, $body)
    {
        $body = str_replace("&#39;", "'", $body);
        $result = [ 'error_code' => '0', 'error_msg' => 'SUCCESS', 'result' => []];
        $res = file_put_contents($feature_file,$body);
        if(!$res) {
            $result = [ 'error_code' => 'XCFeatureFileWriteError', 'error_msg' => 'ERROR: Could not save feature code.', 'result' => ['body' => $body]];
        }
        return $result;
    }

    public function saveSettings($settings_file, $body)
    {
        $body = str_replace("&#39;", "'", $body);
        $result = [ 'error_code' => '0', 'error_msg' => 'SUCCESS', 'result' => []];
        $res = file_put_contents($settings_file,$body);
        if(!$res) {
            $result = [ 'error_code' => 'XCFeatureFileWriteError', 'error_msg' => 'ERROR: Could not save feature code.', 'result' => ['body' => $body]];
        }
        return $result;
    }

    public function getSettings($settings_file)
    {
        $result = [ 'error_code' => '0', 'error_msg' => 'SUCCESS', 'result' => []];
        $res = file_get_contents($settings_file);
        if(!$res) {
            $result = [ 'error_code' => 'XCFeatureFileWriteError', 'error_msg' => 'ERROR: Could not save feature code.', 'result' => ['body' => '']];
        } else {
            $result = [ 'error_code' => '0', 'error_msg' => 'SUCCESS', 'result' => ['body' => $res, 'language' => 'php']];
        }
        
        return $result;
    }


    public function removeFeature($feature_file)
    {
        $result = [ 'error_code' => '0', 'error_msg' => '', 'result' => []];
        $res = unlink($feature_file);
        if(!$res)
        {
            $result = [ 'error_code' => 'XCFeatureFileDeleteError', 'error_msg' => 'ERROR: Could not delete feature.', 'result' => []];
        }
        return $result;
    }

    public function getFeature($feature_file)
    {
        $code = file_get_contents($feature_file);
        $result = [];
        if(!$code) {
            $result = [ 'error_code' => 'XCFeatureFileReadError', 'error_msg' => 'ERROR: Could not read feature code.', 'result' => ['body' => []]];                
        }

        // $code = $result->result->body;
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            $result = [ 'error_code' => 'XCFeatureFileParseError', 'error_msg' => 'ERROR: Could not read feature code.', 'result' => ['error_body' => $error->getMessage()]]; 
            return $result;
        }
        //return json_encode($ast);
        $traverser = new NodeTraverser();
        $nodes = [];
        $visitor = new XCloudNodeVisitor();
        $traverser->addVisitor($visitor);

        $ast = $traverser->traverse($ast);
        $nodes = $visitor->visited_nodes;

        $lines = explode("\n", $code);
        $uses = '';
        foreach ($lines as $line) {
            $trim_line = trim($line);
            if (strpos($trim_line, 'use ') === 0) {
                 $uses .= "{$line}\n";
            }
        }
    
        $result = [ 'error_code' => '0', 'error_msg' => '', 'result' =>['functions' => $nodes, 'body' => $code, 'uses' => $uses]]; 

        return $result;
    }

    public function addFunction($feature_file, $feature_name, $function_name)
    {
        $code = file_get_contents($feature_file);
        $result = [];
        $function_body = "    
    public function $function_name(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];

        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON

        \$result = [];
        echo \"This is a test\";

        return \$response;
    }
";
        $function_body_temp = "  
<?php
Class test {
    {$function_body}
}
";

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            $result = [ 'error_code' => 'XCFeatureFileParseError', 'error_msg' => 'ERROR: Could not read feature code.', 'result' => ['error_body' => $error->getMessage()]]; 
            return $result;
        }

        try {
            $function_ast = $parser->parse($function_body_temp);
        } catch (Error $error) {
            $result = [ 'error_code' => 'XCFeatureFunctionParseError', 'error_msg' => 'ERROR: Could not read feature code.', 'result' => ['error_body' => $error->getMessage()]]; 
            return $result;
        }

        $extract_traverser = new NodeTraverser();
        $extractor = new XCloudNodeFunctionExtractor();
        $extract_traverser->addVisitor($extractor);
        $extractor->function_name = $function_name;
        $extract_traverser->traverse($function_ast);
        $function = $extractor->function;


        $add_traverser = new NodeTraverser();
        $adder = new XCloudNodeAdder();
        $add_traverser->addVisitor($adder);
        $adder->feature_name = $feature_name;
        $adder->function = $function;

        $ast = $add_traverser->traverse($ast);
        $prettyPrinter = new PrettyPrinter\Standard();
        $body = $prettyPrinter->prettyPrintFile($ast);
        $res = file_put_contents($feature_file, $body);
        if(!$res) {
            $result = [ 'error_code' => 'XCFeatureFileWriteError', 'error_msg' => 'ERROR: Could not save feature code.', 'result' => ['body' => $body]];
        }
        else
        {
            $result = [ 'error_code' => 0, 'error_msg' => 'SUCCESS', 'result' => ['body' => $body]];
        }
        return $result;
    }

    public function removeFunction($feature_file, $feature_name, $function_name)
    {

    }

    public function modifyFunction($feature_file, $feature_name, $function_name, $newFunction)
    {

    }


    public function featureEdit(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $param = $request->all();
        $error_msg = 'Success';
        $error_code = '0';
        $result = [];
        $feature_file = '';
        if(isset($param['feature'])) {
            $feature_file = "/var/www/xcloudclientapi/app/FeatureCommands/{$param['feature']}.php";
        }
        
        $settings_file = "/var/www/xcloudclientapi/.env";
        switch($param['command']) {
            case 'get' : 
                $result = $this->getFeature($feature_file);
                break;
            case 'add_feature' :
                $result = $this->addFeature($feature_file, $param['feature']);
                break;
            case 'save' : 
                $result = $this->saveFeature($feature_file, $param['feature'], $param['body']);
                break;
            case 'remove_feature' : 
                $result = $this->removeFeature($feature_file);
                break;
            case 'add_function' : 
                $result = $this->addFunction($feature_file, $param['feature'], $param['function_name']);
                break;
            case 'remove_function' : 
                $result = $this->removeFunction($feature_file, $param['feature'], $param['function_name']);
                break;
            case 'modify_function' : 
                $result = $this->modifyFunction($feature_file, $param['feature'], $param['function_name'], $param['function']);
                break;
            case 'get_settings' : 
                $result = $this->getSettings($settings_file);
                break;
            case 'save_settings' : 
                $result = $this->saveSettings($settings_file, $param['body']);
                break;
            case 'script_language' : 
                $result = ['error_code' => '0', 'error_msg' => 'SUCCESS', 'result' => ['language' => 'php'] ];
                break;
            default:
                $result = ['error_code' => 'XCCommandNotFoundError', 'error_msg' => 'ERROR: Unknown command.', 'result' => [] ];
        }

        return $result;
    }

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
        if($param['field'] == 'root'){
        #$result = get_object_vars($result);
        $return_json = ['error_code' => '0', 'error_msg' => 'Success', 'result' => json_encode($result,true)];
        } else {
                $return_json = ['error_code' => '0', 'error_msg' => 'Success', 'result' => $result[$param['field']]];
        }
        } else {
            $return_json = ['error_code' => '1001', 'error_msg' => "Unknown method {$awsclass}::{$awscommand}", 'result' => []];
        }

        return $return_json;
    }

    public function customFeatureTemplate($feature)
    {
        $str = "<?php
namespace App\FeatureCommands;
use DB;
class {$feature}
{
    public function create(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];
        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON


        return \$response;
    }
    public function readall(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];
        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON


        return \$response;
    }
    public function readone(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];

        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON

        return \$response;
    }
    public function remove(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];
        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON


        return \$response;
    }
    public function resync(\$param)
    {
        \$result = [];
        \$response = ['error_code' => '0', 'error_msg' => 'Success', 'result' => \$result];
        // TODO: write your code below and setup \$result to whatever you want to send back
        // Note: \$result should be a valid JSON


        return \$response;
    }
}";
        return $str;
    }

    public function scriptLang(Request $request)
    {
        $result = ['error_code' => '0', 'error_msg' => 'SUCCESS:', 'result' => ['language' => 'php'] ];
    }

}
