@local @local_sitenotice
Feature: Users are forcibly logged out after closing a notice which requires acknowledgement
  In order to enforce certain policies
  As a site administrator
  I need to be able to log people out of the site forcibly when they do not acknowledge a notice

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                      |
      | bilbo    | Bilbo     | Baggins  | bilbo@westfarthing.invalid |
    And I change window size to "large"
    And I log in as "admin"
    And I navigate to "Site notice > Settings" in site administration
    And I click on "Enabled" "checkbox"
    And I click on "Save changes" "button"

  @javascript
  Scenario: Users are logged out after closing a notice without acknowledging it
    Given the following site notices exist
      | title         | content                                  | reqack |
      | Logout notice | not acknowledging this will log you out  | 1      |
    When I log in as "bilbo"
    And I am on site homepage
    Then I should see "not acknowledging this will log you out"
    And I click on "sitenotice-closebtn" "button"
    Then I should see "Log in"

  @javascript
  Scenario: Users are not logged out after acknowledging a notice and closing it
    Given the following site notices exist
      | title         | content                                  | reqack |
      | Logout notice | not acknowledging this will log you out  | 1      |
    When I log in as "bilbo"
    And I am on site homepage
    Then I should see "not acknowledging this will log you out"
    And I click on "sitenotice-modal-ackcheckbox" "checkbox"
    And I click on "sitenotice-acceptbtn" "button"
    Then I should see "You are logged in as Bilbo Baggins"
