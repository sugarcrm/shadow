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
#include "ext/standard/info.h"
#include "php_shadow.h"
#include "php_streams.h"
#include <fcntl.h>
#include "shadow_cache.h"
#include "ext/standard/php_filestat.h"

#if PHP_VERSION_ID < 50600
#define cwd_state_estrdup(str) strdup(str);
#define cwd_state_efree(str) free(str);
#else
#define cwd_state_estrdup(str) estrdup(str);
#define cwd_state_efree(str) efree(str);
#endif

ZEND_DECLARE_MODULE_GLOBALS(shadow)

typedef struct _shadow_function {
	zend_function original;
	void (*orig_handler)(INTERNAL_FUNCTION_PARAMETERS);
	int argno;
	int argtype;
} shadow_function;

PHP_FUNCTION(shadow);
PHP_FUNCTION(shadow_get_config);
PHP_FUNCTION(shadow_clear_cache);

static php_stream_wrapper_ops shadow_wrapper_ops;
php_stream_wrapper shadow_wrapper = {
	&shadow_wrapper_ops,
	NULL,
	0
};

static size_t shadow_dirstream_read(php_stream *stream, char *buf, size_t count TSRMLS_DC);
static int shadow_dirstream_close(php_stream *stream, int close_handle TSRMLS_DC);
static int shadow_dirstream_rewind(php_stream *stream, off_t offset, int whence, off_t *newoffs TSRMLS_DC);

static php_stream_ops shadow_dirstream_ops = {
	NULL,
	shadow_dirstream_read,
	shadow_dirstream_close,
	NULL,
	"shadow dir",
	shadow_dirstream_rewind,
	NULL, /* cast */
	NULL, /* stat */
	NULL  /* set_option */
};

static php_stream_wrapper_ops *plain_ops;
static char *(*original_zend_resolve_path)(const char *filename, int filename_len TSRMLS_DC);
static void (*orig_touch)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_chmod)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_chdir)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_fread)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_realpath)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_is_writable)(INTERNAL_FUNCTION_PARAMETERS);
static void (*orig_glob)(INTERNAL_FUNCTION_PARAMETERS);

static char *shadow_resolve_path(const char *filename, int filename_len TSRMLS_DC);
static php_stream *shadow_stream_opener(php_stream_wrapper *wrapper, const char *filename, const char *mode,
	int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC);
static int shadow_stat(php_stream_wrapper *wrapper, const char *url, int flags, php_stream_statbuf *ssb,
	php_stream_context *context TSRMLS_DC);
static int shadow_unlink(php_stream_wrapper *wrapper, const char *url, int options, php_stream_context *context TSRMLS_DC);

static int shadow_rename(php_stream_wrapper *wrapper, const char *url_from, const char *url_to, int options, php_stream_context *context TSRMLS_DC);
static int shadow_mkdir(php_stream_wrapper *wrapper, const char *dir, int mode, int options, php_stream_context *context TSRMLS_DC);
static int shadow_rmdir(php_stream_wrapper *wrapper, const char *url, int options, php_stream_context *context TSRMLS_DC);
static php_stream *shadow_dir_opener(php_stream_wrapper *wrapper, const char *path, const char *mode, int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC);
static void shadow_touch(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_chmod(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_chdir(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_fread(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_realpath(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_is_writable(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_glob(INTERNAL_FUNCTION_PARAMETERS);
static void shadow_generic_override(INTERNAL_FUNCTION_PARAMETERS);

ZEND_BEGIN_ARG_INFO_EX(arginfo_shadow, 0, 0, 2)
	ZEND_ARG_INFO(0, template)
	ZEND_ARG_INFO(0, instance)
	ZEND_ARG_INFO(0, instance_only)
ZEND_END_ARG_INFO()

/* {{{ shadow_functions[]
 *
 */
const zend_function_entry shadow_functions[] = {
	PHP_FE(shadow,	arginfo_shadow)
	PHP_FE(shadow_get_config,	NULL)
	PHP_FE(shadow_clear_cache,	NULL)
	{NULL, NULL, NULL}
};
/* }}} */

/* {{{ php_shadow_init_globals
 */
static PHP_GINIT_FUNCTION(shadow)
{
	memset(shadow_globals, 0, sizeof(zend_shadow_globals));
	zend_hash_init(&shadow_globals->cache, 10, NULL, NULL, 1); // persistent!
}
/* }}} */

/* {{{ php_shadow_shutdown_globals
 */
static PHP_GSHUTDOWN_FUNCTION(shadow)
{
	zend_hash_destroy(&shadow_globals->cache);
}
/* }}} */

/* {{{ shadow_module_entry
 */
zend_module_entry shadow_module_entry = {
	STANDARD_MODULE_HEADER,
	"shadow",
	shadow_functions,
	PHP_MINIT(shadow),
	PHP_MSHUTDOWN(shadow),
	PHP_RINIT(shadow),
	PHP_RSHUTDOWN(shadow),
	PHP_MINFO(shadow),
	SHADOW_VERSION,
    PHP_MODULE_GLOBALS(shadow),
    PHP_GINIT(shadow),
    PHP_GSHUTDOWN(shadow),
    NULL,
	STANDARD_MODULE_PROPERTIES_EX
};
/* }}} */

#ifdef COMPILE_DL_SHADOW
ZEND_GET_MODULE(shadow)
#endif

/* {{{ PHP_INI
 */
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("shadow.enabled",      "1", PHP_INI_ALL, OnUpdateBool, enabled, zend_shadow_globals, shadow_globals)
    STD_PHP_INI_ENTRY("shadow.mkdir_mask",      "0755", PHP_INI_ALL, OnUpdateLong, mkdir_mask, zend_shadow_globals, shadow_globals)
    STD_PHP_INI_ENTRY("shadow.debug",      "0", PHP_INI_ALL, OnUpdateLong, debug, zend_shadow_globals, shadow_globals)
    STD_PHP_INI_ENTRY("shadow.cache_size",      "10000", PHP_INI_ALL, OnUpdateLong, cache_size, zend_shadow_globals, shadow_globals)
    STD_PHP_INI_ENTRY("shadow.override",      "", PHP_INI_SYSTEM, OnUpdateString, override, zend_shadow_globals, shadow_globals)
PHP_INI_END()
/* }}} */

#define SHADOW_CONSTANT(C) 		REGISTER_LONG_CONSTANT(#C, C, CONST_CS)

#define SHADOW_OVERRIDE(func) \
	orig_##func = NULL; \
	if (zend_hash_find(CG(function_table), #func, sizeof(#func), (void **)&orig) == SUCCESS) { \
        orig_##func = orig->internal_function.handler; \
        orig->internal_function.handler = shadow_##func; \
	}

#define SHADOW_ENABLED() (SHADOW_G(enabled) != 0 && SHADOW_G(instance) != NULL && SHADOW_G(template) != NULL)

static void shadow_override_function(char *fname, int fname_len, int argno, int argtype)
{
	zend_function *orig;
	shadow_function override;
	HashTable *table = CG(function_table);
	char *col;

	if((col = strchr(fname, ':')) != NULL) {
		zend_class_entry **cls;
		*col = '\0';
		if(zend_hash_find(CG(class_table), fname, col-fname+1, (void **)&cls) != SUCCESS) {
			return;
		}
		table = &((*cls)->function_table);
		fname = col+2;
		fname_len = strlen(fname);
	}

	if (zend_hash_find(table, fname, fname_len+1, (void **)&orig) != SUCCESS) {
		return;
	}
	memcpy(&override, orig, sizeof(zend_function));
	override.orig_handler = orig->internal_function.handler;
	override.original.internal_function.handler = shadow_generic_override;
	override.argno = argno;
	override.argtype = argtype;
	zend_hash_update(table, fname, fname_len+1, &override, sizeof(shadow_function), NULL);
}

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(shadow)
{
	zend_function *orig;

	REGISTER_INI_ENTRIES();

	SHADOW_CONSTANT(SHADOW_DEBUG_FULLPATH);
	SHADOW_CONSTANT(SHADOW_DEBUG_OPEN);
	SHADOW_CONSTANT(SHADOW_DEBUG_STAT);
	SHADOW_CONSTANT(SHADOW_DEBUG_MKDIR);
	SHADOW_CONSTANT(SHADOW_DEBUG_OPENDIR);
	SHADOW_CONSTANT(SHADOW_DEBUG_RESOLVE);
	SHADOW_CONSTANT(SHADOW_DEBUG_UNLINK);
	SHADOW_CONSTANT(SHADOW_DEBUG_RENAME);
	SHADOW_CONSTANT(SHADOW_DEBUG_PATHCHECK);
	SHADOW_CONSTANT(SHADOW_DEBUG_ENSURE);
	SHADOW_CONSTANT(SHADOW_DEBUG_FAIL);
	SHADOW_CONSTANT(SHADOW_DEBUG_TOUCH);
	SHADOW_CONSTANT(SHADOW_DEBUG_CHMOD);
	SHADOW_CONSTANT(SHADOW_DEBUG_OVERRIDE);

	plain_ops = php_plain_files_wrapper.wops;

	memcpy(&shadow_wrapper_ops, plain_ops, sizeof(shadow_wrapper_ops));
	shadow_wrapper_ops.stream_opener = shadow_stream_opener;
	shadow_wrapper_ops.url_stat = shadow_stat;
	shadow_wrapper_ops.unlink = shadow_unlink;
	shadow_wrapper_ops.rename = shadow_rename;
	shadow_wrapper_ops.stream_mkdir = shadow_mkdir;
	shadow_wrapper_ops.stream_rmdir = shadow_rmdir;
	shadow_wrapper_ops.dir_opener = shadow_dir_opener;
	shadow_wrapper_ops.label = "shadow";
	if(SHADOW_G(enabled)) {
		original_zend_resolve_path = zend_resolve_path;
		zend_resolve_path = shadow_resolve_path;
	}

	SHADOW_OVERRIDE(touch);
	SHADOW_OVERRIDE(chmod);
	SHADOW_OVERRIDE(chdir);
	SHADOW_OVERRIDE(fread);
	SHADOW_OVERRIDE(realpath);
	SHADOW_OVERRIDE(is_writable);
	SHADOW_OVERRIDE(glob);

	/* Override functions. Config format:
	 * shadow.override=func1,func2@w1,func2,class::func4
	 */
	if(SHADOW_G(enabled) && SHADOW_G(override) && SHADOW_G(override)[0] != '\0') {
		char *over = SHADOW_G(override);
		int over_len;
		char c;
		int argno;
		int argtype;
		while(*over) {
			char *next = strchr(over, ',');
			if(!next) {
				next = over+strlen(over);
			}
			for(over_len=0;over_len<next-over;over_len++) {
				/* find @ or , */
				if(over[over_len] == '@' || over[over_len] == ',' || over[over_len] =='\0') break;
			}
			argno = 0;
			argtype = 0;
			if(over[over_len] == '@') {
				if(!isdigit(over[over_len+1])) {
					if(over[over_len+1] == 'w') {
						argtype = 1;
					}
					argno = atoi(over+over_len+2);
				} else {
					argno = atoi(over+over_len+1);
				}
			}
			c = over[over_len];
			over[over_len] = '\0';
			shadow_override_function(over, over_len, argno, argtype);
			over[over_len] = c;
			if(*next) {
				over = next+1;
			} else {
				break;
			}
		}

	}

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(shadow)
{
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(shadow)
{
	if(SHADOW_G(enabled)) {
		php_unregister_url_stream_wrapper_volatile("file");
		php_register_url_stream_wrapper_volatile("file", &shadow_wrapper);
	}
	SHADOW_G(template) = NULL;
	SHADOW_G(instance) = NULL;
	SHADOW_G(curdir) = NULL;
	SHADOW_G(segment_id) = 0;
	return SUCCESS;
}
/* }}} */

static void shadow_free_data()
{
	if(SHADOW_G(template)) {
		efree(SHADOW_G(template));
		SHADOW_G(template) = NULL;
	}
	if(SHADOW_G(instance)) {
		efree(SHADOW_G(instance));
		SHADOW_G(instance) = NULL;
	}
	if(SHADOW_G(instance_only)) {
		int i;
		for(i=0;i<SHADOW_G(instance_only_count);i++) {
			efree(SHADOW_G(instance_only)[i]);
		}
		efree(SHADOW_G(instance_only));
		SHADOW_G(instance_only) = NULL;
	}
	if(SHADOW_G(curdir)) {
		free(SHADOW_G(curdir));
		SHADOW_G(curdir) = NULL;
	}
}

/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(shadow)
{
	shadow_free_data();
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(shadow)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "shadow support", "enabled");
	php_info_print_table_header(2, "shadow version", SHADOW_VERSION);
	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();
}
/* }}} */


/* {{{ proto void shadow(string template, string instance[, array instance_only])
   Initiate template/instance shadowing */
PHP_FUNCTION(shadow)
{
	char *temp = NULL;
	char *inst = NULL;
	int temp_len, inst_len;
	HashTable *instance_only = NULL; /* paths relative to template root */

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|h", &temp, &temp_len, &inst, &inst_len, &instance_only) == FAILURE) {
		return;
	}

	if(!SHADOW_G(enabled)) {
		RETURN_FALSE;
	}

	shadow_free_data();
	php_clear_stat_cache(0, NULL, 0 TSRMLS_CC);
	if(temp_len == 0 || inst_len == 0) {
		/* empty arg means turn it off */
		RETURN_TRUE;
	}

	SHADOW_G(template) = zend_resolve_path(temp, temp_len);
	if(!SHADOW_G(template)) {
		RETURN_FALSE;
	}
	SHADOW_G(template_len) = strlen(SHADOW_G(template));
	SHADOW_G(instance) = zend_resolve_path(inst, inst_len);
	if(!SHADOW_G(instance)) {
		efree(SHADOW_G(template));
		SHADOW_G(template) = NULL;
		RETURN_FALSE;
	}

	SHADOW_G(instance_len) = strlen(SHADOW_G(instance));
	shadow_cache_set_id(SHADOW_G(template), SHADOW_G(instance));
	if(instance_only) {
		int i = 0;
		HashPosition pos;
		zval **item;

		SHADOW_G(instance_only_count) = zend_hash_num_elements(instance_only);
		SHADOW_G(instance_only) = ecalloc(SHADOW_G(instance_only_count), sizeof(char *));
		zend_hash_internal_pointer_reset_ex(instance_only, &pos);
			while(zend_hash_get_current_data_ex(instance_only, (void **)&item, &pos) == SUCCESS) {
			convert_to_string(*item);
			SHADOW_G(instance_only)[i++] = estrndup(Z_STRVAL_PP(item), Z_STRLEN_PP(item));
			zend_hash_move_forward_ex(instance_only, &pos);
		}
	}

	RETURN_TRUE;
}
/* }}} */

/* {{{ proto array shadow_get_config()
   Retrieve current shadow configuration */
PHP_FUNCTION(shadow_get_config)
{
	zval *instance_only;
	int i;

	if (zend_parse_parameters_none() == FAILURE) {
		return;
	}

	if(!SHADOW_G(enabled)) {
		RETURN_FALSE;
	}

	array_init_size(return_value, 3);
	add_assoc_string(return_value, "template", SHADOW_G(template)?SHADOW_G(template):"", 1);
	add_assoc_string(return_value, "instance", SHADOW_G(instance)?SHADOW_G(instance):"", 1);
	ALLOC_INIT_ZVAL(instance_only);
	array_init_size(instance_only, SHADOW_G(instance_only_count));
	for(i=0;i<SHADOW_G(instance_only_count);i++) {
		add_next_index_string(instance_only, SHADOW_G(instance_only)[i], 1);
	}
	add_assoc_zval(return_value, "instance_only", instance_only);
}
/* }}} */

/* {{{ proto array shadow_clear_cache()
   Clear cached data */
PHP_FUNCTION(shadow_clear_cache)
{
	if (zend_parse_parameters_none() == FAILURE) {
		return;
	}

	if(!SHADOW_G(enabled)) {
		RETURN_FALSE;
	}

	shadow_cache_clean(TSRMLS_C);
	if(SHADOW_G(curdir)) {
		free(SHADOW_G(curdir));
		SHADOW_G(curdir) = NULL;
	}
}
/* }}} */

/*
 Check if the path is among "instance only" pathes
*/
static int instance_only_subdir(const char *dir TSRMLS_DC)
{
	int i;
	if(SHADOW_G(instance_only) == NULL) {
		return 0;
	}
	for(i=0;i<SHADOW_G(instance_only_count);i++) {
		/* TODO: optimize strlen here */
		int len = strlen(SHADOW_G(instance_only)[i]);
		if(memcmp(dir, SHADOW_G(instance_only)[i], len) == 0 && (!dir[len] || IS_SLASH(dir[len]))) {
			return 1;
		}
	}

	return 0;
}

/*
 * Check if the path is inside "instance only" area
*/
static int is_instance_only(const char *filename TSRMLS_DC)
{
	char *realpath = NULL;
	int fnamelen = strlen(filename);
	int result;

	if(!SHADOW_ENABLED() || SHADOW_G(instance_only) == NULL) {
		return 0;
	}

	if(!IS_ABSOLUTE_PATH(filename, fnamelen)) {
		realpath = expand_filepath(filename, NULL TSRMLS_CC);
		if(!realpath) {
			return 0;
		}
		filename = realpath;
		fnamelen = strlen(realpath);
	}

	if(SHADOW_G(template_len)+1 <= fnamelen &&
		memcmp(SHADOW_G(template), filename, SHADOW_G(template_len)) == 0 &&
		IS_SLASH(filename[SHADOW_G(template_len)])
	) {
		result = instance_only_subdir(filename+SHADOW_G(template_len)+1 TSRMLS_CC);
		if(realpath) {
			efree(realpath);
		}
		return result;
	}

	if(SHADOW_G(instance_len)+1 <= fnamelen &&
		memcmp(SHADOW_G(instance), filename, SHADOW_G(instance_len)) == 0 &&
		IS_SLASH(filename[SHADOW_G(instance_len)])) {
		result = instance_only_subdir(filename+SHADOW_G(instance_len)+1 TSRMLS_CC);
		if(realpath) {
			efree(realpath);
		}
		return result;
	}
	return 0;
}

/*
 * Check if path is subdir of dir
 */
static inline int is_subdir_of(const char *dir, int dirlen, const char *path, int pathlen)
{
	if(dirlen+1 < pathlen &&
		memcmp(dir, path, dirlen) == 0 &&
		IS_SLASH(path[dirlen])) {
			return 1;
	}
	return 0;
}

static char *get_full_path(const char *filename TSRMLS_DC)
{
	cwd_state new_state;

	if(!SHADOW_G(curdir)) {
		SHADOW_G(curdir) = getcwd(NULL, 0);
	}

	new_state.cwd = cwd_state_estrdup(SHADOW_G(curdir));
	new_state.cwd_length = strlen(SHADOW_G(curdir));
	if (virtual_file_ex(&new_state, filename, NULL, CWD_FILEPATH)) {
		if(new_state.cwd) {
			cwd_state_efree(new_state.cwd);
		}
        return NULL;
    }
	char *full_path = estrndup(new_state.cwd, new_state.cwd_length);
	if (new_state.cwd) {
		cwd_state_efree(new_state.cwd);
	}
	return full_path;
}

static inline char *instance_to_template(const char *instname, int len TSRMLS_DC)
{
	char *newname = NULL;
	if(is_subdir_of(SHADOW_G(template), SHADOW_G(template_len), instname, len)) {
		newname = estrndup(instname, len);
	} else if(is_subdir_of(SHADOW_G(instance), SHADOW_G(instance_len), instname, len)) {
		spprintf(&newname, MAXPATHLEN, "%s/%s", SHADOW_G(template), instname+SHADOW_G(instance_len)+1);
	}
	return newname;
}

/* check if file exists */
#define OPT_CHECK_EXISTS 	1
/* do not cache resolution result */
#define OPT_SKIP_CACHE 		2
/* if given instance path, return realpath */
#define OPT_RETURN_INSTANCE 4

/*
Returns new instance path or NULL if template path is OK
filename is relative to template root
*/
static char *template_to_instance(const char *filename, int options TSRMLS_DC)
{
	char *realpath = NULL;
	int fnamelen = strlen(filename);
	char *newname = NULL;

	if(!SHADOW_ENABLED()) {
		return NULL;
	}

	/* Always get the full path since there can be symlinks and stuff like // or ..'s in there */
	realpath = get_full_path(filename TSRMLS_DC);
	if(SHADOW_G(debug) & SHADOW_DEBUG_FULLPATH)	fprintf(stderr, "Full path: %s\n", realpath);
	if(!realpath) {
		return NULL;
	}
	fnamelen = strlen(realpath);
	while(IS_SLASH(realpath[fnamelen-1]) && fnamelen > 1) {
		realpath[fnamelen-1] = '\0';
		fnamelen--;
	}

	if(is_subdir_of(SHADOW_G(template), SHADOW_G(template_len), realpath, fnamelen)) {
		if(SHADOW_G(debug) & SHADOW_DEBUG_PATHCHECK) fprintf(stderr, "In template: %s\n", realpath);
		if((options & OPT_CHECK_EXISTS) && shadow_cache_get(realpath, &newname) == SUCCESS) {
			if(SHADOW_G(debug) & SHADOW_DEBUG_PATHCHECK) fprintf(stderr, "Path check from cache: %s => %s\n", realpath, newname);
			if(realpath) {
            			efree(realpath);
            		}
			return newname;
		}
		/* starts with template - rewrite to instance */
		spprintf(&newname, MAXPATHLEN, "%s/%s", SHADOW_G(instance), realpath+SHADOW_G(template_len)+1);
		if((options & OPT_CHECK_EXISTS) && !instance_only_subdir(realpath+SHADOW_G(template_len)+1 TSRMLS_CC)) {
			if(VCWD_ACCESS(newname, F_OK) != 0) {
				/* file does not exist */
				efree(newname);
				newname = NULL;
			}
			/* drop down to return */
		}
		if(!(options & OPT_SKIP_CACHE)) {
			shadow_cache_put(realpath, newname);
		}
	} else if(is_subdir_of(SHADOW_G(instance), SHADOW_G(instance_len), realpath, fnamelen)) {
		if(SHADOW_G(debug) & SHADOW_DEBUG_PATHCHECK) fprintf(stderr, "In instance: %s\n", realpath);
		if((options & OPT_CHECK_EXISTS)) {
			/* starts with instance, may want to check template too */
			if(!instance_only_subdir(realpath+SHADOW_G(instance_len)+1 TSRMLS_CC) && VCWD_ACCESS(realpath, F_OK) != 0) {
				/* does not exist, go to template */
				spprintf(&newname, MAXPATHLEN, "%s/%s", SHADOW_G(template), realpath+SHADOW_G(instance_len)+1);
			} else {
				/* TODO: use realpath here too? */
				if((options & OPT_RETURN_INSTANCE)) {
					newname = estrndup(realpath, fnamelen);
					efree(realpath);
					realpath = NULL;
				} else {
					newname = NULL;
				}
			}
		} else {
			/* use already resolved name if we are writing - this way we can use it for recursive mkdir */
			newname = estrndup(realpath, fnamelen);
			efree(realpath);
			realpath = NULL;
		}
	} else if((options & OPT_RETURN_INSTANCE) && strncmp(SHADOW_G(instance), realpath, SHADOW_G(instance_len)) == 0
			&& (realpath[SHADOW_G(instance_len)] == '\0' || IS_SLASH(realpath[SHADOW_G(instance_len)]))) {
		/* it is the instance dir itself - return it */
		newname = estrndup(realpath, fnamelen);
	}

	if(SHADOW_G(debug) & SHADOW_DEBUG_PATHCHECK)	fprintf(stderr, "Path check: %s => %s\n", realpath, newname);
	if(realpath) {
		efree(realpath);
	}
	return newname;
}

static void clean_cache_dir(const char *clean_dirname TSRMLS_DC)
{
	int len = strlen(clean_dirname);
	char *dirname = instance_to_template(clean_dirname, len);
	if(!dirname) return; /* not an instance dir */
	len = strlen(dirname);
	shadow_cache_remove(dirname);
	while(len > SHADOW_G(template_len)) {
		char c;
		while(len > SHADOW_G(template_len) && !IS_SLASH(dirname[len])) len--;
		/* remove both one with slash at the end and without it, just in case */
		dirname[len+1] = '\0';
		shadow_cache_remove(dirname);
		dirname[len] = '\0';
		shadow_cache_remove(dirname);
		len--;
	}
	efree(dirname);
}

static void ensure_dir_exists(char *pathname, php_stream_wrapper *wrapper, php_stream_context *context TSRMLS_DC)
{
	int dir_len;
	if(!pathname) {
		return;
	}
	dir_len = zend_dirname(pathname, strlen(pathname));
	if(VCWD_ACCESS(pathname, F_OK) != 0) {
		/* does not exist */
		if(SHADOW_G(debug) & SHADOW_DEBUG_ENSURE)	 fprintf(stderr, "Creating: %s %ld\n", pathname, SHADOW_G(mkdir_mask));
		plain_ops->stream_mkdir(wrapper, pathname, SHADOW_G(mkdir_mask), PHP_STREAM_MKDIR_RECURSIVE|REPORT_ERRORS, context TSRMLS_CC);
		clean_cache_dir(pathname);
	}
	pathname[dir_len] = '/'; /* restore full path */
}

static char *shadow_resolve_path(const char *filename, int filename_len TSRMLS_DC)
{
    char *result = template_to_instance(filename, OPT_CHECK_EXISTS TSRMLS_CC);
    // in any case we have to call original resolver because that can be reimplemented by opcache for example
    if (result) {
        int result_length = strlen(result);
        result = original_zend_resolve_path(result, result_length TSRMLS_CC);
    } else {
        result = original_zend_resolve_path(filename, filename_len TSRMLS_CC);
    }
    if(SHADOW_G(debug) & SHADOW_DEBUG_RESOLVE) fprintf(stderr, "Resolve: %s -> %s\n", filename, result);
    return result;
}

static php_stream *shadow_stream_opener(php_stream_wrapper *wrapper, const char *filename, const char *mode,
	int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC)
{
	int flags;
	php_stream *res;
	if(SHADOW_ENABLED()) {
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPEN)  fprintf(stderr, "Opening: %s %s\n", filename, mode);
	}
	if(php_stream_parse_fopen_modes(mode, &flags) == SUCCESS) {
		if(flags & (O_WRONLY|O_RDWR)) {
			// writing
			char *instname = template_to_instance(filename, 0 TSRMLS_CC);
			if(instname) {
				if(SHADOW_G(debug) & SHADOW_DEBUG_OPEN) fprintf(stderr, "Opening instead: %s %s\n", instname, mode);
				ensure_dir_exists(instname, wrapper, context TSRMLS_CC);
				res = plain_ops->stream_opener(wrapper, instname, mode, options|STREAM_ASSUME_REALPATH, opened_path, context STREAMS_CC TSRMLS_CC);
				if(!res && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
					fprintf(stderr, "Open FAIL: %s %s [%d]\n", instname, mode, errno);
				}
				efree(instname);
				return res;
			}
		} else {
			// reading
			char *instname = template_to_instance(filename, OPT_CHECK_EXISTS TSRMLS_CC);
			if(instname) {
				if(SHADOW_G(debug) & SHADOW_DEBUG_OPEN) fprintf(stderr, "Opening instead: %s %s\n", instname, mode);
		 		res = plain_ops->stream_opener(wrapper, instname, mode, options|STREAM_ASSUME_REALPATH, opened_path, context STREAMS_CC TSRMLS_CC);
				if(!res && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
					fprintf(stderr, "Open FAIL: %s %s [%d]\n", instname, mode, errno);
				}
				efree(instname);
				return res;
			}
		}
	}
	return plain_ops->stream_opener(wrapper, filename, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
}

/**
 * PHP uses access() for plain files, but manual mask matches for stream files.
 * This leads to the problem that is_writable() returns false for root if filemask is not other-write
 * This hack sets mode to 777 if current user is root
 */
static void adjust_stat(php_stream_statbuf *ssb)
{
	if(geteuid() == 0) {
		ssb->sb.st_mode |= 0777;
	}
}

static int shadow_stat(php_stream_wrapper *wrapper, const char *url, int flags, php_stream_statbuf *ssb, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, OPT_CHECK_EXISTS TSRMLS_CC);
	int res;
	if(SHADOW_ENABLED()) {
		if(SHADOW_G(debug) & SHADOW_DEBUG_STAT)  fprintf(stderr, "Stat: %s (%s) %d\n", url, instname, flags);
	}
	if(instname) {
		res = plain_ops->url_stat(wrapper, instname, flags, ssb, context TSRMLS_CC);
		if(SHADOW_G(debug) & SHADOW_DEBUG_STAT)  fprintf(stderr, "Stat res: %d\n", res);
		efree(instname);
		adjust_stat(ssb);
		return res;
	}
	res = plain_ops->url_stat(wrapper, url, flags, ssb, context TSRMLS_CC);
	if(SHADOW_G(debug) & SHADOW_DEBUG_STAT)  fprintf(stderr, "Stat res: %d\n", res);
	adjust_stat(ssb);
	return res;
}

static int shadow_unlink(php_stream_wrapper *wrapper, const char *url, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, 0 TSRMLS_CC);
	int res;
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_UNLINK) fprintf(stderr, "Unlink: %s (%s) %d\n", url, instname, options);
	if(instname) {
		url = instname;
		if(SHADOW_ENABLED()) {
			char *tempname = instance_to_template(instname, strlen(instname) TSRMLS_CC);
			if(tempname) {
				shadow_cache_remove(tempname);
				efree(tempname);
			}
		}
	}
	res = plain_ops->unlink(wrapper, url, options, context TSRMLS_CC);
	if(instname) {
		efree(instname);
	}
	return res;
}

static int shadow_rename(php_stream_wrapper *wrapper, const char *url_from, const char *url_to, int options, php_stream_context *context TSRMLS_DC)
{
	char *fromname = template_to_instance(url_from, OPT_CHECK_EXISTS TSRMLS_CC);
	char *toname = template_to_instance(url_to, 0 TSRMLS_CC);
	int res;
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_RENAME) fprintf(stderr, "Rename: %s(%s) -> %s(%s) %d\n", url_from, fromname, url_to, toname, options);
	if(SHADOW_ENABLED()) {
		shadow_cache_remove(url_from);
	}
	if(fromname) {
		url_from = fromname;
	}
	if(toname) {
		url_to = toname;
	}
	res = plain_ops->rename(wrapper, url_from, url_to, options, context TSRMLS_CC);
	if(!res && SHADOW_ENABLED() && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
		fprintf(stderr, "Rename FAIL: %s -> %s  [%d]\n", url_from, url_to, errno);
	}
	if(fromname) {
		efree(fromname);
	}
	if(toname) {
		efree(toname);
	}
	return res;
}

static int shadow_mkdir(php_stream_wrapper *wrapper, const char *dir, int mode, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(dir, 0 TSRMLS_CC);
	int res;
	if(instname) {
		dir = instname;
		/* always use recursive to create unexisting paths */
		options |= PHP_STREAM_MKDIR_RECURSIVE;
	}
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_MKDIR)  fprintf(stderr, "Mkdir: %s (%s) %d %d\n", dir, instname, mode, options);
	res = plain_ops->stream_mkdir(wrapper, dir, mode, options, context TSRMLS_CC);
	clean_cache_dir(dir);
	if(SHADOW_ENABLED() && !res && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
		fprintf(stderr, "Mkdir FAIL: %s %d %d [%d]\n", dir, mode, options, errno);
	}
	if(instname) {
		efree(instname);
	}
	return res;
}

static int shadow_rmdir(php_stream_wrapper *wrapper, const char *url, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, 0 TSRMLS_CC);
	int res;
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_MKDIR) fprintf(stderr, "Rmdir: %s (%s) %d\n", url, instname, options);
	if(SHADOW_ENABLED()) {
		shadow_cache_remove(url);
	}
	if(instname) {
		url = instname;
	}
	res = plain_ops->stream_rmdir(wrapper, url, options, context TSRMLS_CC);
	if(instname) {
		efree(instname);
	}
	return res;
}

static php_stream *shadow_dir_opener(php_stream_wrapper *wrapper, const char *path, const char *mode, int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC)
{
	char *instname;
	php_stream *tempdir = NULL, *instdir, *mergestream;
	HashTable *mergedata;
	php_stream_dirent entry;
	void *dummy = (void *)1;
	char *templname = NULL;

	if(options & STREAM_USE_GLOB_DIR_OPEN) {
		/* not dealing with globs yet */
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening glob dir: %s\n", path);
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}
	if(is_instance_only(path TSRMLS_CC)) {
		/* if it's instance-only dir, we won't merge in any case */
		instname = template_to_instance(path, 0 TSRMLS_CC);
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening instance only: %s\n", path);
		instdir = plain_ops->dir_opener(wrapper, instname, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
		efree(instname);
		return instdir;
	}
	instname = template_to_instance(path, OPT_CHECK_EXISTS|OPT_RETURN_INSTANCE TSRMLS_CC);
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opendir: %s (%s)\n", path, instname);
	if(!instname) {
		/* we don't have instance dir, don't bother with merging */
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening template: %s\n", path);
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}

	instdir = plain_ops->dir_opener(wrapper, instname, mode, options&(~REPORT_ERRORS), opened_path, context STREAMS_CC TSRMLS_CC);
	if(!instdir) {
		/* instance dir failed, return just template one */
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening template w/o instance: %s\n", path);
		efree(instname);
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}

	if(is_subdir_of(SHADOW_G(template), SHADOW_G(template_len), instname, strlen(instname))) {
		/* instname is in a template, we don't need another template name */
	} else {
		if (strlen(instname) > SHADOW_G(instance_len)) {
			spprintf(&templname, MAXPATHLEN, "%s/%s", SHADOW_G(template), instname+SHADOW_G(instance_len)+1);
		} else {
			templname = estrdup(SHADOW_G(template));
		}
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening templdir: %s\n", templname);
		tempdir = plain_ops->dir_opener(wrapper, templname, mode, options&(~REPORT_ERRORS), opened_path, context STREAMS_CC TSRMLS_CC);
		efree(templname);
	}
	efree(instname);
	if(!tempdir) {
		/* template dir failed, return just instance */
		return instdir;
	}
	/* now we have both dirs, so we need to create a merge dir */
	/* TODO: figure out why we need these flags */
	instdir->flags |= PHP_STREAM_FLAG_NO_BUFFER;
	tempdir->flags |= PHP_STREAM_FLAG_NO_BUFFER;

	ALLOC_HASHTABLE(mergedata);
	zend_hash_init(mergedata, 10, NULL, NULL, 0);
	while(php_stream_readdir(tempdir, &entry)) {
		zend_hash_add(mergedata, entry.d_name, strlen(entry.d_name), &dummy, sizeof(void *), NULL);
	}
	while(php_stream_readdir(instdir, &entry)) {
		zend_hash_add(mergedata, entry.d_name, strlen(entry.d_name), &dummy, sizeof(void *), NULL);
	}
	zend_hash_internal_pointer_reset(mergedata);
	php_stream_free(instdir, PHP_STREAM_FREE_CLOSE);
	php_stream_free(tempdir, PHP_STREAM_FREE_CLOSE);

	/* give back the data as stream */
	mergestream = php_stream_alloc(&shadow_dirstream_ops, mergedata, 0, mode);
	if(!mergestream) {
		zend_hash_destroy(mergedata);
		FREE_HASHTABLE(mergedata);
		return NULL;
	}
	return mergestream;
}

static size_t shadow_dirstream_read(php_stream *stream, char *buf, size_t count TSRMLS_DC)
{
	php_stream_dirent *ent = (php_stream_dirent*)buf;
	HashTable *mergedata = (HashTable *)stream->abstract;
	char *name = NULL;
	int namelen = 0;
	ulong num;

	/* avoid problems if someone mis-uses the stream */
	if (count != sizeof(php_stream_dirent))
		return 0;

	if(zend_hash_get_current_key_ex(mergedata, &name, &namelen, &num, 0, NULL) != HASH_KEY_IS_STRING) {
		return 0;
	}
	if(!name || !namelen) {
		return 0;
	}
	zend_hash_move_forward(mergedata);

	PHP_STRLCPY(ent->d_name, name, sizeof(ent->d_name), namelen);
	return sizeof(php_stream_dirent);
}

static int shadow_dirstream_close(php_stream *stream, int close_handle TSRMLS_DC)
{
	zend_hash_destroy((HashTable *)stream->abstract);
	FREE_HASHTABLE(stream->abstract);
	return 0;
}

static int shadow_dirstream_rewind(php_stream *stream, off_t offset, int whence, off_t *newoffs TSRMLS_DC)
{
	zend_hash_internal_pointer_reset((HashTable *)stream->abstract);
	return 0;
}

/*
Find Nth argument of a current function call
*/
static zval **shadow_get_arg(int arg TSRMLS_DC)
{
	void **p;
	int arg_count;

	if(!EG(current_execute_data)) {
		return NULL;
	}

	p = EG(current_execute_data)->function_state.arguments;
	if(!p) {
		return NULL;
	}

	arg_count = (int)(zend_uintptr_t) *p;
	if(arg >= arg_count) {
		return NULL;
	}

	p -= arg_count;
	p += arg;

	return (zval **)p;
}

/*
 * Call original function while replacing name parameter with repname
 */
static int shadow_call_replace_name(int param, char *repname, void (*orig_func)(INTERNAL_FUNCTION_PARAMETERS), INTERNAL_FUNCTION_PARAMETERS)
{
	zval *old_name, *new_name;
	zval **name;
	name = shadow_get_arg(param TSRMLS_CC);
	if(!name || !*name) {
		orig_func(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return FAILURE;
	}
	old_name = *name;
	ALLOC_INIT_ZVAL(new_name);
	ZVAL_STRING(new_name, repname, 0);
	*name = new_name;
	orig_func(INTERNAL_FUNCTION_PARAM_PASSTHRU);
	*name = old_name;
	zval_ptr_dtor(&new_name);
	return SUCCESS;
}

/*
 * Check if this file belongs to us
 */
static int shadow_stream_check(char *filename TSRMLS_DC)
{
	php_stream_wrapper *wrapper;
	wrapper = php_stream_locate_url_wrapper(filename, NULL, 0 TSRMLS_CC);
	return wrapper == &php_plain_files_wrapper || wrapper == &shadow_wrapper;
}

/* {{{ proto bool touch(string filename [, int time [, int atime]])
   Set modification time of file */
static void shadow_touch(INTERNAL_FUNCTION_PARAMETERS)
{
	char *filename;
	int filename_len;
	long filetime = 0, fileatime = 0;
	char *instname;

	if(!SHADOW_ENABLED()) {
		orig_touch(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|ll", &filename, &filename_len, &filetime, &fileatime) == FAILURE) {
		return;
	}
	if(!shadow_stream_check(filename)) {
		orig_touch(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	instname = template_to_instance(filename, 0 TSRMLS_CC);

	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_TOUCH) fprintf(stderr, "Touching %s (%s)\n", filename, instname);
	if(instname) {
		ensure_dir_exists(instname, &shadow_wrapper, NULL TSRMLS_CC);
		shadow_call_replace_name(0, instname, orig_touch, INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	orig_touch(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

/* {{{ proto bool chmod(string filename, int mode)
   Change file mode */
static void shadow_chmod(INTERNAL_FUNCTION_PARAMETERS)
{
	char *filename;
	int filename_len;
	long mode;
	char *instname;

	if(!SHADOW_ENABLED()) {
		orig_chmod(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &filename, &filename_len, &mode) == FAILURE) {
		return;
	}
	if(!shadow_stream_check(filename)) {
		orig_chmod(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	instname = template_to_instance(filename, OPT_CHECK_EXISTS TSRMLS_CC);

	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_CHMOD) fprintf(stderr, "Chmod %s (%s) %lo\n", filename, instname, mode);
	/* Clear cache because PHP caches non-plain-file stats */
	php_clear_stat_cache(0, NULL, 0 TSRMLS_CC);

	if(instname) {
		shadow_call_replace_name(0, instname, orig_chmod, INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	orig_chmod(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

/* {{{ proto bool chdir(string filename)
   Change current dir */
static void shadow_chdir(INTERNAL_FUNCTION_PARAMETERS)
{
	char *str;
	int ret, str_len;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &str, &str_len) == FAILURE) {
		RETURN_FALSE;
	}
	if(SHADOW_G(curdir)) {
		free(SHADOW_G(curdir));
		SHADOW_G(curdir) = NULL;
	}
	orig_chdir(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* TODO: chown */

/* {{{ proto string fread(resource fp, int length)
   Binary-safe file read */
static void shadow_fread(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *arg1;
	long len;
	php_stream *stream;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rl", &arg1, &len) == FAILURE) {
		RETURN_FALSE;
	}

	php_stream_from_zval(stream, &arg1);
	if(stream->wrapper == &shadow_wrapper) {
		char		*contents	= NULL;
		int newlen;

		if (len <= 0) {
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "Length parameter must be greater than 0");
			RETURN_FALSE;
		}

		len = php_stream_copy_to_mem(stream, &contents, len, 0);
		if (contents) {
#if ZEND_MODULE_API_NO < 20100525
			if (len && PG(magic_quotes_runtime)) {
				contents = php_addslashes(contents, len, &newlen, 1 TSRMLS_CC); /* 1 = free source string */
				len = newlen;
			}
#endif
			RETVAL_STRINGL(contents, len, 0);
		} else {
			RETVAL_EMPTY_STRING();
		}
	} else {
		orig_fread(INTERNAL_FUNCTION_PARAM_PASSTHRU);
	}
}
/* }}} */

/* {{{ proto string realpath(string path)
   Return the resolved path */
static void shadow_realpath(INTERNAL_FUNCTION_PARAMETERS)
{
	char *filename;
	int filename_len;
	char *instname, *copy_name = NULL;

	if(!SHADOW_ENABLED()) {
		orig_realpath(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &filename, &filename_len) == FAILURE) {
		return;
	}
	if(!shadow_stream_check(filename)) {
		orig_realpath(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	instname = template_to_instance(filename, OPT_SKIP_CACHE TSRMLS_CC);

	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_RESOLVE) fprintf(stderr, "Realpath %s (%s)\n", filename, instname);

	if(instname) {
		copy_name = estrdup(instname);
		shadow_call_replace_name(0, instname, orig_realpath, INTERNAL_FUNCTION_PARAM_PASSTHRU);
		if(Z_TYPE_P(return_value) == IS_STRING) {
			return;
		}
	}

	orig_realpath(INTERNAL_FUNCTION_PARAM_PASSTHRU);
	if(Z_TYPE_P(return_value) == IS_STRING) {
		if(copy_name) {
			efree(copy_name);
		}
		return;
	}
	if(copy_name) {
		ZVAL_STRING(return_value, copy_name, 0);
	}
}
/* }}} */

/* {{{ proto bool is_writable(string filename)
   Returns true if file can be written */
static void shadow_is_writable(INTERNAL_FUNCTION_PARAMETERS)
{
	char *filename = NULL;
	int filename_len;
	char *instname;
	zval **name;
	zval *old_name, *new_name;

	if(!SHADOW_ENABLED()) {
		orig_is_writable(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &filename, &filename_len) == FAILURE) {
		return;
	}
	if(!shadow_stream_check(filename)) {
		orig_is_writable(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	instname = template_to_instance(filename, OPT_SKIP_CACHE TSRMLS_CC);
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_STAT) fprintf(stderr, "is_writable %s (%s)\n", filename, instname);
	if(!instname) {
		/* Didn't find anything - use original handler */
		orig_is_writable(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	ensure_dir_exists(instname, &shadow_wrapper, NULL TSRMLS_CC);
	/* Check whether dir containing the file is writable */
	shadow_call_replace_name(0, instname, orig_is_writable, INTERNAL_FUNCTION_PARAM_PASSTHRU);
}
/* }}} */

/* {{{ proto array glob(string pattern [, int flags])
   Find pathnames matching a pattern */
static void shadow_glob(INTERNAL_FUNCTION_PARAMETERS)
{
	char *filename = NULL;
	int filename_len;
	zval **name;
	long flags;
	char *instname=NULL, *templname=NULL, *mask=NULL, *path=NULL;
	zval *instdata, *templdata;
	zval **src_entry;
	HashPosition pos;
	HashTable *mergedata;
	void *dummy = (void *)1;
	int instlen, templen;
	long num;
	int skip_template=0;

	if(!SHADOW_ENABLED()) {
		orig_glob(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|l", &filename, &filename_len, &flags) == FAILURE) {
		return;
	}
	if(!shadow_stream_check(filename)) {
		orig_glob(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	mask = strpbrk(filename, "*?[");
	if(!mask) {
		mask = filename+filename_len;
	}
	while(--mask > filename && !IS_SLASH(*mask)); /* look up for slash */
	path = estrndup(filename, mask-filename);
	/* path will be path part up to the directory containing first glob char */

	if(is_instance_only(path TSRMLS_CC)) {
		/* if it's instance-only dir, we won't merge in any case */
		instname = template_to_instance(path, 0 TSRMLS_CC);
		skip_template = 1;
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Glob instance only: %s (%s)\n", path, instname);
	} else {
		instname = template_to_instance(path, OPT_CHECK_EXISTS|OPT_RETURN_INSTANCE TSRMLS_CC);
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Glob: %s => %s (%s)\n", filename, path, instname);
		if(!instname) {
			/* we don't have instance dir, don't bother with merging */
			if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Globbing template: %s\n", filename);
			orig_glob(INTERNAL_FUNCTION_PARAM_PASSTHRU);
			efree(path);
			return;
		}
	}
	/* here we have instname */
	instlen = strlen(instname);
#if 0
	if(is_subdir_of(SHADOW_G(template), SHADOW_G(template_len), instname, instlen)) {
		/* We can get template dir here, if instance dir does not exist, we still have only one directory then */
		instname = erealloc(instname, instlen+strlen(mask)+1);
		strcat(instname, mask);
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Globbing template(2): %s\n", instname);
		shadow_call_replace_name(0, instname, orig_glob, INTERNAL_FUNCTION_PARAM_PASSTHRU);
		efree(path);
		return;
	}
#endif
	/* We got instance dir, does template dir exist too? */
	if(!skip_template) {
		templname = instance_to_template(instname, instlen TSRMLS_CC);
		if(!templname) {
			skip_template = 1;
		}
	}
	instname = erealloc(instname, instlen+strlen(mask)+1);
	strcat(instname, mask);
#if 0
/*
 * Remove existance check for template - since we'd need to translate names here anyway, we better fall though and have glob return empty
 */
	if(VCWD_ACCESS(templname, F_OK) != 0) {
		efree(templname);
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Globbing instance: %s\n", instname);
		shadow_call_replace_name(0, instname, orig_glob, INTERNAL_FUNCTION_PARAM_PASSTHRU);
		efree(path);
		return;
	}
#endif
	/* We have both, so we will have to merge */
	ALLOC_HASHTABLE(mergedata);
	zend_hash_init(mergedata, 10, NULL, NULL, 0);

	if(!skip_template && templname != NULL) {
		templen = strlen(templname);
		templname = erealloc(templname, templen+strlen(mask)+1);
		strcat(templname, mask);
		if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Globbing merge: %s %s\n", instname, templname);

		/* call with template */
		if(shadow_call_replace_name(0, templname, orig_glob, INTERNAL_FUNCTION_PARAM_PASSTHRU) == SUCCESS && Z_TYPE_P(return_value) == IS_ARRAY) {
			/* cut off instname and put path part there */
			zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(return_value), &pos);
			while (zend_hash_get_current_data_ex(Z_ARRVAL_P(return_value), (void **)&src_entry, &pos) == SUCCESS) {
				char *mergepath;
				if(Z_TYPE_PP(src_entry) != IS_STRING) continue; /* weird, glob shouldn't do that to us */
				spprintf(&mergepath, MAXPATHLEN, "%s/%s", path, Z_STRVAL_PP(src_entry)+templen+1);
				zend_hash_add(mergedata, mergepath, strlen(mergepath), &dummy, sizeof(void *), NULL);
				efree(mergepath);
				zend_hash_move_forward_ex(Z_ARRVAL_P(return_value), &pos);
			}
		} else {
			/* ignore problems here - other one may pick it up */
			array_init(return_value);
		}
	} else {
		/* we're skipping the template */
		array_init(return_value);
	}

	/* replace the return value */
	templdata = return_value;
	ALLOC_INIT_ZVAL(instdata);
	return_value = instdata;
	/* call with instance */
	if(shadow_call_replace_name(0, instname, orig_glob, INTERNAL_FUNCTION_PARAM_PASSTHRU) == SUCCESS && Z_TYPE_P(return_value) == IS_ARRAY) {
		/* merge data */
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(return_value), &pos);
		while (zend_hash_get_current_data_ex(Z_ARRVAL_P(return_value), (void **)&src_entry, &pos) == SUCCESS) {
			char *mergepath;
			if(Z_TYPE_PP(src_entry) != IS_STRING) continue; /* weird, glob shouldn't do that to us */
			spprintf(&mergepath, MAXPATHLEN, "%s/%s", path, Z_STRVAL_PP(src_entry)+instlen+1);
			zend_hash_add(mergedata, mergepath, strlen(mergepath), &dummy, sizeof(void *), NULL);
			efree(mergepath);
			zend_hash_move_forward_ex(Z_ARRVAL_P(return_value), &pos);
		}
	}
	return_value = templdata;
	zval_ptr_dtor(&instdata);
	/* convert mergedata to return */
	zend_hash_clean(Z_ARRVAL_P(return_value));
	zend_hash_internal_pointer_reset_ex(mergedata, &pos);
	while(zend_hash_get_current_key_ex(mergedata, &filename, &filename_len, &num, 0, &pos) == HASH_KEY_IS_STRING) {
		add_next_index_stringl(return_value, filename, filename_len, 1);
		zend_hash_move_forward_ex(mergedata, &pos);
	}
	/* cleanup */
	zend_hash_destroy(mergedata);
	efree(mergedata);
	efree(path);
}
/* }}} */

static void shadow_generic_override(INTERNAL_FUNCTION_PARAMETERS)
{
	shadow_function *func = (shadow_function *)EG(current_execute_data)->function_state.function;
	zval *old_name, *new_name;
	zval **name;
	int opts = OPT_CHECK_EXISTS|OPT_RETURN_INSTANCE;
	char *instname;

	if(!SHADOW_ENABLED()) {
		func->orig_handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	name = shadow_get_arg(func->argno TSRMLS_CC);
	if(!name || !*name || Z_TYPE_PP(name) != IS_STRING) {
		func->orig_handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	/* not our path - don't mess with it */
	if(!shadow_stream_check(Z_STRVAL_PP(name))) {
		func->orig_handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	/* try to translate */
	if(func->argtype != 0) {
		/* for write */
		opts = OPT_RETURN_INSTANCE;
	}
	instname = template_to_instance(Z_STRVAL_PP(name), opts TSRMLS_CC);
	if(SHADOW_ENABLED() && SHADOW_G(debug) & SHADOW_DEBUG_OVERRIDE) fprintf(stderr, "Overriding %s: %s (%s)\n", func->original.internal_function.function_name, Z_STRVAL_PP(name), instname);
	/* we didn't find better name, use original */
	if(!instname) {
		func->orig_handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}
	old_name = *name;
	ALLOC_INIT_ZVAL(new_name);
	ZVAL_STRING(new_name, instname, 0);
	*name = new_name;
	func->orig_handler(INTERNAL_FUNCTION_PARAM_PASSTHRU);
	*name = old_name;
	zval_ptr_dtor(&new_name);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
