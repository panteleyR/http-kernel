<?php

declare(strict_types=1);

namespace Lilith\HttpKernel;

use Lilith\DependencyInjection\ContainerInterface;
use Lilith\Http\Message\RequestInterface;
use Lilith\Http\Message\ResponseInterface;
use Lilith\HttpKernel\Events\ExceptionEvent;
use Lilith\HttpKernel\Events\RequestEvent;
use Lilith\HttpKernel\Events\ResponseEvent;

class Kernel
{
    public function __construct(protected ContainerInterface $container) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->handleRaw($request);
        } catch (\Throwable $e) {
            return $this->handleThrowable($e, $request);
        }
    }

    protected function handleRaw(RequestInterface $request): ResponseInterface
    {
        $this->container->get('eventDispatcher')->dispatch(new RequestEvent($request));
        $response = $this->container->get('router')->execute($request);
        $this->container->get('eventDispatcher')->dispatch(new ResponseEvent($request, $response));

        return $response;
    }

    protected function handleThrowable(\Throwable $e, RequestInterface $request): ResponseInterface
    {
        $event = new ExceptionEvent($request, $e);
        $event = $this->container->get('eventDispatcher')->dispatch($event);

        return $event->getResponse();
    }
}
