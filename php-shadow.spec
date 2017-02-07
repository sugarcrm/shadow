%global php_apiver  %((echo 0; php -i 2>/dev/null | sed -n 's/^PHP API => //p') | tail -1)
%global php_extdir  %(php-config --extension-dir 2>/dev/null || echo "undefined")
%global php_version %(php-config --version 2>/dev/null || echo 0)

Name:           php-shadow
Version:        0.4.1
Release:        1sugar
Summary:        Shadow is a multitenancy-support module for Sugar

Group:          Development/Languages
License:        PHP
URL:            http://github.com/sugarcrm/shadow
Source0:        shadow-%{version}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildRequires:  php-devel
Requires:       php(zend-abi) = %{php_zend_api}
Requires:       php(api) = %{php_apiver}

%description
Shadow

%prep
%setup -q -n shadow-%{version}

%build
%{_bindir}/phpize
%configure
make %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
make install INSTALL_ROOT=$RPM_BUILD_ROOT

# install configuration
%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/php.d
mkdir -p $RPM_BUILD_ROOT/etc/php.d
%{__cp} sugarcrm/shadow.ini $RPM_BUILD_ROOT/etc/php.d/shadow.ini

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%config(noreplace) %{_sysconfdir}/php.d/shadow.ini
%{php_extdir}/shadow.so

%changelog
* Mon Jan 27 2017 Alex Vlasov <avlasov@sugarcrm.com> - 0.5.0
- PHP 7
* Wed Jan 25 2017 Jason Corley <jcorley@sugarcrm.com> - 0.4.1
- bump version to 0.4.1, last release supporting PHP 5.x
* Wed Oct  5 2016 Jason Corley <jcorley@sugarcrm.com> - 0.4.0
- bump version to 0.4.0, PHP 5.6 support added
* Fri May 22 2015 Michael Gusev <mgusev@sugarcrm.com> - 0.3.12
- Enabling shadow_resolve_path to support correct behavior with opcache
* Wed Feb 27 2013 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.11
- Fix treating dirs ending on /
* Mon Feb 25 2013 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.10
- Fix listing main instance directory (for 6.7 autoloader)
* Mon Feb 04 2013 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.7
- Fix class method overriding, update shadow.ini
* Thu Jan 03 2013 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.6
- Bugfixes and new shadow.ini
* Wed Dec 19 2012 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.5
- Add config overrides
* Sat Dec 15 2012 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.4
- bugfixes
* Fri Dec 14 2012 Stas Malyshev <smalyshev@sugarcrm.com> - 0.3.1
- 0.3 adds glob support
* Wed Apr 18 2012 Uriah Welcome <uriah@sugarcrm.com> - 0.0.1-1
- initial packaging
