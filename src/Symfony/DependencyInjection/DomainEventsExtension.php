<?php

namespace IPresence\DomainEvents\Symfony\DependencyInjection;

use IPresence\DomainEvents\Listener\Listener;
use IPresence\DomainEvents\Listener\ListenerBuilder;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Publisher\PublisherBuilder;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class DomainEventsExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new DomainEventsConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadPublisher($container, $config);
        $this->loadListener($container, $config);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function loadPublisher(ContainerBuilder $container, array $config)
    {
        $definition = (new Definition())
            ->setFactory([PublisherBuilder::class, 'buildFromConfig'])
            ->addArgument($config)
            ->addArgument(new Reference(Monitor::class))
            ->addArgument(new Reference(LoggerInterface::class));

        $container->setDefinition(Publisher::class, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function loadListener(ContainerBuilder $container, array $config)
    {
        $definition = (new Definition(ListenerBuilder::class))
            ->setFactory([ListenerBuilder::class, 'buildFromConfig'])
            ->addArgument($config)
            ->addArgument(new Reference(Monitor::class))
            ->addArgument(new Reference(LoggerInterface::class));

        $container->setDefinition(Listener::class, $definition);
    }
}
