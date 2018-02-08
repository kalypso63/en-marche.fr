Feature:

  Scenario: The search city is base on user's city
    Given the following fixtures are loaded:
      | LoadAdherentData |

    When I am on "/evenements"
    Then the "search-city" field should contain "Paris"

    When I am logged as "benjyd@aol.com"
    And I am on "/evenements"
    Then the "search-city" field should contain "Marseille 3e, France"