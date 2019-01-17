<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DomainEventsConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('domain_events');

        $rootNode
            ->children()
                ->variableNode('mapping')
                    ->info('The mapping of domain event names to classes. This is used deserializing events fro the queue')
                    ->defaultValue([])
                ->end()
                ->arrayNode('rabbit')
                    ->info('Rabbit queue configuration')
                    ->children()
                        ->scalarNode('host')
                            ->info('Rabbit host')
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('port')
                            ->info('Rabbit port')
                            ->defaultValue('5672')
                        ->end()
                        ->scalarNode('vhost')
                            ->info('Rabbit vhost')
                            ->defaultValue('/')
                        ->end()
                        ->scalarNode('user')
                            ->info('Rabbit user')
                        ->end()
                        ->scalarNode('pass')
                            ->info('Rabbit password')
                        ->end()
                        ->arrayNode('exchange')
                            ->children()
                                ->scalarNode('name')
                                    ->info('Exchange to send the domain events to')
                                    ->defaultValue('domain-events')
                                ->end()
                                ->scalarNode('type')
                                    ->defaultValue('direct')
                                ->end()
                                ->scalarNode('passive')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('durable')
                                    ->defaultTrue()
                                ->end()
                                ->scalarNode('autoDelete')
                                    ->defaultFalse()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('queue')
                            ->children()
                                ->scalarNode('name')
                                    ->info('Queue to consume domain events from')
                                ->end()
                                ->variableNode('bindings')
                                    ->info('Domain event names binding')
                                    ->defaultValue([])
                                ->end()
                                ->scalarNode('passive')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('durable')
                                    ->defaultTrue()
                                ->end()
                                ->scalarNode('exclusive')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('autoDelete')
                                    ->defaultFalse()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('consumer')
                            ->children()
                                ->scalarNode('noLocal')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('noAck')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('exclusive')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('noWait')
                                    ->defaultFalse()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

}
