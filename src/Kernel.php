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
use Lilith\Http\Message\RequestInterface;
use Lilith\Http\Message\ResponseInterface;
use Lilith\HttpKernel\Events\ExceptionEvent;
use Lilith\Router\RouterInterface;

class Kernel
{
    protected ContainerInterface $container;
    protected string $environment;

    public function __construct(protected bool $debug) {}

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

    protected function handleThroughEventBus(RequestInterface $request): ResponseInterface
    {
        $router = $this->container->get('router');
        $event = $router->findRoute($request);
        $event = $this->container->get('eventDispatcher')->dispatch($event);

        return $event->getResponse();
    }

    protected function handleThrowable(\Throwable $e, RequestInterface $request): ResponseInterface
    {
        $event = new ExceptionEvent($request, $e);
        $event = $this->container->get('eventDispatcher')->dispatch($event);

        return $event->getResponse();
    }

    protected function getKernelParameters(): array
    {
        return [
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
//            'kernel.environment' => $this->environment,
//            'kernel.runtime_environment' => '%env(default:kernel.environment:APP_RUNTIME_ENV)%',
//            'kernel.debug' => $this->debug,
//            'kernel.build_dir' => realpath($buildDir = $this->warmupDir ?: $this->getBuildDir()) ?: $buildDir,
//            'kernel.cache_dir' => realpath($cacheDir = ($this->getCacheDir() === $this->getBuildDir() ? ($this->warmupDir ?: $this->getCacheDir()) : $this->getCacheDir())) ?: $cacheDir,
//            'kernel.logs_dir' => realpath($this->getLogDir()) ?: $this->getLogDir(),
//            'kernel.charset' => $this->getCharset(),
//            'kernel.container_class' => $this->getContainerClass(),
        ];
    }

    protected function boot(): void
    {
        chdir(__DIR__ . '/..');
        $this->initilizeContainer();
        $this->container->get('env')->load($this->getProjectDir() . '/.env');
        $this->container->get('router')->setRoutes($this->container->getParameter('package.routes'));
        $eventDispatcherConfig = $this->container->getParameter('package.eventListeners') ?? [];
        foreach ($eventDispatcherConfig as $event => $listenerList) {
            foreach ($listenerList as $listenerClass => $listenerConfig) {
                $object = $this->container->get($listenerClass);
                $this->container->get('eventDispatcher')->addListener(
                    $object,
                    $event,
                    $listenerConfig['method'] ?? null,
                    $listenerConfig['priority'] ?? 1,
                );
            }

        }
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
        $this->container->set('container', $this->container);
    }

    protected function getKernelServices(): array
    {
        return [
            [['eventDispatcher', EventDispatcherInterface::class], EventDispatcher::class],
            [['router', RouterInterface::class], RouterInterface::class],
            [['env', EnvInterface::class], Env::class],
//            [['cache', CacheInterface::class], Cache::class],
        ];
    }

    protected function setConfiguresContainer(ContainerConfiguratorInterface $containerConfigurator): void
    {
        $configDir = $this->getConfigDir();

        $containerConfigurator->importPackage($configDir . '/{packages}/*.yaml');
        $containerConfigurator->importPackage($configDir . '/{packages}/' . $this->environment.'/*.yaml');

        $containerConfigurator->import($configDir . '/services.yaml');
        $containerConfigurator->import($configDir . '/services_' . $this->environment.'.yaml');

        $containerConfigurator->importPackage($configDir . '/routes/' . $this->environment.'/*.yaml');
        $containerConfigurator->importPackage($configDir . '/routes/*.yaml');
        $containerConfigurator->importPackage($configDir . '/routes.yaml');
    }
}
