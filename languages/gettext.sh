#---------------------------0
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro-donations.pot, cd to the main /pmpro-donations/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro.pot open before running script above)
# then execute `cp languages/pmpro-donations.pot languages/pmpro-donations.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-donations.po --output-file languages/pmpro-donations.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-donations.pot... "
xgettext -j -o languages/pmpro-donations.pot \
--default-domain=pmpro-donations \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"