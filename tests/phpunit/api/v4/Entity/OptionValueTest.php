<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class OptionValueTest extends Api4TestBase implements TransactionalInterface {

  public function testNullDefault(): void {
    OptionGroup::create(FALSE)
      ->addValue('name', 'myTestGroup')
      ->addValue('title', 'myTestGroup')
      ->execute();

    $defaultId = OptionValue::create()
      ->addValue('option_group_id.name', 'myTestGroup')
      ->addValue('label', 'One')
      ->addValue('value', 1)
      ->addValue('is_default', TRUE)
      ->execute()->first()['id'];

    $this->assertTrue(OptionValue::get(FALSE)->addWhere('id', '=', $defaultId)->execute()->first()['is_default']);

    // Now create a second option with is_default set to null.
    // This should not interfere with the default setting in option one
    OptionValue::create()
      ->addValue('option_group_id.name', 'myTestGroup')
      ->addValue('label', 'Two')
      ->addValue('value', 2)
      ->addValue('is_default', NULL)
      ->execute();

    $this->assertTrue(OptionValue::get(FALSE)->addWhere('id', '=', $defaultId)->execute()->first()['is_default']);
  }

  public function testUpdateWeights(): void {
    $getValues = function($groupName) {
      return OptionValue::get(FALSE)
        ->addWhere('option_group_id.name', '=', $groupName)
        ->addOrderBy('weight')
        ->execute()->column('weight', 'value');
    };

    // Create 2 option groups. Control group is to ensure updating one doesn't affect the other
    foreach (['controlGroup', 'experimentalGroup'] as $groupName) {
      OptionGroup::create(FALSE)
        ->addValue('name', $groupName)
        ->execute();
      $sampleData = [
        ['label' => 'One', 'value' => 1],
        ['label' => 'Two', 'value' => 2],
        ['label' => 'Three', 'value' => 3],
        ['label' => 'Four', 'value' => 4],
      ];
      OptionValue::save(FALSE)
        ->setRecords($sampleData)
        ->addDefault('option_group_id.name', $groupName)
        ->execute();
      // Default weights should have been set during create
      $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $getValues($groupName));
    }

    // Move first option to last position
    OptionValue::update(FALSE)
      ->addWhere('option_group_id.name', '=', 'experimentalGroup')
      ->addWhere('value', '=', 1)
      ->addValue('weight', 4)
      ->execute();
    // Experimental group should be updated, control group should not
    $this->assertEquals([2 => 1, 3 => 2, 4 => 3, 1 => 4], $getValues('experimentalGroup'));
    $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $getValues('controlGroup'));

    // Move 2nd (new first) option to last position
    OptionValue::update(FALSE)
      ->addWhere('option_group_id.name', '=', 'experimentalGroup')
      ->addWhere('value', '=', 2)
      ->addValue('weight', 4)
      ->execute();
    // Experimental group should be updated, control group should not
    $this->assertEquals([3 => 1, 4 => 2, 1 => 3, 2 => 4], $getValues('experimentalGroup'));
    $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $getValues('controlGroup'));

    // Move last option to first position
    OptionValue::update(FALSE)
      ->addWhere('option_group_id.name', '=', 'experimentalGroup')
      ->addWhere('value', '=', 2)
      ->addValue('weight', 1)
      ->execute();
    // Experimental group should be updated, control group should not
    $this->assertEquals([2 => 1, 3 => 2, 4 => 3, 1 => 4], $getValues('experimentalGroup'));
    $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $getValues('controlGroup'));

    // Same thing again - should have no impact
    OptionValue::update(FALSE)
      ->addWhere('option_group_id.name', '=', 'experimentalGroup')
      ->addWhere('value', '=', 2)
      ->addValue('weight', 1)
      ->execute();
    // Nothing should have changed
    $this->assertEquals([2 => 1, 3 => 2, 4 => 3, 1 => 4], $getValues('experimentalGroup'));
    $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $getValues('controlGroup'));
  }

  public function testEnsureOptionGroupExistsNewValue(): void {
    OptionGroup::create(FALSE)
      ->addValue('name', 'Bombed')
      ->addValue('title', 'Bombed')
      ->execute();
    $optionGroups = OptionValue::getFields(FALSE)
      ->addWhere('name', '=', 'option_group_id')
      ->setLoadOptions(TRUE)
      ->execute()->first()['options'];
    $this->assertContains('Bombed', $optionGroups);

    OptionGroup::create(FALSE)
      ->addValue('name', 'Bombed Again')
      ->addValue('title', 'Bombed Again')
      ->execute();
    $optionGroups = OptionValue::getFields(FALSE)
      ->addWhere('name', '=', 'option_group_id')
      ->setLoadOptions(TRUE)
      ->execute()->first()['options'];
    $this->assertContains('Bombed Again', $optionGroups);
  }

  /**
   * Tests legacy adapter for accessing SiteEmailAddress via the OptionValue api
   * @see \Civi\API\Subscriber\SiteEmailLegacyOptionValueAdapter
   */
  public function testLegacyFromEmailAddressOptionGroup(): void {
    $email1 = OptionValue::create(FALSE)
      ->addValue('option_group_id.name', 'from_email_address')
      ->addValue('label', '"Legacy Test1"   <spaces@get.removed>')
      ->execute()->single();
    $email2 = OptionValue::create(FALSE)
      ->addValue('option_group_id:name', 'from_email_address')
      ->addValue('name', ' "Legacy Test2"<no@space.ok> ')
      ->execute()->single();

    $allEmails = OptionValue::get(FALSE)
      ->addSelect('label', 'domain_id')
      ->addWhere('option_group_id.name', '=', 'from_email_address')
      ->execute()->indexBy('id');

    $this->assertEquals('"Legacy Test1" <spaces@get.removed>', $allEmails[$email1['id']]['label']);
    $this->assertEquals('"Legacy Test2" <no@space.ok>', $allEmails[$email2['id']]['name']);
    $this->assertEquals(\CRM_Core_Config::domainID(), $allEmails[$email1['id']]['domain_id']);

    $result = OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', '=', 'from_email_address')
      ->addWhere('label', '=', '"Legacy Test1" <spaces@get.removed>')
      ->addWhere('value', '=', $email1['id'])
      ->addOrderBy('weight')
      ->execute()->single();
    $this->assertEquals('"Legacy Test1" <spaces@get.removed>', $result['label']);
    $this->assertEquals('1', $result['weight']);

    $result = OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'from_email_address')
      ->addWhere('label', 'LIKE', '%Legacy Test1%')
      ->addValue('label', '"Updated Test1" <my@new.email>')
      ->execute()->single();

    $result = OptionValue::get(FALSE)
      ->addWhere('option_group_id.name', '=', 'from_email_address')
      ->addWhere('value', '=', $email1['id'])
      ->execute()->single();
    $this->assertEquals('"Updated Test1" <my@new.email>', $result['label']);
  }

}
