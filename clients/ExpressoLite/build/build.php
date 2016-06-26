<?php

function command_exist($cmd) {
    $returnVal = shell_exec("which $cmd");
    return (empty($returnVal) ? false : true);
}

if (!command_exist('uglifyjs') || !command_exist('cleancss')) {
    die("\n".
        "UglifyJS and/or CleanCss are not installed\n".
        "NodeJS, UglifyJS and CleanCSS are required to run this build script.\n".
        "Check build/README for more details\n\n");
}

if(!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
    die("\n".
        " Minifier for Expresso Lite\n".
        " Usage:\n".
        "   php build.php source_folder dest_folder version_name\n\n");
}


$src = $argv[1];
$dest = $argv[2];
$versionName = $argv[3];

function Now() {
    $tod = gettimeofday();
    return $tod['sec'] * 1000 + round($tod['usec'] / 1000); // timestamp in milliseconds
}
function EndsWith($str, $what) {
    return substr($str, -strlen($what)) === $what;
}

$totalDirs = 0;
$totalFiles = 0;
$totalMinified = 0;

function ProcessDir($dir) {
    global $src, $dest, $totalDirs, $totalFiles, $totalMinified;

    if(!file_exists($dest.substr($dir, strlen($src)))) {
        echo 'mkdir -> '.$dest.substr($dir, strlen($src))."\n";
        mkdir($dest.substr($dir, strlen($src)));
    }

    $dir .= EndsWith($dir, '/') ? '*' : '/*';
    foreach(glob($dir) as $file) {
        if(is_dir($file)) {
            if(EndsWith($file, '_build')) continue;
            ++$totalDirs;
            ProcessDir($file);
        } else {
            if (EndsWith($file, '.zScript build ip') || EndsWith($file, '.gz') || EndsWith($file, '.bz2')) {
                continue;
            } else if (EndsWith($file, '.js') && !EndsWith($file, '.min.js')) {
                echo 'UglifyJS -> '.$dest.substr($file, strlen($src))."\n";
                system('uglifyjs -o '.$dest.substr($file, strlen($src)).' '.$file);
                ++$totalMinified;
            } else if (EndsWith($file, '.css')) {
                echo 'CleanCSS -> '.$dest.substr($file, strlen($src))."\n";
                system('cleancss --skip-rebase -o '.$dest.substr($file, strlen($src)).' '.$file);
                ++$totalMinified;
            } else {
                copy($file, $dest.substr($file, strlen($src)));
            }
            ++$totalFiles;
        }
    }
}

function ReplaceVersionName($fileName) {
    global $versionName;
    echo "Version name: $versionName\n";
    $str=file_get_contents($fileName);

    $oldValue = "define('PACKAGE_STRING', 'lite_development');";
    $newValue = "define('PACKAGE_STRING', '$versionName');";

    $str=str_replace($oldValue, $newValue,$str);

    file_put_contents($fileName, $str);
}


$t0 = Now();
if(!EndsWith($src, '/')) $src .= '/';
if(!EndsWith($dest, '/')) $dest .= '/';
ProcessDir($src);
ReplaceVersionName($dest . 'version.php');
$t = (Now() - $t0) / 1000;
echo "Total: $totalFiles files ($totalMinified minified) within $totalDirs directories in $t seconds.\n";

