<?php
/*
* Copyright (c) 2014-2015 SugarCRM Inc.  This product is licensed by SugarCRM
* pursuant to the terms of the End User License Agreement available at
* http://support.sugarcrm.com/06_Customer_Center/10_Master_Subscription_Agreements/10_Marketo/
*/

global $sugar_version;

$manifest = array(
    'acceptable_sugar_versions' =>
        array(
            'exact_matches' =>
                array(),
            'regex_matches' =>
                array(
                    0 => '7\.(8|9|10)\.[0-9]?\.?[0-9]?'
                ),
        ),
    'acceptable_sugar_flavors' =>
        array(
            'PRO',
            'CORP',
            'ENT',
            'ULT'
        ),
    'readme' => '',
    'key' => '',
    'author' => 'SugarCRM Inc.',
    'description' => 'The Sugar Connector for Marketo takes advantage of the SugarCRM Cloud Connector and Scheduler frameworks to deeply integrate Marketo data directly into SugarCRM.',
    'icon' => '',
    'name' => 'Sugar Connector for Marketo',
    'published_date' => 'Tuesday, 05-Sep-17 09:39:03 EDT',
    'type' => 'module',
    'version' => '3.1.4.2',
    'remove_tables' => 'false',
    'is_uninstallable' => 'true',
);


$coreFiles =
    array(
        'clients/base/api/MarketoApi.php',
        'Ext/EntryPointRegistry/MarketoWebHookConsumer.php',
        'Ext/Language/en_us.marketo.php',
        'Ext/TableDictionary/mkto_queue.php',
        'Ext/TableDictionary/opportunities_contacts_ext.php',
        'include/api/help/connector_marketo_get_lead_help.html',
        'include/api/help/connector_marketo_get_schema_help.html',
        'include/externalAPI/Marketo/classes/AuthenticationHeaderInfo.php',
        'include/externalAPI/Marketo/classes/Campaigns.php',
        'include/externalAPI/Marketo/classes/Leads.php',
        'include/externalAPI/Marketo/classes/MarketoError.php',
        'include/externalAPI/Marketo/classes/MObjects.php',
        'include/externalAPI/Marketo/LogicHookController.php',
        'include/externalAPI/Marketo/MarketoClasses.php',
        'include/externalAPI/Marketo/MarketoFactory.php',
        'include/externalAPI/Marketo/MarketoWebHookConsumer.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerBase.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerUsers.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerContacts.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerLeads.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerOpportunities.php',
        'include/externalAPI/Marketo/JobHandlers/MarketoJobHandlerAccounts.php',
        'include/SugarQueue/jobs/SugarJobGetMarketoActivities.php',
        'include/SugarQueue/jobs/SugarJobUpdateMarketo.php',
        'include/SugarQueue/jobs/SugarJobPurgeOldMarketoJobs.php',
        'metadata/mkto_queueMetaData.php',
        'metadata/opportunities_contacts_extMetaData.php',
        'modules/Accounts/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'modules/ActivityLogs/ActivityLog.php',
        'modules/ActivityLogs/clients/base/api/ActivityLogsRelateApi.php',
        'modules/ActivityLogs/clients/base/filters/basic/basic.php',
        'modules/ActivityLogs/clients/base/filters/default/default.php',
        'modules/ActivityLogs/clients/base/layouts/detail/detail.php',
        'modules/ActivityLogs/clients/base/layouts/edit/edit.php',
        'modules/ActivityLogs/clients/base/layouts/marketo-information/marketo-information.php',
        'modules/ActivityLogs/clients/base/layouts/marketo-reset/marketo-reset.php',
        'modules/ActivityLogs/clients/base/layouts/marketo-schema/marketo-schema.php',
        'modules/ActivityLogs/clients/base/layouts/subpanels/subpanels.php',
        'modules/ActivityLogs/clients/base/menus/header/header.php',
        'modules/ActivityLogs/clients/base/views/list-headerpane/list-headerpane.php',
        'modules/ActivityLogs/clients/base/views/list/list.php',
        'modules/ActivityLogs/clients/base/views/marketo-information/marketo-information.hbs',
        'modules/ActivityLogs/clients/base/views/marketo-information/marketo-information.js',
        'modules/ActivityLogs/clients/base/views/marketo-reset/marketo-reset.hbs',
        'modules/ActivityLogs/clients/base/views/marketo-reset/marketo-reset.js',
        'modules/ActivityLogs/clients/base/views/marketo-reset/marketo-reset.php',
        'modules/ActivityLogs/clients/base/views/marketo-schema/marketo-schema.hbs',
        'modules/ActivityLogs/clients/base/views/marketo-schema/marketo-schema.js',
        'modules/ActivityLogs/clients/base/views/marketo-schema/marketo-schema.php',
        'modules/ActivityLogs/clients/base/views/massupdate/massupdate.php',
        'modules/ActivityLogs/clients/base/views/panel-top/panel-top.php',
        'modules/ActivityLogs/clients/base/views/record/record.php',
        'modules/ActivityLogs/clients/base/views/subpanel-for-leads/subpanel-for-leads.php',
        'modules/ActivityLogs/clients/base/views/subpanel-list/subpanel-list.php',
        'modules/ActivityLogs/clients/mobile/layouts/detail/detail.php',
        'modules/ActivityLogs/clients/mobile/layouts/edit/edit.php',
        'modules/ActivityLogs/clients/mobile/layouts/list/list.php',
        'modules/ActivityLogs/clients/mobile/views/detail/detail.php',
        'modules/ActivityLogs/clients/mobile/views/edit/edit.php',
        'modules/ActivityLogs/clients/mobile/views/list/list.php',
        'modules/ActivityLogs/clients/mobile/views/search/search.php',
        'modules/ActivityLogs/language/en_us.lang.php',
        'modules/ActivityLogs/metadata/dashletviewdefs.php',
        'modules/ActivityLogs/metadata/detailviewdefs.php',
        'modules/ActivityLogs/metadata/editviewdefs.php',
        'modules/ActivityLogs/metadata/listviewdefs.php',
        'modules/ActivityLogs/metadata/metafiles.php',
        'modules/ActivityLogs/metadata/popupdefs.php',
        'modules/ActivityLogs/metadata/quickcreatedefs.php',
        'modules/ActivityLogs/metadata/searchdefs.php',
        'modules/ActivityLogs/metadata/SearchFields.php',
        'modules/ActivityLogs/metadata/studio.php',
        'modules/ActivityLogs/metadata/subpanels/default.php',
        'modules/ActivityLogs/metadata/subpanels/subpanel-for-activity_logs.php',
        'modules/ActivityLogs/views/view.list.php',
        'modules/ActivityLogs/vardefs.php',
        'modules/Administration/Ext/Language/en_us.marketo.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/config.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/language/en_us.lang.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/mapping.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/marketo.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/MarketoHelper.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/language/en_us.lang.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/mapping.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/opportunity.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/vardefs.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/role/language/en_us.lang.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/role/role.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/role/vardefs.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/tpls/source_properties.tpl',
        'modules/Connectors/connectors/sources/ext/soap/marketo/vardefs.php',
        'modules/Connectors/connectors/sources/ext/soap/marketo/views/sourceproperties.php',
        'modules/Contacts/Ext/Dependencies/marketo_read_only.php',
        'modules/Contacts/Ext/Dependencies/mkto_sync.php',
        'modules/Contacts/Ext/Language/en_us.marketo.php',
        'modules/Contacts/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'modules/Contacts/Ext/Vardefs/ActivityLogs.php',
        'modules/Contacts/Ext/Vardefs/MarketoFields.php',
        'modules/Employees/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'modules/Leads/Ext/Dependencies/marketo_read_only.php',
        'modules/Leads/Ext/Dependencies/mkto_sync.php',
        'modules/Leads/Ext/Language/en_us.marketo.php',
        'modules/Leads/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'modules/Leads/Ext/Vardefs/ActivityLogs.php',
        'modules/Leads/Ext/Vardefs/MarketoFields.php',
        'modules/Opportunities/Ext/Dependencies/mkto_sync.php',
        'modules/Opportunities/Ext/Language/en_us.marketo.php',
        'modules/Opportunities/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'modules/Opportunities/Ext/Vardefs/MarketoFields.php',
        'modules/Schedulers/Ext/Language/en_us.marketo.php',
        'modules/Users/Ext/LogicHooks/UpdateMarketoWithChanges.php',
        'themes/default/images/ActivityLogs.gif',
        'themes/RacerX/images/icon_ActivityLogs_32.gif',
    );

$extraFiles =
    array(
        'custom/Extension/application/Ext/Include/marketo.php',
        'custom/Extension/modules/Schedulers/Ext/ScheduledTasks/MarketoSchedulers.php',
        'custom/Extension/modules/Administration/Ext/Administration/Marketo.php',
        'custom/Extension/modules/Contacts/Ext/clients/base/layouts/subpanels/subpanels.php',
        'custom/Extension/modules/Leads/Ext/clients/base/layouts/subpanels/subpanels.php',
    );

$sugar6Files =
    array(
        'custom/modules/Connectors/connectors/sources/ext/soap/marketo/language/keep.txt',
        'custom/modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/language/keep.txt',
        'custom/modules/Connectors/connectors/sources/ext/soap/marketo/role/language/keep.txt',
        'custom/modules/Connectors/views/view.sourceproperties.php',
        'custom/Extension/modules/Schedulers/Ext/ScheduledTasks/MarketoSugar6.php',
        'custom/Extension/modules/Leads/Ext/Language/en_us.MarketoPanel.php',
        'custom/Extension/modules/Contacts/Ext/Language/en_us.MarketoPanel.php',
        'custom/Extension/modules/Opportunities/Ext/Language/en_us.MarketoPanel.php',
        'custom/Extension/modules/Contacts/Ext/Layoutdefs/ActivityLogs.php',
        'custom/Extension/modules/Leads/Ext/Layoutdefs/ActivityLogs.php',
        'custom/include/marketo/MarketoUtils.php',
    );

$installdefs =
    array(
        'pre_uninstall' => array(
            '<basepath>/scripts/pre_uninstall.php',
        ),
        'id' => 'MarketoForSugar7',
        'copy' =>
            array(
                array(
                    'from' => "<basepath>/custom/modules/Connectors/connectors/sources/ext/soap/marketo/language/keep.txt",
                    'to' => "custom/modules/Connectors/connectors/sources/ext/soap/marketo/language/keep.txt",
                ),
                array(
                    'from' => "<basepath>/custom/modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/language/keep.txt",
                    'to' => "custom/modules/Connectors/connectors/sources/ext/soap/marketo/opportunity/language/keep.txt",
                ),
                array(
                    'from' => "<basepath>/custom/modules/Connectors/connectors/sources/ext/soap/marketo/role/language/keep.txt",
                    'to' => "custom/modules/Connectors/connectors/sources/ext/soap/marketo/role/language/keep.txt",
                ),
                array(
                    'from' => "<basepath>/custom/modules/Connectors/connectors/sources/ext/soap/marketo/role/language/keep.txt",
                    'to' => "custom/modules/Connectors/connectors/sources/ext/soap/marketo/role/language/keep.txt",
                ),
                array(
                    'from' => "<basepath>/modules/Connectors/views/view.sourceproperties.php",
                    'to' => "custom/modules/Connectors/views/view.sourceproperties.php",
                ),
            ),
        'beans' =>
            array(
                array(
                    'module' => 'ActivityLogs',
                    'class' => 'ActivityLog',
                    'path' => 'modules/ActivityLogs/ActivityLog.php',
                    'tab' => true,
                ),
            ),
        'custom_fields' =>
            array(
                'Leadsmrkto2_industry_c' =>
                    array(
                        'id' => 'Leadsmrkto2_industry_c',
                        'name' => 'mrkto2_industry_c',
                        'label' => 'LBL_MRKTO2_INDUSTRY',
                        'comments' => null,
                        'help' => null,
                        'module' => 'Leads',
                        'type' => 'enum',
                        'max_size' => '255',
                        'require_option' => '0',
                        'default_value' => null,
                        'date_modified' => '2010-05-04 14:53:03',
                        'deleted' => '0',
                        'audited' => '1',
                        'mass_update' => '0',
                        'duplicate_merge' => '1',
                        'reportable' => '1',
                        'importable' => 'true',
                        'ext1' => 'industry_dom',
                        'ext2' => null,
                        'ext3' => null,
                        'ext4' => null,
                    ),
                'Leadsmrkto2_main_phone_c' =>
                    array(
                        'id' => 'Leadsmrkto2_main_phone_c',
                        'name' => 'mrkto2_main_phone_c',
                        'label' => 'LBL_MRKTO2_MAIN_PHONE',
                        'comments' => null,
                        'help' => null,
                        'module' => 'Leads',
                        'type' => 'phone',
                        'max_size' => '30',
                        'require_option' => '0',
                        'default_value' => null,
                        'date_modified' => '2010-05-04 14:52:57',
                        'deleted' => '0',
                        'audited' => '1',
                        'mass_update' => '0',
                        'duplicate_merge' => '1',
                        'reportable' => '1',
                        'importable' => 'true',
                        'ext1' => null,
                        'ext2' => null,
                        'ext3' => null,
                        'ext4' => null,
                    ),
                'Leadsmrkto2_number_of_employees_c' =>
                    array(
                        'id' => 'Leadsmrkto2_number_of_employees_c',
                        'name' => 'mrkto2_number_of_employees_c',
                        'label' => 'LBL_MRKTO2_NUMBER_OF_EMPLOYEES',
                        'comments' => null,
                        'help' => null,
                        'module' => 'Leads',
                        'type' => 'int',
                        'max_size' => '11',
                        'require_option' => '0',
                        'default_value' => null,
                        'date_modified' => '2010-05-04 14:53:05',
                        'deleted' => '0',
                        'audited' => '1',
                        'mass_update' => '0',
                        'duplicate_merge' => '1',
                        'reportable' => '1',
                        'importable' => 'true',
                        'ext1' => null,
                        'ext2' => null,
                        'ext3' => null,
                        'ext4' => null,
                    ),
                'Leadsmrkto2_sic_code_c' =>
                    array(
                        'id' => 'Leadsmrkto2_sic_code_c',
                        'name' => 'mrkto2_sic_code_c',
                        'label' => 'LBL_MRKTO2_SIC_CODE',
                        'comments' => null,
                        'help' => null,
                        'module' => 'Leads',
                        'type' => 'varchar',
                        'max_size' => '10',
                        'require_option' => '0',
                        'default_value' => null,
                        'date_modified' => '2010-05-04 14:53:09',
                        'deleted' => '0',
                        'audited' => '1',
                        'mass_update' => '0',
                        'duplicate_merge' => '1',
                        'reportable' => '1',
                        'importable' => 'true',
                        'ext1' => null,
                        'ext2' => null,
                        'ext3' => null,
                        'ext4' => null,
                    ),
                'Leadsmrkto2_annualrevenue_c' =>
                    array(
                        'id' => 'Leadsmrkto2_annualrevenue_c',
                        'name' => 'mrkto2_annualrevenue_c',
                        'label' => 'LBL_MRKTO2_ANNUALREVENUE_C',
                        'comments' => null,
                        'help' => null,
                        'module' => 'Leads',
                        'type' => 'currency',
                        'max_size' => '26',
                        'require_option' => '0',
                        'default_value' => null,
                        'date_modified' => '2010-05-04 14:52:58',
                        'deleted' => '0',
                        'audited' => '1',
                        'mass_update' => '0',
                        'duplicate_merge' => '1',
                        'reportable' => '1',
                        'importable' => 'true',
                        'ext1' => null,
                        'ext2' => null,
                        'ext3' => null,
                        'ext4' => null,
                    ),
            )
    );
foreach ($coreFiles as $file) {
    $installdefs['copy'][] =
        array(
            'from' => "<basepath>/$file",
            'to' => $file,
        );
}
foreach ($extraFiles as $file) {
    $installdefs['copy'][] =
        array(
            'from' => "<basepath>/$file",
            'to' => $file,
        );
}

if (version_compare($sugar_version, '7.0.0') < 0) {
    foreach ($coreFiles as $file) {
        $pos = strpos($file, 'Ext');
        if ($pos !== false) {
            $installdefs['copy'][] =
                array(
                    'from' => "<basepath>/$file",
                    'to' => ($pos == 0) ? "custom/Extension/application/$file" : "custom/Extension/$file",
                );
        }
    }

    foreach ($sugar6Files as $file) {
        $installdefs['copy'][] =
            array(
                'from' => "<basepath>/$file",
                'to' => $file,
            );
    }
}
