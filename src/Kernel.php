<?php

declare(strict_types=1);

namespace Lilith\HttpKernel;

use Lilith\DependencyInjection\ContainerBuilder;
use Lilith\DependencyInjection\ContainerConfigurator;
use Lilith\DependencyInjection\ContainerConfiguratorInterface;
use Lilith\DependencyInjection\ContainerInterface;
use Lilith\Env\Env;
use Lilith\Env\EnvInterface;
use Lilith\EventDispatcher\EventDispatcher;
use Lilith\EventDispatcher\EventDispatcherInterface;
use Lilith\EventDispatcher\EventDispatcherProvider;
use Lilith\Http\Message\RequestInterface;
use Lilith\Http\Message\ResponseInterface;
use Lilith\HttpKernel\Events\ExceptionEvent;
use Lilith\Router\Router;
use Lilith\Router\RouterInterface;

class Kernel
{
    protected ContainerInterface $container;
    protected Env $env;
//
//    public function __construct(protected bool $debug) {
//
//    }

    public function handle(RequestInterface $request): ResponseInterface
    {
            $this->boot();

//            if ($this->container->has('http_cache')) {
//                return $container->get('http_cache')->handle($request, $type, $catch);
//            }

        try {
            return $this->handleRaw($request);
        } catch (\Throwable $e) {
            return $this->handleThrowable($e, $request);
        }
    }

    protected function handleRaw(RequestInterface $request): ResponseInterface
    {
        $router = $this->container->get('router');
        [$class, $method] = $router->findRoute($request);
        $controller = $this->container->get($class);

        return $controller->{$method}($request);
    }

//    protected function handleThroughEventBus(RequestInterface $request): ResponseInterface
//    {
//        $router = $this->container->get('router');
//        $event = $router->findRoute($request);
//        $event = $this->container->get('eventDispatcher')->dispatch($event);
//
//        return $event->getResponse();
//    }

    protected function handleThrowable(\Throwable $e, RequestInterface $request): ResponseInterface
    {
        $event = new ExceptionEvent($request, $e);
        $event = $this->container->get('eventDispatcher')->dispatch($event);

        return $event->getResponse();
    }

    protected function boot(): void
    {
        chdir(dirname($_SERVER["SCRIPT_FILENAME"]) . '/..');
        $this->initilizeEnv();
        $this->initilizeContainer();
//        $this->container->get('router')->setRoutes($this->container->getParameter('package.routes'));
//        $eventDispatcherConfig = $this->container->getParameter('package.event_listeners') ?? [];
//        foreach ($eventDispatcherConfig as $event => $listenerList) {
//            foreach ($listenerList as $listenerClass => $listenerConfig) {
//                $object = $this->container->get($listenerClass);
//                $this->container->get('eventDispatcher')->addListener(
//                    $object,
//                    $event,
//                    $listenerConfig['method'] ?? null,
//                    $listenerConfig['priority'] ?? 1,
//                );
//            }
//        }
    }

    protected function initilizeEnv(): void
    {
        $this->env = new Env();
        $this->env->load($this->getProjectDir() . '/.env');
    }

    protected function getProjectDir(): string
    {
        return getcwd();
    }

    protected function getConfigDir(): string
    {
        return $this->getProjectDir() . '/config';
    }

    protected function initilizeContainer(): void
    {
        $containerConfigurator = new ContainerConfigurator();
        $this->setConfiguresContainer($containerConfigurator);
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addParameters($this->getKernelParameters());
        $containerBuilder->addServices($this->getKernelServices());
        $this->container = $containerBuilder->build($containerConfigurator);
    }

    protected function getKernelServices(): array
    {
        return [
            [['eventDispatcher', EventDispatcherInterface::class], EventDispatcher::class],
            [['router', RouterInterface::class], Router::class],
//            [['cache', CacheInterface::class], Cache::class],
        ];
    }

    protected function getKernelParameters(): array
    {
        return [
            'env' => $this->env->getAll(),
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            'kernel.environment' => $this->env->get('APP_DEV'),
//            'kernel.debug' => $this->debug,
//            'kernel.build_dir' => realpath($buildDir = $this->warmupDir ?: $this->getBuildDir()) ?: $buildDir,
//            'kernel.cache_dir' => realpath($cacheDir = ($this->getCacheDir() === $this->getBuildDir() ? ($this->warmupDir ?: $this->getCacheDir()) : $this->getCacheDir())) ?: $cacheDir,
//            'kernel.logs_dir' => realpath($this->getLogDir()) ?: $this->getLogDir(),
//            'kernel.charset' => $this->getCharset(),
        ];
    }

    protected function setConfiguresContainer(ContainerConfiguratorInterface $containerConfigurator): void
    {
        $configDir = $this->getConfigDir();

        $containerConfigurator->importPackage($configDir . '/packages/*.yaml');
//        $containerConfigurator->importPackage($configDir . '/packages/' . $this->env->get('APP_DEV') . '/*.yaml');

        $containerConfigurator->import($configDir . '/services.yaml');
        $containerConfigurator->import($configDir . '/services_' . $this->env->get('APP_DEV') .'.yaml');

        $containerConfigurator->importPackage($configDir . '/routes.yaml');
//        $containerConfigurator->importPackage($configDir . '/routes/' . $this->env->get('APP_DEV') . '/*.yaml');
//        $containerConfigurator->importPackage($configDir . '/routes/*.yaml');
        $containerConfigurator->importPackage($configDir . '/event_listeners.yaml');
    }
}
