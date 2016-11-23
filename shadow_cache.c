/*
 * Copyright (C) 2014, SugarCRM Inc. 
 *
 *  This product is licensed by SugarCRM under the Apache License, Version 2.0 (the "License"). 
 *  You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 */

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
void shadow_cache_set_id(zend_string *template, zend_string *instance TSRMLS_DC)
{
	char *segname;
	int namelen;
	uint *psegment;

	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	namelen = spprintf(&segname, 0, "0\x9%s\x9%s", template->val, instance->val);
	if ((psegment = zend_hash_str_find_ptr(&SHADOW_G(cache), segname, namelen + 1)) == NULL) {
		uint segment;
		shadow_cache_check_full(TSRMLS_C);
		segment = zend_hash_num_elements(&SHADOW_G(cache))+2; /* 0 and 1 are reserved */
		psegment = &segment;
		zend_string *segname_zs = zend_string_init(segname, namelen + 1, 0);
		zend_hash_update_ptr(&SHADOW_G(cache), segname_zs, psegment);
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
	if(SHADOW_G(cache_size) == 0) {
		return FAILURE;
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	if ((centry = zend_hash_str_find_ptr(&SHADOW_G(cache), segname, namelen+1)) != NULL) {
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
	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	/* will copy the string */
	if(!entry) {
		entry = "";
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_string *segname_zs = zend_string_init(segname, namelen + 1, 0);
	zend_hash_update_ptr(&SHADOW_G(cache), segname_zs, (void **)entry);
	efree(segname);
}

void shadow_cache_remove(const char *name TSRMLS_DC)
{
	char *segname;
	int namelen;
	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_hash_str_del(&SHADOW_G(cache), segname, namelen + 1);
	efree(segname);
}

void shadow_cache_clean(TSRMLS_C)
{
	zend_hash_clean(&SHADOW_G(cache));
}
