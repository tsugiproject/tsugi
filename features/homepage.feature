Feature: General Index testing

   Basic testing on the main index page

   # Given I am on "index.php"

   Scenario: Visit Index Page
      When I am on homepage
      Then I should see "Hello and welcome to TSUGI."
