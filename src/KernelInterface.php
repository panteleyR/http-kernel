<?php

declare(strict_types=1);

namespace Lilith\HttpKernel;

use Lilith\Http\Message\RequestInterface;
use Lilith\Http\Message\ResponseInterface;

interface KernelInterface
{
    public function handle(RequestInterface $request): ResponseInterface;
}
