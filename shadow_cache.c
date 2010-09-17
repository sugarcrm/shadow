#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_shadow.h"
#include "shadow_cache.h"

int shadow_cache_get(const char *name, int namelen, char **entry TSRMLS_CC)
{
	char *centry;
	if(zend_hash_find(&SHADOW_G(cache), name, namelen+1, (void **)&centry) == SUCCESS) {
		if(centry[0]) {
			*entry = estrdup(centry);
		} else {
			*entry = NULL;
		}
		return SUCCESS;
	}
	return FAILURE;
}

void shadow_cache_put(const char *name, int namelen, char *entry TSRMLS_CC)
{
	/* will copy the string */
	if(!entry) {
		entry = "";
	}
	zend_hash_update(&SHADOW_G(cache), name, namelen+1, (void **)entry, strlen(entry)+1, NULL);
}

void shadow_cache_remove(const char *name, int namelen TSRMLS_CC)
{
	zend_hash_del(&SHADOW_G(cache), name, namelen+1);
}

void shadow_cache_clean(TSRMLS_C)
{
	zend_hash_clean(&SHADOW_G(cache));
}
