<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $status = 500;
        $message = 'An error occurred while processing your request';
        $response = [
            'status' => false,
            'message' => $message
        ];

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            $status = 422;
            $response['message'] = $exception->errors();
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            $status = 405;
            $response['message'] = 'Method not allowed';
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $status = 404;
            $response['message'] = 'Not found';
        } elseif ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            $status = 401;
            $response['message'] = 'Unauthorized access';
        }

        return new \Illuminate\Http\JsonResponse($response, $status);
    }
} 