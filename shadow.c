/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2010 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id: header 297205 2010-03-30 21:09:07Z johannes $ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_shadow.h"
#include "php_streams.h"
#include <fcntl.h>

ZEND_DECLARE_MODULE_GLOBALS(shadow)

PHP_FUNCTION(shadow);
PHP_FUNCTION(shadow_get_config);

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
static char *(*old_resolve_path)(const char *filename, int filename_len TSRMLS_DC);

static char *shadow_resolve_path(const char *filename, int filename_len TSRMLS_DC);
static php_stream *shadow_stream_opener(php_stream_wrapper *wrapper, char *filename, char *mode, 
	int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC);
static int shadow_stat(php_stream_wrapper *wrapper, char *url, int flags, php_stream_statbuf *ssb,
	php_stream_context *context TSRMLS_DC);
static int shadow_unlink(php_stream_wrapper *wrapper, char *url, int options, php_stream_context *context TSRMLS_DC);

static int shadow_rename(php_stream_wrapper *wrapper, char *url_from, char *url_to, int options, php_stream_context *context TSRMLS_DC);
static int shadow_mkdir(php_stream_wrapper *wrapper, char *dir, int mode, int options, php_stream_context *context TSRMLS_DC);
static int shadow_rmdir(php_stream_wrapper *wrapper, char *url, int options, php_stream_context *context TSRMLS_DC);
static php_stream *shadow_dir_opener(php_stream_wrapper *wrapper, char *path, char *mode, int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC);

/* {{{ shadow_functions[]
 *
 * Every user visible function must have an entry in shadow_functions[].
 */
const zend_function_entry shadow_functions[] = {
	PHP_FE(shadow,	NULL)	
	PHP_FE(shadow_get_config,	NULL)	
	{NULL, NULL, NULL}	
};
/* }}} */

/* {{{ shadow_module_entry
 */
zend_module_entry shadow_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"shadow",
	shadow_functions,
	PHP_MINIT(shadow),
	PHP_MSHUTDOWN(shadow),
	PHP_RINIT(shadow),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(shadow),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(shadow),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
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
PHP_INI_END()
/* }}} */

/* {{{ php_shadow_init_globals
 */
static void php_shadow_init_globals(zend_shadow_globals *shadow_globals)
{
	memset(shadow_globals, 0, sizeof(zend_shadow_globals));
}
/* }}} */
#define SHADOW_CONSTANT(C) 		REGISTER_LONG_CONSTANT(#C, C, CONST_CS)

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(shadow)
{
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
		old_resolve_path = zend_resolve_path;
		zend_resolve_path = shadow_resolve_path;
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
}

/* Remove if there's nothing to do at request end */
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
	char *strg;
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
	
	SHADOW_G(template) = old_resolve_path(temp, temp_len);
	if(!SHADOW_G(template)) {
		RETURN_FALSE;
	}
	SHADOW_G(template_len) = strlen(SHADOW_G(template));
	SHADOW_G(instance) = old_resolve_path(inst, inst_len);
	if(!SHADOW_G(instance)) {
		efree(SHADOW_G(template));
		SHADOW_G(template) = NULL;
		RETURN_FALSE;
	}

	SHADOW_G(instance_len) = strlen(SHADOW_G(instance));
	
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
	char *newname = NULL;

	if(SHADOW_G(template) == NULL || SHADOW_G(instance) == NULL || SHADOW_G(instance_only) == NULL) {
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
		return instance_only_subdir(filename+SHADOW_G(template_len)+1 TSRMLS_CC);
	}
	
	if(SHADOW_G(instance_len)+1 <= fnamelen && 
		memcmp(SHADOW_G(instance), filename, SHADOW_G(instance_len)) == 0 &&
		IS_SLASH(filename[SHADOW_G(instance_len)])) {
		return instance_only_subdir(filename+SHADOW_G(instance_len)+1 TSRMLS_CC);	
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

/* 
Returns new instance path or NULL if template path is OK
filename is relative to template root
*/
static char *template_to_instance(const char *filename, int check_exists TSRMLS_DC)
{
	char *realpath = NULL;
	int fnamelen = strlen(filename);
	char *newname = NULL;

	if(SHADOW_G(template) == NULL || SHADOW_G(instance) == NULL) {
		return NULL;
	}

	if(!IS_ABSOLUTE_PATH(filename, fnamelen)) {
		realpath = expand_filepath(filename, NULL TSRMLS_CC);
		if(!realpath) {
			return NULL;
		}
		if(SHADOW_G(debug) & SHADOW_DEBUG_FULLPATH)	fprintf(stderr, "Full path: %s\n", realpath);
		filename = realpath;
		fnamelen = strlen(realpath);
	}
	
	if(is_subdir_of(SHADOW_G(template), SHADOW_G(template_len), filename, fnamelen)) {
		/* starts with template - rewrite to instance */
		spprintf(&newname, MAXPATHLEN, "%s/%s", SHADOW_G(instance), filename+SHADOW_G(template_len)+1);
		if(check_exists && !instance_only_subdir(filename+SHADOW_G(template_len)+1 TSRMLS_CC)) {
			if(VCWD_ACCESS(newname, F_OK) != 0) {
				/* file does not exist */
				efree(newname);
				return NULL;
			}
		}
	} else if(is_subdir_of(SHADOW_G(instance), SHADOW_G(instance_len), filename, fnamelen)) {
		if(check_exists) {
			/* starts with instance, may want to check template too */
			if(!instance_only_subdir(filename+SHADOW_G(instance_len)+1 TSRMLS_CC) && VCWD_ACCESS(filename, F_OK) != 0) {
				/* does not exist, go to template */
				spprintf(&newname, MAXPATHLEN, "%s/%s", SHADOW_G(template), filename+SHADOW_G(instance_len)+1);
			} else {
				/* TODO: use realpath here too? */
				return NULL;
			}
		} else {
			/* use already resolved name if we are writing - this way we can use it for recursive mkdir */
			newname = realpath?realpath:estrndup(filename, fnamelen);
			realpath = NULL;
		}
	}
	
	if(realpath) {
		efree(realpath);
	}
	if(SHADOW_G(debug) & SHADOW_DEBUG_PATHCHECK)	fprintf(stderr, "Path check: %s => %s\n", filename, newname);
	return newname;
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
	}
	pathname[dir_len] = '/'; /* restore full path */
}

static char *shadow_resolve_path(const char *filename, int filename_len TSRMLS_DC)
{
	char *result = old_resolve_path(filename, filename_len TSRMLS_CC);
	if(SHADOW_G(debug) & SHADOW_DEBUG_RESOLVE) fprintf(stderr, "Resolve: %s -> %s\n", filename, result);	
	return result;
}

static php_stream *shadow_stream_opener(php_stream_wrapper *wrapper, char *filename, char *mode, 
	int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC)
{
	int flags; 
	php_stream *res;
	if(SHADOW_G(debug) & SHADOW_DEBUG_OPEN)  fprintf(stderr, "Opening: %s %s\n", filename, mode);
	if(php_stream_parse_fopen_modes(mode, &flags) == SUCCESS) {
		if(flags & O_WRONLY) {
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
			char *instname = template_to_instance(filename, 1 TSRMLS_CC);
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

static int shadow_stat(php_stream_wrapper *wrapper, char *url, int flags, php_stream_statbuf *ssb, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, 1 TSRMLS_CC);
	if(SHADOW_G(debug) & SHADOW_DEBUG_STAT)  fprintf(stderr, "Stat: %s (%s) %d\n", url, instname, flags);	
	if(instname) {
		int res = plain_ops->url_stat(wrapper, instname, flags, ssb, context TSRMLS_CC);
		efree(instname);
		return res;
	}
	return plain_ops->url_stat(wrapper, url, flags, ssb, context TSRMLS_CC);
}

static int shadow_unlink(php_stream_wrapper *wrapper, char *url, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, 0 TSRMLS_CC);
	int res;
	if(SHADOW_G(debug) & SHADOW_DEBUG_UNLINK) fprintf(stderr, "Unlink: %s (%s) %d\n", url, instname, options);	
	if(instname) {
		url = instname;
	}
	res = plain_ops->unlink(wrapper, url, options, context TSRMLS_CC);
	if(instname) {
		efree(instname);
	}
	return res;
}

static int shadow_rename(php_stream_wrapper *wrapper, char *url_from, char *url_to, int options, php_stream_context *context TSRMLS_DC)
{
	char *fromname = template_to_instance(url_from, 1 TSRMLS_CC);
	char *toname = template_to_instance(url_to, 0 TSRMLS_CC);
	int res;
	if(SHADOW_G(debug) & SHADOW_DEBUG_RENAME) fprintf(stderr, "Rename: %s(%s) -> %s(%s) %d\n", url_from, fromname, url_to, toname, options);	
	if(fromname) {
		url_from = fromname;
	}
	if(toname) {
		url_to = toname;
	}
	res = plain_ops->rename(wrapper, url_from, url_to, options, context TSRMLS_CC);
	if(!res && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
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

static int shadow_mkdir(php_stream_wrapper *wrapper, char *dir, int mode, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(dir, 0 TSRMLS_CC);
	int res;
	if(instname) {
		dir = instname;
		/* always use recursive to create unexisting paths */
		options |= PHP_STREAM_MKDIR_RECURSIVE;
	}
	if(SHADOW_G(debug) & SHADOW_DEBUG_MKDIR)  fprintf(stderr, "Mkdir: %s (%s) %d %d\n", dir, instname, mode, options);	
	res = plain_ops->stream_mkdir(wrapper, dir, mode, options, context TSRMLS_CC);
	if(!res && (SHADOW_G(debug) & SHADOW_DEBUG_FAIL)) {
		fprintf(stderr, "Mkdir FAIL: %s %d %d [%d]\n", dir, mode, options, errno);	
	}
	if(instname) {
		efree(instname);
	}
	return res;
}

static int shadow_rmdir(php_stream_wrapper *wrapper, char *url, int options, php_stream_context *context TSRMLS_DC)
{
	char *instname = template_to_instance(url, 0 TSRMLS_CC);
	int res;
	if(SHADOW_G(debug) & SHADOW_DEBUG_MKDIR) fprintf(stderr, "Rmdir: %s (%s) %d\n", url, instname, options);	
	if(instname) {
		url = instname;
	}
	res = plain_ops->stream_rmdir(wrapper, url, options, context TSRMLS_CC);
	if(instname) {
		efree(instname);
	}
	return res;
}

static php_stream *shadow_dir_opener(php_stream_wrapper *wrapper, char *path, char *mode, int options, char **opened_path, php_stream_context *context STREAMS_DC TSRMLS_DC)
{
	char *instname;
	php_stream *tempdir, *instdir, *mergestream;
	HashTable *mergedata;
	php_stream_dirent entry;
	void *dummy = (void *)1;
	
	if(options & STREAM_USE_GLOB_DIR_OPEN) {
		/* not dealing with globs yet */
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening glob: %s\n", path);	
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	} 
	if(is_instance_only(path TSRMLS_CC)) {
		/* if it's instance-only dir, we won't merge in any case */
		instname = template_to_instance(path, 0 TSRMLS_CC);
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening instance only: %s\n", path);	
		return plain_ops->dir_opener(wrapper, instname, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}
	instname = template_to_instance(path, 1 TSRMLS_CC);
	if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opendir: %s (%s)\n", path, instname);	
	if(!instname) {
		/* we don't have instance dir, don't bother with merging */
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening template: %s\n", path);	
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}
	
	instdir = plain_ops->dir_opener(wrapper, instname, mode, options&(~REPORT_ERRORS), opened_path, context STREAMS_CC TSRMLS_CC);
	if(!instdir) {
		/* instance dir failed, return just template one */
		if(SHADOW_G(debug) & SHADOW_DEBUG_OPENDIR) fprintf(stderr, "Opening template w/o instance: %s\n", path);	
		return plain_ops->dir_opener(wrapper, path, mode, options, opened_path, context STREAMS_CC TSRMLS_CC);
	}
	tempdir = plain_ops->dir_opener(wrapper, path, mode, options&(~REPORT_ERRORS), opened_path, context STREAMS_CC TSRMLS_CC);
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
	php_stream_free(instdir, 0);
	php_stream_free(tempdir, 0);
	
	/* give back the data as stream */
	mergestream = php_stream_alloc(&shadow_dirstream_ops, mergedata, 0, mode);
	if(!mergestream) {
		zend_hash_destroy(mergedata);
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
	return 0;
}

static int shadow_dirstream_rewind(php_stream *stream, off_t offset, int whence, off_t *newoffs TSRMLS_DC)
{
	zend_hash_internal_pointer_reset((HashTable *)stream->abstract);
	return 0;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
