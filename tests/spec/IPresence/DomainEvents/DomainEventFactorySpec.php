<?php

namespace spec\IPresence\DomainEvents;

use InvalidArgumentException;
use IPresence\DomainEvents\DomainEventFactory;
use PhpSpec\ObjectBehavior;
use Webmozart\Assert\Assert;

/**
 * @mixin DomainEventFactory
 */
class DomainEventFactorySpec extends ObjectBehavior
{
    const MAPPING = [
        'test.serializable_name' => 'spec\IPresence\DomainEvents\DeserializableDomainEventMock',
        'test.not_serializable_name' => 'spec\IPresence\DomainEvents\NotDeserializableDomainEventMock',
    ];

    public function let()
    {
        $this->beConstructedWith(self::MAPPING);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\DomainEventFactory');
    }

    public function it_throws_an_exception_if_can_not_decode_the_json()
    {
        $this->shouldThrow(new InvalidArgumentException('Invalid JSON: Syntax error'))->duringFromJSON('{invalid json');
    }

    public function it_throws_an_exception_if_id_is_missing()
    {
        $json = <<<JSON
{
    "origin": "test",
    "name": "name",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }

    public function it_throws_an_exception_if_origin_is_missing()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "name": "name",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }

    public function it_throws_an_exception_if_name_is_missing()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }

    public function it_throws_an_exception_if_version_is_missing()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "name",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }

    public function it_throws_an_exception_if_occurredOn_is_missing()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "name",
    "version": "v1.0.0",
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }

    public function it_throws_an_exception_if_body_is_missing()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "name",
    "version": "v1.0.0",
    "occurredOn": 1547212953
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json"))->duringFromJSON($json);
    }


    public function it_creates_a_generic_domain_event()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "name",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $event = $this->fromJSON($json)->getWrappedObject();

        Assert::eq($event->id(), '48b36651-aef3-4f2c-bed2-ec8c797600bc');
        Assert::eq($event->origin(), 'test');
        Assert::eq($event->name(), 'name');
        Assert::eq($event->version(), 'v1.0.0');
        Assert::eq($event->occurredOn()->getTimestamp(), 1547212953);
        Assert::eq($event->body(), ['key1' => 'value1']);
    }

    public function it_creates_a_custom_domain_event()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "serializable_name",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->fromJSON($json)->shouldHaveType(DeserializableDomainEventMock::class);
    }

    public function it_throws_an_exception_if_custom_domain_event_is_not_deserializable()
    {
        $json = <<<JSON
{
    "id": "48b36651-aef3-4f2c-bed2-ec8c797600bc",
    "origin": "test",
    "name": "not_serializable_name",
    "version": "v1.0.0",
    "occurredOn": 1547212953,
    "body": {
        "key1": "value1"
    }
}
JSON;

        $this->shouldThrow(new InvalidArgumentException("Class spec\IPresence\DomainEvents\NotDeserializableDomainEventMock does not implement JsonDeserialize interface"))->duringFromJSON($json);
    }
}
