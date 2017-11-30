<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        

        $error_str = $e->getMessage();
        $stack = $e->getTrace();
        $error_str = str_replace("\n", "<br>", $error_str);
        $stack_str = json_encode($stack,JSON_PRETTY_PRINT);
        $body = ['error_str' => $error_str, 'stack_str' => $stack_str ];
        $res = ['error_code' => 'XCInternalError', 'error_msg' => '500 Internal server error', 'result' => $body ];

        $myfile = fopen("/var/lib/test.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $error_str);
        fclose($myfile);
        return response(($res));
        //parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $myfile = fopen("/var/lib/test.txt", "w") or die("Unable to open file!");
        $txt = "writing from render function\n";
        fwrite($myfile, $txt);
        fclose($myfile);
       
        $error_str = $e->getMessage();
        $stack = $e->getTrace();
        $error_str = str_replace("\n", "<br>", $error_str);
        $stack_str = json_encode($stack,JSON_PRETTY_PRINT);
        $body = ['error_str' => $error_str, 'stack_str' => $stack_str ];
        $res = ['error_code' => 'XCInternalError', 'error_msg' => '500 Internal server error', 'result' => $body ];

        return (($res));
       
        //return parent::render($request, $e);
    }
}
