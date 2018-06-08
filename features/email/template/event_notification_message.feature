@emailTemplate
Feature:
  In order to
  An email should be sent

  Scenario: A EventNotificationMessage template is rendered
    When the "EventNotificationMessage" email template is rendered
    Then the email template should contain the following variables:
      | recipient_first_name |
      | host_first_name      |
      | event_name           |
      | event_date           |
      | event_hour           |
      | event_address        |
      | event_show_url       |
      | event_attend_url     |
