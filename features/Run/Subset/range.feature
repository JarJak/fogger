Feature:
  In order to make the resulting database smaller
  As a user
  I want to be able to subset selected tables

  Background:
    Given there is a source database
    And there is a table scores with following columns:
      | name  | type    | length | index   |
      | id    | integer |        | primary |
      | score | integer |        |         |
      | desc  | string  | 128    |         |
    And the table scores contains following data:
      | id | score | desc   |
      | 1  | 47    | desc 1 |
      | 2  | 5     | desc 2 |
      | 3  | 43    | desc 3 |
      | 4  | 65    | desc 4 |
      | 5  | 31    | desc 5 |
    And there is an empty target database
    And the task queue is empty

  Scenario: We want only the records within configured range
    Given the config test.yaml contains:
    """
    tables:
      scores:
        subsetStrategy: range
        subsetOptions: { column: "score", from: 10, to: 50 }
    """
    When I run "run" command with input:
      | --chunk-size | 1000      |
      | --file       | test.yaml |
      | --dont-wait  | true      |
    Then I should see "1 chunks have been added to queue" in command's output
    And the command should exit with code 0
    And published tasks counter should equal 1
    And processed tasks counter should equal 0
    When worker processes 1 task
    Then processed tasks counter should equal 1
    And the table scores in target database should have 3 row
    And the table scores in target database should contain rows:
      | id | score |
      | 1  | 47    |
      | 3  | 43    |
      | 5  | 31    |

  Scenario: We want only the records within configured range (multiple chunks)
    Given the config test.yaml contains:
    """
    tables:
      scores:
        subsetStrategy: range
        subsetOptions: { column: "score", from: 10, to: 50 }
    """
    When I run "run" command with input:
      | --chunk-size | 2         |
      | --file       | test.yaml |
      | --dont-wait  | true      |
    Then I should see "2 chunks have been added to queue" in command's output
    And the command should exit with code 0
    And published tasks counter should equal 2
    And processed tasks counter should equal 0
    When worker processes 2 tasks
    Then processed tasks counter should equal 2
    And the table scores in target database should have 3 row
    And the table scores in target database should contain rows:
      | id | score |
      | 1  | 47    |
      | 3  | 43    |
      | 5  | 31    |
