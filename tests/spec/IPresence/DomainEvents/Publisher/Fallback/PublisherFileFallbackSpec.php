<?php

namespace spec\IPresence\DomainEvents\Publisher\Fallback;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFallback;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFileFallback;
use PhpSpec\ObjectBehavior;
use Webmozart\Assert\Assert;

/**
 * @mixin PublisherFileFallback
 */
class PublisherFileFallbackSpec extends ObjectBehavior
{
    const PATH = __DIR__.'/';

    public function let(DomainEventFactory $factory)
    {
        $this->beConstructedWith($factory, self::PATH);
    }

    public function letGo()
    {
        $files = $this->getStoredFiles();
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PublisherFallback::class);
    }

    public function it_does_not_store_any_file_if_there_are_no_events()
    {
        $this->store([]);
        Assert::isEmpty($this->getStoredFiles());
    }

    public function it_stores_a_file(DomainEvent $event)
    {
        $event->jsonSerialize()->willReturn(['data']);

        $this->store([$event, $event]);
        Assert::count($this->getStoredFiles(), 1);
    }

    public function it_restores_the_events_from_the_file(
        DomainEventFactory $factory,
        DomainEvent $event1,
        DomainEvent $event2
    ) {
        $factory->fromJSON('["data1"]'.PHP_EOL)->willReturn($event1);
        $factory->fromJSON('["data2"]'.PHP_EOL)->willReturn($event2);

        $event1->jsonSerialize()->willReturn(['data1']);
        $event2->jsonSerialize()->willReturn(['data2']);

        $this->store([$event1, $event2]);
        $this->restore()->shouldHaveCount(2);
    }

    private function getStoredFiles()
    {
        return glob(self::PATH.'*'.PublisherFileFallback::EXTENSION);
    }
}
