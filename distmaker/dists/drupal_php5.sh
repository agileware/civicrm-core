#!/bin/bash
set -ex

P=`dirname $0`
CFFILE=$P/../distmaker.conf
if [ ! -f $CFFILE ] ; then
	echo "NO DISTMAKER.CONF FILE!"
	exit 1
else
	. $CFFILE
fi
. "$P/common.sh"

SRC=$DM_SOURCEDIR
TRG=$DM_TMPDIR/civicrm

# copy all the stuff
dm_reset_dirs "$TRG"
cp $SRC/drupal/civicrm.config.php.drupal $TRG/civicrm.config.php
dm_generate_version "$TRG/civicrm-version.php" Drupal
dm_install_core "$SRC" "$TRG"
dm_install_packages "$SRC/packages" "$TRG/packages"
dm_install_vendor "$SRC/vendor" "$TRG/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/bower_components"
dm_install_drupal "$SRC/drupal" "$TRG/drupal"
dm_install_cvext_bundled "$SRC/ext" "$TRG/ext"
dm_install_cvext_unsupported 'uk.co.vedaconsulting.mosaico@https://download.civicrm.org/extension/uk.co.vedaconsulting.mosaico/latest/uk.co.vedaconsulting.mosaico-latest.zip' "$TRG/ext/uk.co.vedaconsulting.mosaico" --dev
dm_install_cvext org.civicrm.api4 "$TRG/ext/api4"
# Installing from git for bugfix # dm_install_cvext biz.jmaconsulting.lineitemedit "$TRG/ext/biz.jmaconsulting.lineitemedit"
dm_install_cvext com.cividesk.apikey "$TRG/ext/com.cividesk.apikey"
dm_install_cvext com.joineryhq.activityical "$TRG/ext/com.joineryhq.activityical"
dm_install_cvext com.pogstone.contenttokens "$TRG/ext/com.pogstone.contenttokens"
dm_install_cvext eu.tttp.noverwrite "$TRG/ext/eu.tttp.noverwrite"
dm_install_cvext eu.tttp.bootstrapvisualize "$TRG/ext/eu.tttp.bootstrapvisualize"
dm_install_cvext eu.tttp.civisualize "$TRG/ext/eu.tttp.civisualize"
dm_install_cvext net.ourpowerbase.sumfields "$TRG/ext/net.ourpowerbase.sumfields"
dm_install_cvext nz.co.fuzion.civitoken "$TRG/ext/nz.co.fuzion.civitoken"
dm_install_cvext nz.co.fuzion.csvimport "$TRG/ext/nz.co.fuzion.csvimport"
dm_install_cvext nz.co.fuzion.entitysetting "$TRG/ext/nz.co.fuzion.entitysetting"
dm_install_cvext nz.co.fuzion.extendedreport "$TRG/ext/nz.co.fuzion.extendedreport"
dm_install_cvext nz.co.fuzion.relatedpermissions "$TRG/ext/nz.co.fuzion.relatedpermissions"
dm_install_cvext org.civicoop.emailapi "$TRG/ext/org.civicoop.emailapi"
dm_install_cvext org.civicrm.angularprofiles "$TRG/ext/org.civicrm.angularprofiles"
dm_install_cvext org.civicrm.contactlayout "$TRG/ext/org.civicrm.contactlayout"
dm_install_cvext org.civicrm.module.cividiscount "$TRG/ext/org.civicrm.module.cividiscount"
dm_install_cvext org.civicrm.sms.twilio "$TRG/ext/org.civicrm.sms.twilio"
dm_install_cvext org.civicrm.tutorial "$TRG/ext/org.civicrm.tutorial"
dm_install_cvext org.wikimedia.relationshipblock "$TRG/ext/org.wikimedia.relationshipblock"
dm_install_cvext uk.co.compucorp.civicrm.pivotreport "$TRG/ext/uk.co.compucorp.civicrm.pivotreport"
dm_install_cvext uk.co.vedaconsulting.gdpr "$TRG/ext/uk.co.vedaconsulting.gdpr"
dm_install_cvext uk.co.vedaconsulting.mailchimp "$TRG/ext/uk.co.vedaconsulting.mailchimp"
dm_install_cvext uk.squiffle.kam "$TRG/ext/uk.squiffle.kam"

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal.tar.gz civicrm

# clean up
rm -rf $TRG
