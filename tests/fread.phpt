--TEST--
Check fread with specific length
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
require_once('setup.inc');
$f = fopen("$template/manifest.php", 'r');
fread($f, 8192);
fread($f, 8192);
$data = fread($f, 8192);
var_dump($data);

?>
--EXPECT--
string(2440) "          'audited' => '1',
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
"


