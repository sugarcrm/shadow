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
	zval *segment_zv;

	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	namelen = spprintf(&segname, 0, "0\x9%s\x9%s", template->val, instance->val);
	zend_string *segname_zs = zend_string_init(segname, namelen, 0);
	if ((segment_zv = zend_hash_find(&SHADOW_G(cache), segname_zs)) == NULL) {
		uint segment;
		shadow_cache_check_full(TSRMLS_C);
		segment = zend_hash_num_elements(&SHADOW_G(cache))+2; /* 0 and 1 are reserved */
		zval segment_new_zv;
		ZVAL_LONG(&segment_new_zv, segment);
		zend_hash_update(&SHADOW_G(cache), segname_zs, &segment_new_zv);
		SHADOW_G(segment_id) = segment;
	} else {
		SHADOW_G(segment_id) = Z_LVAL_P(segment_zv);
	}
    zend_string_release(segname_zs);
	efree(segname);
}

int shadow_cache_segmented_name(char **outname, const char *name TSRMLS_DC)
{
	return spprintf(outname, 0, "%d\x9%s", SHADOW_G(segment_id), name);
}

int shadow_cache_get(const char *name, char **entry TSRMLS_DC)
{
	char *segname;
	int namelen;
	zval *centry;
	if(SHADOW_G(cache_size) == 0) {
		return FAILURE;
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_string *segname_zs = zend_string_init(segname, namelen, 0);
	if ((centry = zend_hash_find(&SHADOW_G(cache), segname_zs)) != NULL) {
        if(Z_STRLEN_P(centry) == 0){
            return NULL;
        }
		*entry = estrdup(Z_STR_P(centry)->val);
		efree(segname);
        zend_string_release(segname_zs);
		return SUCCESS;
	} else {
		*entry = NULL;
		efree(segname);
        zend_string_release(segname_zs);
		return FAILURE;
	}
}

void shadow_cache_put(const char *name, const char *entry TSRMLS_DC)
{
	char *segname;
	int namelen;
	zend_string * entry_zs;
	zval entry_zv;
	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	/* will copy the string */
	if(!entry) {
		entry = "";
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_string *segname_zs = zend_string_init(segname, namelen, 0);
	entry_zs = zend_string_init(entry, strlen(entry), 0);
	ZVAL_STR(&entry_zv, entry_zs);
	zend_hash_update(&SHADOW_G(cache), segname_zs, &entry_zv);
	efree(segname);
    zend_string_release(segname_zs);
}

void shadow_cache_remove(const char *name TSRMLS_DC)
{
	char *segname;
	int namelen;
	if(SHADOW_G(cache_size) == 0) {
		return;
	}
	namelen = shadow_cache_segmented_name(&segname, name TSRMLS_CC);
	zend_hash_str_del(&SHADOW_G(cache), segname, namelen);
	efree(segname);
}

void shadow_cache_clean(TSRMLS_C)
{
	zend_hash_clean(&SHADOW_G(cache));
}
