<?php
# Define the date
date_default_timezone_set('Africa/Johannesburg');

$discardBefore  = strtotime("-60 minute");
$path           = 'requests/';

if ($handle = opendir($path))
{
    # Loop through the read dir
    while (($file = readdir($handle)) !== false)
    {
        # Break the file up
        $fileInfo = pathinfo($path.$file);

        # We will only be looking at the files with the txt extension
        if($fileInfo['extension'] == 'txt')
        {
            # Attempt to read the file
            $readFile = json_decode(file_get_contents($path.$file), false);

            # Ensure that we had a positive result
            if ($readFile !== false)
            {
                # Loop through the entries
                foreach((array)$readFile as $arrayIndex=>$arrays)
                {
                    # loop through the individual entries
                    foreach((array)$arrays as $entryIndex=>$entry)
                    {
                        echo (int)$entry->timestamp . "-" . $discardBefore . "\n\n";
                    }
                }
            }
        }
    }
}