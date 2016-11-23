/*
 * Copyright (C) 2014, SugarCRM Inc. 
 *
 *  This product is licensed by SugarCRM under the Apache License, Version 2.0 (the "License"). 
 *  You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 */

void shadow_cache_set_id(zend_string *template, zend_string *instance TSRMLS_DC);
int shadow_cache_get(const char *name, char **entry TSRMLS_DC);
void shadow_cache_put(const char *name, const char *entry TSRMLS_DC);
void shadow_cache_remove(const char *name TSRMLS_DC);
void shadow_cache_clean(TSRMLS_D);
