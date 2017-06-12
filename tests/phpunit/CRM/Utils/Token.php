<?php

/**
 * Class CRM_Utils_TokenTest
 * @group headless
 */
class CRM_Utils_TokenTest extends CiviUnitTestCase {

  /**
   * Basic test on getTokenDetails function.
   */
  public function testGetTokenDetails() {
    $contactID = $this->individualCreate(array('preferred_communication_method' => array('Phone', 'Fax')));
    $resolvedTokens = CRM_Utils_Token::getTokenDetails(array($contactID));
    $this->assertEquals('Phone, Fax', $resolvedTokens[0][$contactID]['preferred_communication_method']);
  }

  /**
   * Test getting multiple contacts.
   *
   * Check for situation described in CRM-19876.
   */
  public function testGetTokenDetailsMultipleEmails() {
    $i = 0;

    $params = array(
      'do_not_phone' => 1,
      'do_not_email' => 0,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 0,
      'email' => 'guardians@galaxy.com',
      'legal_identifier' => 'convict 56',
      'nick_name' => 'bob',
      'contact_source' => 'bargain basement',
      'formal_title' => 'Your silliness',
      'job_title' => 'World Saviour',
      'gender_id' => '1',
      'birth_date' => '2017-01-01',
      // 'city' => 'Metropolis',
    );
    $contactIDs = array();
    while ($i < 27) {
      $contactIDs[] = $contactID = $this->individualCreate($params);
      $this->callAPISuccess('Email', 'create', array(
        'contact_id' => $contactID,
        'email' => 'goodguy@galaxy.com',
        'location_type_id' => 'Other',
        'is_primary' => 0,
      ));
      $this->callAPISuccess('Email', 'create', array(
        'contact_id' => $contactID,
        'email' => 'villain@galaxy.com',
        'location_type_id' => 'Work',
        'is_primary' => 1,
      ));
      $i++;
    }
    unset($params['email']);

    $resolvedTokens = CRM_Utils_Token::getTokenDetails($contactIDs);
    foreach ($contactIDs as $contactID) {
      $resolvedContactTokens = $resolvedTokens[0][$contactID];
      $this->assertEquals('Individual', $resolvedContactTokens['contact_type']);
      $this->assertEquals('Anderson, Anthony', $resolvedContactTokens['sort_name']);
      $this->assertEquals('en_US', $resolvedContactTokens['preferred_language']);
      $this->assertEquals('Both', $resolvedContactTokens['preferred_mail_format']);
      $this->assertEquals(3, $resolvedContactTokens['prefix_id']);
      $this->assertEquals(3, $resolvedContactTokens['suffix_id']);
      $this->assertEquals('Mr. Anthony J. Anderson II', $resolvedContactTokens['addressee_display']);
      $this->assertEquals('villain@galaxy.com', $resolvedContactTokens['email']);

      foreach ($params as $key => $value) {
        $this->assertEquals($value, $resolvedContactTokens[$key]);
      }
    }
  }

}
