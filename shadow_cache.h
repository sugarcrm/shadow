void shadow_cache_set_id(const char *template, const char *instance TSRMLS_DC);
int shadow_cache_get(const char *name, char **entry TSRMLS_DC);
void shadow_cache_put(const char *name, const char *entry TSRMLS_DC);
void shadow_cache_remove(const char *name TSRMLS_DC);
void shadow_cache_clean(TSRMLS_D);
