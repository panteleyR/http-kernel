<?php

declare(strict_types=1);

namespace Lilith\HttpKernel;

use Lilith\Console\KernelInterface as ConsoleKernelInterface;
use Lilith\Console\Kernel as ConsoleKernel;
use Lilith\DependencyInjection\Container;
use Lilith\DependencyInjection\ContainerBuilder;
use Lilith\DependencyInjection\ContainerConfigurator;
use Lilith\DependencyInjection\ContainerConfiguratorInterface;
use Lilith\DependencyInjection\ContainerInterface;
use Lilith\Env\Env;
use Lilith\EventDispatcher\EventDispatcher;
use Lilith\EventDispatcher\EventDispatcherInterface;
use Lilith\Http\Message\RequestInterface;
use Lilith\Http\Message\ResponseInterface;
use Lilith\Router\Router;
use Lilith\Console\Router as ConsoleRouter;
use Lilith\Router\RouterInterface;

class Application
{
    protected ContainerInterface $container;
    protected Env $env;

    public function __construct()
    {
        $this->boot();
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->container->get('kernel')->handle($request);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function boot(): void
    {
        chdir(dirname($_SERVER["SCRIPT_FILENAME"]) . '/..');
        $this->initilizeEnv();
        $this->initilizeContainer();
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
        $this->container = new Container();
        $containerConfigurator = new ContainerConfigurator();
        $this->setConfiguresContainer($containerConfigurator);

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addParameters($this->getKernelParameters());
        $containerBuilder->addServices($this->getKernelServices());

        $containerBuilder->addPackages($containerConfigurator->getPackages());
        $containerBuilder->addParameters($containerConfigurator->getParameters());
        $containerBuilder->addServices($containerConfigurator->getServices());
        $containerBuilder->addServiceProviders($containerConfigurator->getServices());

        $containerBuilder->build($this->container);
    }

    protected function getKernelServices(): array
    {
        return [
            [['kernel', KernelInterface::class], Kernel::class],
            [['console.kernel', ConsoleKernelInterface::class], ConsoleKernel::class],
            [['eventDispatcher', EventDispatcherInterface::class], EventDispatcher::class],
            [['router', RouterInterface::class], Router::class],
            [['console.router'], ConsoleRouter::class],
        ];
    }

    protected function getKernelParameters(): array
    {
        return [
            'env' => $this->env->getAll(),
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            'kernel.environment' => $this->env->get('APP_DEV'),
        ];
    }

    protected function setConfiguresContainer(ContainerConfiguratorInterface $containerConfigurator): void
    {
        $configDir = $this->getConfigDir();

        $containerConfigurator->importPackage($configDir . '/packages/*.yaml');
        $containerConfigurator->importPackage($configDir . '/packages/' . $this->env->get('APP_DEV') . '/*.yaml');

        $containerConfigurator->import($configDir . '/services.yaml');
        $containerConfigurator->import($configDir . '/services_' . $this->env->get('APP_DEV') .'.yaml');
    }
}
