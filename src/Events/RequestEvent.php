<?php

declare(strict_types=1);

namespace Lilith\HttpKernel\Events;

use Lilith\Http\Message\RequestInterface;

class RequestEvent
{
    public function __construct(
        protected RequestInterface $request,
    ) {}

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }
}
