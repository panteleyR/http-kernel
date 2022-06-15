<?php

declare(strict_types=1);

namespace Lilith\HttpKernel\EventListeners;

use Lilith\HttpKernel\Events\ResponseEvent;

class ResponseEventListener
{
    public function __invoke(ResponseEvent $exceptionEvent): void
    {
//        if ($exceptionEvent->getResponse() === null) {
//            $exceptionEvent->setResponse();
//        }
    }
}
