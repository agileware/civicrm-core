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
dm_install_cvext_unsupported org.civicrm.shoreditch "$TRG/ext/org.civicrm.shoreditch" --dev
dm_install_cvext_unsupported org.civicrm.flexmailer "$TRG/ext/org.civicrm.flexmailer" --dev
dm_install_cvext_unsupported uk.co.vedaconsulting.mosaico "$TRG/ext/uk.co.vedaconsulting.mosaico" --dev
dm_install_cvext org.civicrm.api4 "$TRG/ext/api4"
dm_install_cvext biz.jmaconsulting.lineitemedit "$TRG/ext/biz.jmaconsulting.lineitemedit"

# gen tarball
cd $TRG/..
tar czf $DM_TARGETDIR/civicrm-$DM_VERSION-drupal.tar.gz civicrm

# clean up
rm -rf $TRG
