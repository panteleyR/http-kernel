<?php

declare(strict_types=1);

namespace Lilith\HttpKernel\EventListeners;

use Lilith\HttpKernel\Events\ExceptionEvent;

class ExceptionEventListener
{
    public function __invoke(ExceptionEvent $exceptionEvent): void
    {
        if ($exceptionEvent->getResponse() === null) {
//            $exceptionEvent->setResponse();
        }
    }
}
