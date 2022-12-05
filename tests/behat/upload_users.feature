@profilefield @profilefield_conditional @_file_upload
Feature: Upload users
  In order to add users to the system
  As an admin
  I need to upload files containing the users data

  @javascript
  Scenario: Upload users with one conditional field without conditions
        # Create user profile field.
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
        # Upload users.
    When I navigate to "Users > Accounts > Upload users" in site administration
    And I upload "lib/tests/fixtures/upload_users_profile.csv" file to "File" filemanager
    And I press "Upload users"
    And I should see "Upload users preview"
    And I press "Upload users"
        # Check that users were created and the superfield is filled.
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Tom Jones"
    And I should see "Super field"
    And I should see "The big guy"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Trent Reznor"
    And I should see "Super field"
    And I should see "Loves cats"
    And I log out
