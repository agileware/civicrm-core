
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
dm_reset_dirs "$TRG" "$TRG/civicrm/civicrm"
cp $SRC/WordPress/civicrm.config.php.wordpress $TRG/civicrm/civicrm/civicrm.config.php
dm_generate_version "$TRG/civicrm/civicrm/civicrm-version.php" Wordpress
dm_install_core "$SRC" "$TRG/civicrm/civicrm"
dm_install_packages "$SRC/packages" "$TRG/civicrm/civicrm/packages"
dm_install_vendor "$SRC/vendor" "$TRG/civicrm/civicrm/vendor"
dm_install_bower "$SRC/bower_components" "$TRG/civicrm/civicrm/bower_components"
dm_install_wordpress "$SRC/WordPress" "$TRG/civicrm"
dm_install_cvext_bundled "$SRC/ext" "$TRG/civicrm/civicrm/ext"
dm_install_cvext_unsupported 'uk.co.vedaconsulting.mosaico@https://storage.googleapis.com/civicrm/mosaico/2.0-beta4.1528762072/uk.co.vedaconsulting.mosaico-2.0-beta4.1528762072.zip' "$TRG/civicrm/civicrm/ext/uk.co.vedaconsulting.mosaico" --dev
dm_install_cvext org.civicrm.api4 "$TRG/civicrm/civicrm/ext/api4"
dm_install_cvext biz.jmaconsulting.lineitemedit "$TRG/civicrm/civicrm/ext/biz.jmaconsulting.lineitemedit"
dm_install_cvext net.ourpowerbase.sumfields "$TRG/civicrm/civicrm/ext/net.ourpowerbase.sumfields"
dm_install_cvext eu.tttp.noverwrite "$TRG/civicrm/civicrm/ext/eu.tttp.noverwrite"
dm_install_cvext com.joineryhq.activityical "$TRG/civicrm/civicrm/ext/com.joineryhq.activityical"
dm_install_cvext nz.co.fuzion.extendedreport "$TRG/civicrm/civicrm/ext/nz.co.fuzion.extendedreport"
dm_install_cvext org.wikimedia.relationshipblock "$TRG/civicrm/civicrm/ext/org.wikimedia.relationshipblock"
dm_install_cvext org.civicrm.angularprofiles "$TRG/civicrm/civicrm/ext/org.civicrm.angularprofiles"
dm_install_cvext org.civicrm.contactlayout "$TRG/civicrm/civicrm/ext/org.civicrm.contactlayout"
dm_install_cvext org.civicrm.tutorial "$TRG/civicrm/civicrm/ext/org.civicrm.tutorial"
dm_install_cvext nz.co.fuzion.civitoken "$TRG/civicrm/civicrm/ext/nz.co.fuzion.civitoken"
dm_install_cvext org.civicrm.module.cividiscount "$TRG/civicrm/civicrm/ext/org.civicrm.module.cividiscount"
dm_install_cvext org.civicoop.emailapi "$TRG/civicrm/civicrm/ext/org.civicoop.emailapi"

# gen tarball
cd $TRG
${DM_ZIP:-zip} -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-wordpress.zip *

# gen wporg tarball
touch "$TRG/civicrm/civicrm/.use-civicrm-setup"
cp "$TRG/civicrm/civicrm/vendor/civicrm/civicrm-setup/plugins/blocks/opt-in.disabled.php" "$TRG/civicrm/civicrm/vendor/civicrm/civicrm-setup/plugins/blocks/opt-in.civi-setup.php"
cd "$TRG"
${DM_ZIP:-zip} -r -9 $DM_TARGETDIR/civicrm-$DM_VERSION-wporg.zip *

# clean up
rm -rf $TRG
