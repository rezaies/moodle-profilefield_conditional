@profilefield @profilefield_conditional
Feature: Create conditional profile field
  In order to make some fields conditionally required or hidden based on a value of a certain field
  As an admin
  I should be able to create a field based on the Conditional Field type

  Background:
    Given I log in as "admin"

    # Create a dependent text input field
    Given I navigate to "Users > Accounts > User profile fields" in site administration
    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | Short name | dependenttextinput   |
      | Name       | Dependent text input |
    And I click on "Save changes" "button"

    # Create a dependent text area field
    Given I navigate to "Users > Accounts > User profile fields" in site administration
    And I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | Short name | dependenttextarea   |
      | Name       | Dependent text area |
    And I click on "Save changes" "button"

    # Create an independent checkbox field
    Given I navigate to "Users > Accounts > User profile fields" in site administration
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | Short name | independentcheckbox  |
      | Name       | Independent checkbox |
    And I click on "Save changes" "button"

  @javascript
  Scenario: Successfully toggle visibility of dependent fields
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
    And I click on "[data-field='profilefield_conditional_field_required_The big guy_dependenttextinput']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_hidden_The big guy_dependenttextarea']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_hidden_Loves cats_dependenttextinput']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_required_Loves cats_dependenttextarea']" "css_element"
    And I click on "Apply" "button"
    And I click on "Save changes" "button"

    When I follow "Profile" in the user menu
    And I click on "Edit profile" "link" in the "region-main" "region"
    And I expand all fieldsets
    And I set the field "profile_field_superfield" to "The big guy"

    Then "Dependent text input" "field" should be visible
    And "Dependent text area" "field" should not be visible
    And "Independent checkbox" "field" should be visible

    When I select "Loves cats" from the "profile_field_superfield" singleselect

    Then "Dependent text input" "field" should not be visible
    And "Dependent text area" "field" should not be visible
    And "Independent checkbox" "field" should be visible

    When I set the field "Dependent text area" to "Apples and Oranges"
    And I start watching to see if a new page loads
    And I click on "Update profile" "button"

    Then a new page should have loaded since I started watching
    And I should see "Dependent text area"
    And I should see "Apples and Oranges"
    And I should not see "Dependent text input"
    And I should not see "Update profile"
