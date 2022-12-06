@profilefield @profilefield_conditional
Feature: Create profilefield
  In order to make some fields conditionally required or hidden based on a value of a certain field
  As an admin
  I should be able to create a field based on the Conditional Field type

  @javascript
  Scenario: Successfully create conditional field without conditions
    Given I log in as "admin"
    And I navigate to "Users > Accounts > User profile fields" in site administration
    And I set the field "datatype" to "Conditional field"
    And I set the following fields to these values:
      | Short name | superfield  |
      | Name       | Super field |
    And I set the field "Menu options (one per line)" to multiline:
      """
      The big guy
      Loves cats
      """
    And I click on "Configure conditions" "button"
    And I click on "Apply" "button"

    When I click on "Save changes" "button"
    And I navigate to "Users > Accounts > User profile fields" in site administration

    Then I should see "Super field"
