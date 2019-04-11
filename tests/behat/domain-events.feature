Feature: Simulate producing and consuming domain events

  Scenario: I send and consume domain events from RabbitMQ
    Given I have a rabbit queue ready to handle domain events
    And I am subscribed to "test" events
    When I send a domain event with name "test"
    Then I should consume that event

  Scenario: I do not consume other domain events from RabbitMQ
    Given I have a rabbit queue ready to handle domain events
    And I am subscribed to "another_test" events
    When I send a domain event with name "test"
    Then I should not consume that event

  Scenario: I send and consume domain events from Google Pub/Sub
    Given I have a google queue ready to handle domain events
    And I am subscribed to "test" events
    When I send a domain event with name "test"
    Then I should consume that event

  Scenario: I do not consume other domain events from Google Pub/Sub
    Given I have a google queue ready to handle domain events
    And I am subscribed to "another_test" events
    When I send a domain event with name "test"
    Then I should not consume that event

  Scenario: I do not consume other domain events
    Given I have a rabbit queue ready to handle domain events
    And I have a google queue ready to handle domain events
    And I am subscribed to "another_test" events
    When I send a domain event with name "test"
    Then I should not consume that event

  Scenario: I do store the events if the writer fail
    Given The writers are not working
    When I send a domain event with name "test"
    Then I have this event stored

  Scenario: I consume domain events from a Symfony consumer
    Given I have a rabbit queue ready to handle domain events
    And I am subscribed to "test" events
    When I send a domain event with name "test" through the Symfony sender
    Then I should consume that event from the Symfony receiver
