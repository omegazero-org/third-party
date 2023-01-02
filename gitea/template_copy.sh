#!/bin/sh

mkdir -p custom/templates/base
mkdir -p custom/templates/user/auth
mkdir -p custom/templates/user/settings/security

cp ../repo/templates/base/footer_content.tmpl custom/templates/base/footer_content.tmpl
cp ../repo/templates/base/head.tmpl custom/templates/base/head.tmpl
cp ../repo/templates/base/head_navbar.tmpl custom/templates/base/head_navbar.tmpl
cp ../repo/templates/home.tmpl custom/templates/home.tmpl
cp ../repo/templates/user/auth/link_account.tmpl custom/templates/user/auth/link_account.tmpl
cp ../repo/templates/user/auth/signin_inner.tmpl custom/templates/user/auth/signin_inner.tmpl
cp ../repo/templates/user/settings/account.tmpl custom/templates/user/settings/account.tmpl
cp ../repo/templates/user/settings/applications_oauth2.tmpl custom/templates/user/settings/applications_oauth2.tmpl
cp ../repo/templates/user/settings/profile.tmpl custom/templates/user/settings/profile.tmpl
cp ../repo/templates/user/settings/security/accountlinks.tmpl custom/templates/user/settings/security/accountlinks.tmpl
cp ../repo/templates/user/settings/security/security.tmpl custom/templates/user/settings/security/security.tmpl
cp ../repo/templates/user/settings/security/twofa.tmpl custom/templates/user/settings/security/twofa.tmpl
cp ../repo/templates/user/settings/security/twofa_enroll.tmpl custom/templates/user/settings/security/twofa_enroll.tmpl
