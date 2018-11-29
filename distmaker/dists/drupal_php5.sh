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
dm_install_cvext_unsupported 'uk.co.vedaconsulting.mosaico@https://storage.googleapis.com/civicrm/mosaico/2.0-beta4.1528762072/uk.co.vedaconsulting.mosaico-2.0-beta4.1528762072.zip' "$TRG/ext/uk.co.vedaconsulting.mosaico" --dev
dm_install_cvext org.civicrm.api4 "$TRG/ext/api4"
dm_install_cvext biz.jmaconsulting.lineitemedit "$TRG/ext/biz.jmaconsulting.lineitemedit"
dm_install_cvext net.ourpowerbase.sumfields "$TRG/ext/net.ourpowerbase.sumfields"
dm_install_cvext eu.tttp.noverwrite "$TRG/ext/eu.tttp.noverwrite"
dm_install_cvext com.joineryhq.activityical "$TRG/ext/com.joineryhq.activityical"
dm_install_cvext nz.co.fuzion.extendedreport "$TRG/ext/nz.co.fuzion.extendedreport"
dm_install_cvext org.wikimedia.relationshipblock "$TRG/ext/org.wikimedia.relationshipblock"
dm_install_cvext org.civicrm.contactlayout "$TRG/ext/org.civicrm.contactlayout"
dm_install_cvext org.civicrm.tutorial "$TRG/ext/org.civicrm.tutorial"
dm_install_cvext nz.co.fuzion.civitoken "$TRG/ext/nz.co.fuzion.civitoken"
dm_install_cvext org.civicrm.module.cividiscount "$TRG/ext/org.civicrm.module.cividiscount"

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal.tar.gz civicrm

# clean up
rm -rf $TRG
