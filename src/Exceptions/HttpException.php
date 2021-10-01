<?php


namespace Dymantic\InstagramFeed\Exceptions;


use Throwable;

class HttpException extends \Exception
{

    private $response;

    public function __construct($response, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public static function new(string $url, int $status, string $message, $response)
    {
        return new self(
            $response,
            sprintf(
                "Http request to %s failed with a status of %d and error message: %s",
                $url,  $status, $message
            )
        );
    }

    public function getResponse()
    {
        return $this->response;
    }


}