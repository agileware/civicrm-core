<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 * @group headless
 */
class CRM_Case_XMLProcessor_ProcessTest extends CiviCaseTestCase {

  /**
   * @var array
   */
  private array $defaultAssigneeOptionsValues = [];

  /**
   * @var array
   */
  private array $moreRelationshipTypes = [];

  /**
   * @var SimpleXMLElement
   */
  private SimpleXMLElement $activityTypeXml;

  /**
   * @var array
   */
  private array $activityParams = [];

  /**
   * @var CRM_Case_XMLProcessor_Process
   */
  private CRM_Case_XMLProcessor_Process $process;

  public function setUp(): void {
    parent::setUp();

    $this->setupContacts();
    $this->setupDefaultAssigneeOptions();
    $this->setupRelationships();
    $this->setupMoreRelationshipTypes();
    $this->setupActivityDefinitions();

    $this->process = new CRM_Case_XMLProcessor_Process();
  }

  /**
   * Creates sample contacts.
   */
  protected function setUpContacts() {
    $this->individualCreate(['first_name' => 'ana'], 'ana');
    $this->individualCreate(['first_name' => 'beto'], 'beto');
    $this->individualCreate(['first_name' => 'carlos'], 'carlos');
  }

  /**
   * Adds the default assignee group and options to the test database.
   * It also stores the IDs of the options in an index.
   */
  protected function setupDefaultAssigneeOptions() {
    $options = [
      'NONE', 'BY_RELATIONSHIP', 'SPECIFIC_CONTACT', 'USER_CREATING_THE_CASE',
    ];

    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'activity_default_assignee',
    ]);

    foreach ($options as $option) {
      $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_default_assignee',
        'name' => $option,
        'label' => $option,
      ]);

      $this->defaultAssigneeOptionsValues[$option] = $optionValue['value'];
    }
  }

  /**
   * Adds a relationship between the activity's target contact and default assignee.
   */
  protected function setupRelationships() {
    $relationships = [
      'ana_is_pupil_of_beto' => [
        'type_id' => NULL,
        'name_a_b' => 'Pupil of',
        'name_b_a' => 'Instructor',
        'contact_id_a' => $this->ids['Contact']['ana'],
        'contact_id_b' => $this->ids['Contact']['beto'],
      ],
      'ana_is_spouse_of_carlos' => [
        'type_id' => NULL,
        'name_a_b' => 'Spouse of',
        'name_b_a' => 'Spouse of',
        'contact_id_a' => $this->ids['Contact']['ana'],
        'contact_id_b' => $this->ids['Contact']['carlos'],
      ],
    ];
    $this->relationshipTypeCreate([
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'name_a_b' => 'Pupil of',
      'label_a_b' => 'Pupil of',
      'name_b_a' => 'Instructor',
      'label_b_a' => 'Instructor',
    ]);
    foreach ($relationships as $relationship) {
      $this->createTestEntity('Relationship', [
        'contact_id_a' => $relationship['contact_id_a'],
        'contact_id_b' => $relationship['contact_id_b'],
        'relationship_type_id:name' => $relationship['name_a_b'],
      ]);
    }
  }

  /**
   * Set up some additional relationship types for some specific tests.
   */
  protected function setupMoreRelationshipTypes() {
    $this->moreRelationshipTypes = [
      'unidirectional_name_label_different' => [
        'type_id' => NULL,
        'name_a_b' => 'jm7ab',
        'label_a_b' => 'Jedi Master is',
        'name_b_a' => 'jm7ba',
        'label_b_a' => 'Jedi Master for',
        'description' => 'Jedi Master',
      ],
      'unidirectional_name_label_same' => [
        'type_id' => NULL,
        'name_a_b' => 'Quilt Maker is',
        'label_a_b' => 'Quilt Maker is',
        'name_b_a' => 'Quilt Maker for',
        'label_b_a' => 'Quilt Maker for',
        'description' => 'Quilt Maker',
      ],
      'bidirectional_name_label_different' => [
        'type_id' => NULL,
        'name_a_b' => 'f12',
        'label_a_b' => 'Friend of',
        'name_b_a' => 'f12',
        'label_b_a' => 'Friend of',
        'description' => 'Friend',
      ],
      'bidirectional_name_label_same' => [
        'type_id' => NULL,
        'name_a_b' => 'Enemy of',
        'label_a_b' => 'Enemy of',
        'name_b_a' => 'Enemy of',
        'label_b_a' => 'Enemy of',
        'description' => 'Enemy',
      ],
    ];

    foreach ($this->moreRelationshipTypes as &$relationship) {
      $relationship['type_id'] = $this->relationshipTypeCreate([
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Individual',
        'name_a_b' => $relationship['name_a_b'],
        'label_a_b' => $relationship['label_a_b'],
        'name_b_a' => $relationship['name_b_a'],
        'label_b_a' => $relationship['label_b_a'],
        'description' => $relationship['description'],
      ]);
    }
  }

  /**
   * Defines the the activity parameters and XML definitions. These can be used
   * to create the activity.
   */
  protected function setupActivityDefinitions() {
    $this->activityTypeXml = $this->getActivityTypeXMl();
    $this->activityParams = [
      'activity_date_time' => date('Ymd'),
      // @todo This seems wrong, it just happens to work out because both caseId and caseTypeId equal 1 in the stock setup here.
      'caseID' => $this->caseTypeId,
      'clientID' => $this->ids['Contact']['ana'],
      'creatorID' => $this->getLoggedInUser(),
    ];
  }

  /**
   * Tests the creation of activities where the default assignee should be the
   * target contact's instructor. Beto is the instructor for Ana.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityWithDefaultContactByRelationship(): void {
    $activityTypeXml = $this->getActivityTypeXMl();
    $activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Pupil of') . '_b_a';

    $this->process->createActivity($activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->ids['Contact']['beto']);
  }

  /**
   * Test the creation of activities where the default assignee should not
   * end up being a contact from another case where it has the same client
   * and relationship.
   *
   * @throws \Exception
   */
  public function testCreateActivityWithDefaultContactByRelationshipTwoCases(): void {
    /*
    At this point the stock setup looks like this:
    Case 1: no roles assigned
    Non-case relationship with ana as pupil of beto
    Non-case relationship with ana as spouse of carlos

    So we want to:
    Make another case for the same client ana.
    Add a pupil role on that new case with some other person.
    Make an activity on the first case.

    Since there is a non-case relationship of that type for the
    right person we do want it to take that one even though there is no role
    on the first case, i.e. it SHOULD fall back to non-case relationships.
    So this is test 1.

    Then we want to get rid of the non-case relationship and try again. In
    this situation it should not make any assignment, i.e. it should not
    take the other person from the other case. The original bug was that it
    would assign the activity to that other person from the other case. This
    is test 2.
     */

    // Make another case and add a case role with the same relationship we
    // want, but a different person.
    $caseObj = $this->createCase($this->ids['Contact']['ana'], $this->getLoggedInUser());
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->ids['Contact']['ana'],
      'contact_id_b' => $this->ids['Contact']['carlos'],
      'relationship_type_id' => $this->ids['RelationshipType']['Pupil of'],
      'case_id' => $caseObj->id,
    ]);

    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Pupil of') . '_b_a';
    $this->process->createActivity($this->activityTypeXml, $this->activityParams);

    // We can't use assertActivityAssignedToContactExists because it assumes
    // there's only one activity in the database, but we have several from the
    // second case. We want the one we just created on the first case.
    $result = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $this->activityParams['caseID'],
      'return' => ['assignee_contact_id'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      // Note the first parameter is turned into an array to match the second.
      $this->assertEquals([$this->ids['Contact']['beto']], $activity['assignee_contact_id']);
    }

    // Now remove the non-case relationship.
    $result = $this->callAPISuccess('Relationship', 'get', [
      'case_id' => ['IS NULL' => 1],
      'relationship_type_id' => $this->ids['RelationshipType']['Pupil of'],
      'contact_id_a' => $this->ids['Contact']['ana'],
      'contact_id_b' => $this->ids['Contact']['beto'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      $this->callAPISuccess('Relationship', 'delete', ['id' => $activity['id']]);
    }

    // Create another activity on the first case. Make it a different activity
    // type so we can find it better.
    $activityXml = '<activity-type><name>Follow up</name></activity-type>';
    $activityXmlElement = new SimpleXMLElement($activityXml);
    $activityXmlElement->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Pupil of') . '_b_a';
    $this->process->createActivity($activityXmlElement, $this->activityParams);

    $result = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $this->activityParams['caseID'],
      'activity_type_id' => 'Follow up',
      'return' => ['assignee_contact_id'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      // It should be empty, not the contact from the second case.
      $this->assertEmpty($activity['assignee_contact_id']);
    }
  }

  /**
   * Tests when the default assignee relationship exists, but in the other direction only.
   * Ana is a pupil, but has no pupils related to her.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityWithDefaultContactByRelationshipMissing(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Pupil of') . '_a_b';

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests when the the default assignee relationship exists and is a bidirectional
   * relationship. Ana and Carlos are spouses.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityWithDefaultContactByRelationshipBidirectional(): void {
    $this->activityParams['clientID'] = $this->ids['Contact']['carlos'];
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Spouse of') . '_b_a';

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->ids['Contact']['ana']);
  }

  /**
   * Tests when the default assignee relationship does not exist. Ana is not an
   * employee for anyone.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityWithDefaultContactByRelationButTheresNoRelationship(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Employee of') . '_b_a';

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityAssignedToSpecificContact(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = $this->ids['Contact']['carlos'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->ids['Contact']['carlos']);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact,
   * but the contact does not exist.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityAssignedToNonExistentSpecificContact(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = 987456321;

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with the default assignee being the one
   * creating the case's activity.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityAssignedToUserCreatingTheCase(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['USER_CREATING_THE_CASE'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->getLoggedInUser());
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityAssignedNoUser(): void {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['NONE'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateActivityWithNoDefaultAssigneeOption(): void {
    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Asserts that an activity was created where the assignee was the one related
   * to the target contact.
   *
   * @param int|null $assigneeContactId the ID of the expected assigned contact or NULL if expected to be empty.
   */
  protected function assertActivityAssignedToContactExists(?int $assigneeContactId) {
    $expectedContact = $assigneeContactId === NULL ? [] : [$assigneeContactId];
    $result = $this->callAPISuccess('Activity', 'get', [
      'target_contact_id' => $this->activityParams['clientID'],
      'return' => ['assignee_contact_id'],
    ]);
    $activity = CRM_Utils_Array::first($result['values']);

    $this->assertNotNull($activity, 'Target contact has no activities assigned to them');
    $this->assertEquals($expectedContact, $activity['assignee_contact_id'], 'Activity is not assigned to expected contact');
  }

  /**
   * Test that caseRoles() doesn't have name and label mixed up.
   *
   * @param $key string The array key in the moreRelationshipTypes array that
   *   is the relationship type we're currently testing. So not necessarily
   *   unique for each entry in the dataProvider since want to test a given
   *   relationship type against multiple xml strings. It's not a test
   *   identifier, it's an array key to use to look up something.
   * @param string $xmlString
   * @param array|null $expected
   *
   * @throws \Exception
   * @dataProvider xmlCaseRoleDataProvider
   */
  public function testCaseRoles(string $key, string $xmlString, ?array $expected) {
    $xmlObj = new SimpleXMLElement($xmlString);

    // element 0 is direction (a_b), 1 is the text we want
    $expectedArray = empty($expected) ? [] : ["{$this->moreRelationshipTypes[$key]['type_id']}_{$expected[0]}" => $expected[1]];

    $this->assertEquals($expectedArray, $this->process->caseRoles($xmlObj->CaseRoles, FALSE));
  }

  /**
   * Test that locateNameOrLabel doesn't have name and label mixed up.
   *
   * @param $key string The array key in the moreRelationshipTypes array that
   *   is the relationship type we're currently testing. So not necessarily
   *   unique for each entry in the dataprovider since want to test a given
   *   relationship type against multiple xml strings. It's not a test
   *   identifier, it's an array key to use to look up something.
   * @param string $xmlString
   * @param null|array $unused We're re-using the data provider for two tests and
   *   we don't care about those expected values.
   * @param array $expected
   *
   * @throws \Exception
   * @dataProvider xmlCaseRoleDataProvider
   */
  public function testLocateNameOrLabel(string $key, string $xmlString, ?array $unused, array $expected) {
    $xmlObj = new SimpleXMLElement($xmlString);

    // element 0 is direction (a_b), 1 is the text we want.
    // In case of failure, the function is expected to return FALSE for the
    // direction and then for the text it just gives us back the string we
    // gave it.
    $expectedArray = empty($expected[0])
        ? [FALSE, $expected[1]]
        : ["{$this->moreRelationshipTypes[$key]['type_id']}_{$expected[0]}", $expected[1]];

    $this->assertEquals($expectedArray, $this->process->locateNameOrLabel($xmlObj->CaseRoles->RelationshipType));
  }

  /**
   * Data provider for testCaseRoles and testLocateNameOrLabel
   * @return array
   */
  public static function xmlCaseRoleDataProvider(): array {
    return [
      // Simulate one that has been converted to the format it should be going
      // forward, where name is the actual name, i.e. same as machineName.
      'unidirectional_name_label_different' => [
        // this is the array key in the $this->moreRelationshipTypes array
        'unidirectional_name_label_different',
        // some xml
        '<CaseType><CaseRoles><RelationshipType><name>jm7ba</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        // this is the expected for testCaseRoles
        ['a_b', 'Jedi Master is'],
        // this is the expected for testLocateNameOrLabel
        ['a_b', 'jm7ba'],
      ],
      // Simulate one that is still in label format, i.e. one that is still in
      // xml files that haven't been updated, or in the db but upgrade script
      // not run yet.
      'unidirectional_name_label_different_label_format' => [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Jedi Master for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Jedi Master is'],
        ['a_b', 'jm7ba'],
      ],
      // Ditto but where we know name and label are the same in the db.
      'unidirectional_name_label_same' => [
        'unidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Quilt Maker for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Quilt Maker is'],
        ['a_b', 'Quilt Maker for'],
      ],
      // Simulate one that is messed up and should fail, e.g. like a typo
      // in an xml file. Here we've made a typo on purpose.
      'unidirectional_name_label_different_wrong' => [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Jedi Masterrrr for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        NULL,
        [FALSE, 'Jedi Masterrrr for'],
      ],
      // Now some similar tests to above but for bidirectional relationships.
      // Bidirectional relationship, name and label different, using machine name.
      'bidirectional_name_label_different' => [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>f12</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Friend of'],
        ['b_a', 'f12'],
      ],
      // Bidirectional relationship, name and label different, using display label.
      'bidirectional_name_label_different_label' => [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Friend of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Friend of'],
        ['b_a', 'f12'],
      ],
      // Bidirectional relationship, name and label same.
      'bidirectional_name_label_same' => [
        'bidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Enemy of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Enemy of'],
        ['b_a', 'Enemy of'],
      ],
    ];
  }

  /**
   * Test XMLProcessor activityTypes()
   */
  public function testXmlProcessorActivityTypes(): void {
    // First change an activity's label since we also test getting the labels.
    // @todo Having a brain freeze or something - can't do this in one step?
    $activity_type_id = $this->callApiSuccess('OptionValue', 'get', [
      'option_group_id' => 'activity_type',
      'name' => 'Medical evaluation',
    ])['id'];
    $this->callApiSuccess('OptionValue', 'create', [
      'id' => $activity_type_id,
      'label' => 'Medical evaluation changed',
    ]);

    $p = new CRM_Case_XMLProcessor_Process();
    $xml = $p->retrieve('housing_support');

    // Test getting the `name`s
    $activityTypes = $p->activityTypes($xml->ActivityTypes, FALSE, FALSE, FALSE);
    $this->assertEquals(array_values(
      [
        13 => 'Open Case',
        56 => 'Medical evaluation',
        57 => 'Mental health evaluation',
        58 => 'Secure temporary housing',
        61 => 'Income and benefits stabilization',
        59 => 'Long-term housing plan',
        14 => 'Follow up',
        15 => 'Change Case Type',
        16 => 'Change Case Status',
        18 => 'Change Case Start Date',
        25 => 'Link Cases',
      ]),
      array_values($activityTypes)
    );

    // While we're here and have the `name`s check the editable types in
    // Settings.xml which is something that gets called reasonably often
    // thru CRM_Case_XMLProcessor_Process::activityTypes().
    $activityTypeValues = array_flip($activityTypes);
    $xml = $p->retrieve('Settings');
    $settings = $p->activityTypes($xml->ActivityTypes, FALSE, FALSE, 'edit');
    $this->assertEquals(
      [
        'edit' => [
          0 => $activityTypeValues['Change Case Status'],
          1 => $activityTypeValues['Change Case Start Date'],
        ],
      ],
      $settings
    );

    // Now get `label`s
    $xml = $p->retrieve('housing_support');
    $activityTypes = $p->activityTypes($xml->ActivityTypes, FALSE, TRUE, FALSE);
    $this->assertEquals(
      array_values([
        13 => 'Open Case',
        56 => 'Medical evaluation changed',
        57 => 'Mental health evaluation',
        58 => 'Secure temporary housing',
        61 => 'Income and benefits stabilization',
        59 => 'Long-term housing plan',
        14 => 'Follow up',
        15 => 'Change Case Type',
        16 => 'Change Case Status',
        18 => 'Change Case Start Date',
        25 => 'Link Cases',
      ]),
      array_values($activityTypes)
    );
  }

  private function getActivityTypeXMl(): SimpleXMLElement {
    try {
      $activityTypeXml = '<activity-type><name>Open Case</name></activity-type>';
      return new SimpleXMLElement($activityTypeXml);
    }
    catch (Exception $e) {
      $this->fail('xml not loaded');
    }
  }

}
