{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Export Wizard - Step 2 *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
<div class="crm-block crm-form-block crm-export-form-block">

 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}

 <div id="export-type">
  <div class="crm-section crm-exportOption-section">
    <h3>{ts count=$totalSelectedRecords plural='%count records selected for export.'}One record selected for export.{/ts}</h3>
    <div class="content-no-label crm-content-exportOption">
        {$form.exportOption.html}
   </div>
  </div>

  <div id="map" class="crm-section crm-export-mapping-section">
      {if $form.mapping }
        <div class="label crm-label-export-mapping">
            {$form.mapping.label}
        </div>
        <div class="content crm-content-export-mapping">
            {$form.mapping.html}
        </div>
    <div class="clear"></div>
      {/if}
  </div>

  {if $isShowMergeOptions}
  <div class="crm-section crm-export-mergeOptions-section">
    <div class="label crm-label-mergeOptions">{ts}Merge Options{/ts} {help id="id-export_merge_options"}</div>
    <div class="content crm-content-mergeOptions">
      {$form.mergeOption.html}
    </div>
    <div id='greetings' class="content crm-content-greetings class='hiddenElement'">
      <table class="form-layout-compressed">
        <tr>
           <td>{$form.postal_greeting.label}</td>
           <td>{$form.postal_greeting.html}</td>
        </tr>
        <tr id='postal_greeting_other_wrapper' class='hiddenElement'>
           <td>{$form.postal_greeting_other.label}</td>
           <td>{$form.postal_greeting_other.html}</td>
        </tr>
        <tr><td></td><td></td></tr>
        <tr>
           <td>{$form.addressee.label}</td>
           <td>{$form.addressee.html}</td>
        </tr>
        <tr id='addressee_other_wrapper' class='hiddenElement'>
           <td>{$form.addressee_other.label}</td>
           <td>{$form.addressee_other.html}</td>
        </tr>
      </table>
      <div class="clear">&nbsp;</div>
    </div>

    <div class="content crm-content-mergeSameHousehold">
        &nbsp;{$form.merge_same_household.html}
    </div>
    <br/>
    <div class="label crm-label-postalMailingExport">{$form.postal_mailing_export.label}</div>
    <div class="content crm-content-postalMailingExport">
        &nbsp;{$form.postal_mailing_export.html}
        {ts}Exclude contacts with "do not mail" privacy, no street address, or who are deceased.{/ts}
    </div>
    <br/>
    <div class="label crm-label-additionalGroup">{$form.additional_group.label}</div>
    <div class="content crm-content-additionalGroup">
        &nbsp;{$form.additional_group.html}
    </div>
  <div class="clear"></div>
  </div>
  {/if}

 </div>

 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
  <script type="text/javascript">
     var matchingContacts = '';
     {/literal}{if $matchingContacts}{literal}
       matchingContacts = {/literal}'{$matchingContacts}'{literal};
     {/literal}{/if}{literal}

     function showGreetingOptions( )
     {
        var mergeAddress = cj( "input[name='mergeOption']:checked" ).val( );

        if ( mergeAddress == 1 ) {
            cj( "#greetings" ).show( );
        } else {
            cj( "#greetings" ).hide( );
  }
     }

     function showOther( ele )
     {
        if ( cj('option:selected', ele).text( ) == '{/literal}{ts escape='js'}Other{/ts}{literal}' ) {
     cj('#' + cj(ele).attr('id') + '_other_wrapper').show( );
        } else {
          cj('#' + cj(ele).attr('id') + '_other').val('');
    cj('#' + cj(ele).attr('id') + '_other_wrapper').hide( );
  }
     }

     showGreetingOptions( );
     showOther(cj('#postal_greeting'));
     showOther(cj('#addressee'));
  </script>
{/literal}
