# features/courseTools.feature
@common @tools
Feature: Course tools basic testing
  In order to use a course
  As a teacher
  I need to be able to enter a course and each of its tools

  Background:
    Given I am a platform administrator

  Scenario: See the courses list
    Given I am on "/main/admin/course_list.php"
    Then I should see "Course list"
    And I should not see "not authorized"

  Scenario: See the course creation link on the admin page
    Given I am on "/main/admin/index.php"
    Then I should see "Add course"

  Scenario: Create a private course before testing
    Given I am on "/main/admin/course_add.php"
    Then I should not see "not authorized"
    When I fill in "title" with "TEMP_PRIVATE"
    Then I check the "Private access (access authorized to group members only)" radio button
    And I press "submit"
    Then wait for the page to be loaded
    Then I should see "added"

  Scenario: Create a course before testing
    Given I am on "/main/admin/course_add.php"
    When I fill in "title" with "TEMP"
    And I press "submit"
    Then wait for the page to be loaded
    Then I should see "Course list"
    And I should see "TEMP"

#  Scenario: Make sure the course exists
#    Given course "TEMP" exists
#    Then I should not see an error

#  Scenario: Make sure the course description tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/course_description/index.php"
#    Then I should not see an error

#  Scenario: Make sure the documents tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/document/document.php"
#    Then I should not see an error

  Scenario: Make sure the learning path tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/lp/lp_controller.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the links tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/link/link.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the tests tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/exercise/exercise.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the announcements tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/announcements/announcements.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the assessments tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/gradebook/index.php?cid=1"
    Then I should not see an error

#  Scenario: Make sure the glossary tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/glossary/index.php?cid=1"
#    Then I should not see an error

  Scenario: Make sure the attendances tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/attendance/index.php?cid=1"
    Then I should not see an error

#  Scenario: Make sure the course progress tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/course_progress/index.php"
#    Then I should not see an error

  Scenario: Make sure the agenda tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/calendar/agenda_js.php?cid=1"
    Then I should not see an error

#  Scenario: Make sure the forums tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/forum/index.php?cid=1"
#    Then I should not see an error

  Scenario: Make sure the dropbox tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/dropbox/index.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the users tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/user/user.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the groups tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/group/group.php?cid=1"
    Then I should not see an error

#  Scenario: Make sure the chat tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/chat/chat.php?cid=1"
#    Then I should not see an error

#  Scenario: Make sure the assignments tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/work/work.php?cid=1"
#    Then I should not see an error

  Scenario: Make sure the surveys tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/survey/index.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the wiki tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/wiki/index.php?cid=1"
    Then I should not see an error

#  Scenario: Make sure the notebook tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/notebook/index.php?cid=1"
#    Then I should not see an error
#
#  Scenario: Make sure the projects tool is available
#    Given I am on course "TEMP" homepage
#    And I am on "/main/blog/blog_admin.php?cid=1"
#    Then I should not see an error

  Scenario: Make sure the reporting tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/tracking/courseLog.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the settings tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/course_info/infocours.php?cid=1"
    Then I should not see an error

  Scenario: Make sure the backup tool is available
    Given I am on course "TEMP" homepage
    And I am on "/main/course_info/maintenance.php?cid=1"
    Then I should not see an error

#  Scenario: Enter to public password-protected course
#    Given I have a public password-protected course named "PASSWORDPROTECTED" with password "123456"
#    And I am not logged
#    And I am on "/courses/PASSWORDPROTECTED/index.php"
#    When I fill in "course_password" with "123456"
#    And I press "submit"
#    Then I should not see "The course password is incorrect"



