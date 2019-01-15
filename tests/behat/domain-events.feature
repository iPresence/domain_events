Feature: Simulate producing and consuming domain events

  Scenario: I send and consume domain events
    Given I have a queue ready to handle domain events
    And I am subscribed to "test" events
    When I send a domain event with name "test"
    Then I should consume that event

  Scenario: I do not consume other domain events
    Given I have a queue ready to handle domain events
    And I am subscribed to "another_test" events
    When I send a domain event with name "test"
    Then I should not consume that event
