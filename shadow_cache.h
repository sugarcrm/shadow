int shadow_cache_get(const char *name, int namelen, char **entry TSRMLS_CC);
void shadow_cache_put(const char *name, int namelen, char *entry TSRMLS_CC);
void shadow_cache_remove(const char *name, int namelen TSRMLS_CC);
void shadow_cache_clean(TSRMLS_C);
