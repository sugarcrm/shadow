dnl $Id$
dnl config.m4 for extension shadow


PHP_ARG_ENABLE(shadow, whether to enable shadow support,
  [  --enable-shadow           Enable shadow support])

if test "$PHP_SHADOW" != "no"; then
  PHP_NEW_EXTENSION(shadow, shadow.c, $ext_shared)
fi
