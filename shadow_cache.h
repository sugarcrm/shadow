/*
 * Copyright (C) SugarCRM Inc. All rights reserved.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License. 
 */

void shadow_cache_set_id(const char *template, const char *instance TSRMLS_DC);
int shadow_cache_get(const char *name, char **entry TSRMLS_DC);
void shadow_cache_put(const char *name, const char *entry TSRMLS_DC);
void shadow_cache_remove(const char *name TSRMLS_DC);
void shadow_cache_clean(TSRMLS_D);
