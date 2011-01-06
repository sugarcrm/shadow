#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_shadow.h"
#include "shadow_cache.h"

/* if the cache is full, clean it */
void shadow_cache_check_full(TSRMLS_D)
{
	if(zend_hash_num_elements(&SHADOW_G(cache)) >= SHADOW_G(cache_size)) {
		shadow_cache_clean(TSRMLS_C);
	}
}

/* set cache segment ID from template/instance pair */
void shadow_cache_set_id(const char *template, const char *instance TSRMLS_DC)
{
	char *segname;
	int namelen;
	uint *psegment;

	namelen = spprintf(&segname, 0, "0\x9%s\x9%s", template, instance);
	if(zend_hash_find(&SHADOW_G(cache), segname, namelen+1, (void **)&psegment) != SUCCESS) {
		uint segment;
		shadow_cache_check_full(TSRMLS_C);
		segment = zend_hash_num_elements(&SHADOW_G(cache))+2; /* 0 and 1 are reserved */
		psegment = &segment;
		zend_hash_update(&SHADOW_G(cache), segname, namelen+1, (void **)psegment, sizeof(uint), NULL);
		SHADOW_G(segment_id) = *psegment;
	}
	efree(segname);
	SHADOW_G(segment_id) = *psegment;
}

int shadow_cache_segmented_name(char **outname, const char *name TSRMLS_DC)
{
	return spprintf(outname, 0, "%d\x9%s", SHADOW_G(segment_id), name);
}

int shadow_cache_get(const char *name, char **entry TSRMLS_DC)
{
	char *segname;
	int namelen;
	char *centry;
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	if(zend_hash_find(&SHADOW_G(cache), segname, namelen+1, (void **)&centry) == SUCCESS) {
		if(centry[0]) {
			*entry = estrdup(centry);
		} else {
			*entry = NULL;
		}
		efree(segname);
		return SUCCESS;
	}
	efree(segname);
	return FAILURE;
}

void shadow_cache_put(const char *name, const char *entry TSRMLS_DC)
{
	char *segname;
	int namelen;
	/* will copy the string */
	if(!entry) {
		entry = "";
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_hash_update(&SHADOW_G(cache), segname, namelen+1, (void **)entry, strlen(entry)+1, NULL);
	efree(segname);
}

void shadow_cache_remove(const char *name TSRMLS_DC)
{
	char *segname;
	int namelen;
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_hash_del(&SHADOW_G(cache), name, namelen+1);
	efree(segname);
}

void shadow_cache_clean(TSRMLS_C)
{
	zend_hash_clean(&SHADOW_G(cache));
}
