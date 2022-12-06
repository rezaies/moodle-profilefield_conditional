@profilefield @profilefield_conditional @_file_upload @javascript
Feature: Upload users
  In order to add users with conditional custom profile fields to the system
  As an admin
  I need to upload files containing the users data

  Background:
    # Create conditional profile field
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
    And I click on "Save changes" "button"

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

  @javascript
  Scenario: Upload users with one conditional field without conditions
    # Upload users via CSV file
    Given I navigate to "Users > Accounts > Upload users" in site administration
    And I upload "user/profile/field/conditional/tests/fixtures/upload_users_profile.csv" file to "File" filemanager
    And I press "Upload users"
    Then I should see "Upload users preview"
    When I press "Upload users"

    # Verify that conditional field has been set
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Tom Jones"
    Then I should see "Super field"
    And I should see "The big guy"
    When I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Trent Reznor"
    Then I should see "Super field"
    And I should see "Loves cats"

  @javascript @only
  Scenario: Upload users with one conditional field with conditions
    # Configure conditions on conditional field
    Given I navigate to "Users > Accounts > User profile fields" in site administration
    And I click on ".icon[title=Edit]" "css_element" in the "Super field" "table_row"
    And I click on "Configure conditions" "button"
    And I click on "[data-field='profilefield_conditional_field_required_The big guy_dependenttextinput']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_hidden_The big guy_dependenttextarea']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_hidden_Loves cats_dependenttextinput']" "css_element"
    And I click on "[data-field='profilefield_conditional_field_required_Loves cats_dependenttextarea']" "css_element"
    And I click on "Apply" "button"
    And I click on "Save changes" "button"

    # Upload users via CSV file
    When I navigate to "Users > Accounts > Upload users" in site administration
    And I upload "user/profile/field/conditional/tests/fixtures/upload_users_with_conditions.csv" file to "File" filemanager
    And I press "Upload users"
    Then I should see "Upload users preview"
    When I press "Upload users"

    # Verify that conditional field has been set
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Tom Jones"
    Then I should see "Super field"
    And I should see "The big guy"
    And I should see "Dependent text input"
    And I should see "Apples"
    And I should not see "Dependent text area"
    When I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Trent Reznor"
    Then I should see "Super field"
    And I should see "Loves cats"
    And I should see "Dependent text area"
    And I should see "History of dogs"
    And I should not see "Dependent text input"
