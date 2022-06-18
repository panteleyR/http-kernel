<?php

declare(strict_types=1);

namespace Lilith\HttpKernel\EventListeners;

use Lilith\Http\Message\Response;
use Lilith\HttpKernel\Events\ExceptionEvent;

class ExceptionEventListener
{
    public function __invoke(ExceptionEvent $exceptionEvent): ExceptionEvent
    {
        $message =  'error: ' . $exceptionEvent->getThrowable()->getTraceAsString() . $exceptionEvent->getThrowable()->getMessage();
        $response = new Response(500, [], $message);
        $exceptionEvent->setResponse($response);

        return $exceptionEvent;
    }
}
