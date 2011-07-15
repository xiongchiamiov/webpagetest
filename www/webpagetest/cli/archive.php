<?php
chdir('..');
include 'common.inc';
set_time_limit(0);

$kept = 0;
$archiveCount = 0;
$deleted = 0;

// check the old tests first
$archived = json_decode(gz_file_get_contents("./logs/archived/old.archived"), true);
if( !$archived )
    $archived = array();
CheckOldDir('./results/old', $archived);
gz_file_put_contents("./logs/archived/old.archived", json_encode($archived));

/*
*   Archive any tests that have not already been archived
*   We will also keep track of all of the tests that are 
*   known to have been archived separately so we don't thrash
*/  
$endDate = (int)date('Ymd');
$years = scandir('./results');
foreach( $years as $year )
{
    mkdir('./logs/archived', 0777, true);
    $yearDir = "./results/$year";
    if( is_dir($yearDir) && $year != '.' && $year != '..'  && $year != 'video' )
    {
        if( $year != 'old' )
        {
            $months = scandir($yearDir);
            foreach( $months as $month )
            {
                $monthDir = "$yearDir/$month";
                if( is_dir($monthDir) && $month != '.' && $month != '..' )
                {
                    $days = scandir($monthDir);
                    foreach( $days as $day )
                    {
                        $dayDir = "$monthDir/$day";
                        if( is_dir($dayDir) && $day != '.' && $day != '..' )
                        {
                            $dayString = "20$year$month$day";
                            if( (int)$dayString < $endDate )
                            {
                                $archived = json_decode(gz_file_get_contents("./logs/archived/$dayString.archived"), true);
                                if( !$archived )
                                    $archived = array();
                                CheckDay($dayDir, "$year$month$day", $archived);
                                gz_file_put_contents("./logs/archived/$dayString.archived", json_encode($archived));
                            }
                        }
                    }
                    rmdir($monthDir);
                }
            }
            rmdir($yearDir);
        }
    }
}
echo "\nDone\n\n";

$log = date('n') . "\nArchived: $archiveCount\nDeleted: $deleted\nKept: $kept";
file_put_contents('./cli/archive.log', $log);

/**
* Recursively scan the old directory for tests
* 
* @param mixed $path
*/
function CheckOldDir($path, &$archived)
{
    $oldDirs = scandir($path);
    foreach( $oldDirs as $oldDir )
    {
        if( $oldDir != '.' && $oldDir != '..' )
        {
            // see if it is a test or a higher-level directory
            if( is_file("$path/$oldDir/testinfo.ini") )
                CheckTest("$path/$oldDir", $oldDir, $archived);
            else
                CheckOldDir("$path/$oldDir", $archived);
        }
    }
    rmdir($path);
}

/**
* Recursively check within a given day
* 
* @param mixed $dir
* @param mixed $baseID
* @param mixed $archived
*/
function CheckDay($dir, $baseID, &$archived)
{
    $tests = scandir($dir);
    foreach( $tests as $test )
    {
        if( $test != '.' && $test != '..' )
        {
            // see if it is a test or a higher-level directory
            if( is_file("$dir/$test/testinfo.ini") )
                CheckTest("$dir/$test", "{$baseID}_$test", $archived);
            else
                CheckDay("$dir/$test", "{$baseID}_$test", $archived);
        }
    }
    rmdir($dir);
}

/**
* Check the given log file for all tests that match
* 
* @param mixed $logFile
* @param mixed $match
*/
function CheckTest($testPath, $id, &$archived)
{
    global $archiveCount;
    global $deleted;
    global $kept;
    $delete = false;
    $archived = false;

    if( !$archived[$id] )
    {
        if( ArchiveTest($id) )
        {
            $archived = true;
            $archiveCount++;
            $archived[$id] = 1;
        }
    }

    // Delete tests after 3 days of no access
    $elapsed = TestLastAccessed($id);
    if( $elapsed > 3 )
        $delete = true;

    if( $delete )
    {
        delTree("$testPath/");
        $deleted++;
    }
    else
        $kept++;

    $load = GetLoad();
    echo "\rLoad:$load, Arc:$archiveCount, Del:$deleted, Kept:$kept, Checking:" . str_pad($id,45);
}

/**
* Get the current system load
* 
*/
function GetLoad()
{
    $load = 0;
    
    $loadValues = explode(' ',file_get_contents("/proc/loadavg"));
    if( count($loadValues) )
        $load = (float)$loadValues[0];

    return $load;
}

?>