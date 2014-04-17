<?php

# Define the date
date_default_timezone_set('Africa/Johannesburg');



# APPLICATION VARIABLES
$sms_user       = "";       # Clickatell SMS Username
$sms_password   = "";       # Clickatell SMS Password
$sms_api_id     = 0;        # Clickatell SMS API ID ("HTTP API")
$sms_mo_number  = 0;        # Clickatell MO ("Two-Way number"), in order to receive the SMS and process the response
$apiKey         = "";       # Goolge Places API token
$radius         = 5000;     # Distance in radius which Google Places needs to search. In this case 5km
# APPLICATION VARIABLES



# Require the class
require('class.sms.php');

# Log the requests
$requests = "";
foreach ((array)$_REQUEST as $index=>$r)
{
    $requests .= $index . " => " . $r . "\n\n";
}

# Write the requests to file
file_put_contents("requests.txt", $requests);

# Some things should be considered as types and not names
$placeTypes = array("accounting", "airport", "amusement_park", "aquarium", "art_gallery", "atm", "bakery", "bank", "bar", "beauty_salon", "bicycle_store", "book_store", "bowling_alley", "bus_station", "cafe", "campground", "car_dealer", "car_rental", "car_repair", "car_wash", "casino", "cemetery", "church", "city_hall", "clothing_store", "convenience_store", "courthouse", "dentist", "department_store", "doctor", "electrician", "electronics_store", "embassy", "establishment", "finance", "fire_station", "florist", "funeral_home", "furniture_store", "gas_station", "general_contractor", "grocery_or_supermarket", "gym", "hair_care", "hardware_store", "health", "hindu_temple", "home_goods_store", "insurance_agency", "jewelry_store", "laundry", "lawyer", "library", "liquor_store", "local_government_office", "locksmith", "lodging", "meal_delivery", "meal_takeaway", "mosque", "movie_rental", "movie_theater", "moving_company", "museum", "night_club", "painter", "park", "parking", "pet_store", "pharmacy", "physiotherapist", "place_of_worship", "plumber", "police", "post_office", "real_estate_agency", "restaurant", "roofing_contractor", "rv_park", "school", "shoe_store", "shopping_mall", "spa", "stadium", "storage", "store", "subway_station", "synagogue", "taxi_stand", "train_station", "travel_agency", "university", "veterinary_care", "zoo");
$placeTypes[] = "accommodation";
$placeTypes[] = "accomodation";
$placeTypes[] = "acommodation";
$placeTypes[] = "acomodation";
$placeTypes[] = "dining";
$placeTypes[] = "entertainment";


# Get the content from the SMS
$from = (isset($_REQUEST['from'])) ? $_REQUEST['from'] : false;
$text = (isset($_REQUEST['text'])) ? $_REQUEST['text'] : false;


# Send a SMS to create
function sendSMS($to, $message)
{
    # I know globals right, but in this context it works. You would ideally place this in a class and inject dependencies
    global $sms_user, $sms_password, $sms_api_id, $sms_mo_number;
    $message = urldecode($message);

    if ($to != 'none')
    {
        if (!class_exists($Clickatell))
        {
            # Fire up a class instance
            $Clickatell = new Clickatell(
                $sms_user,
                $sms_password,
                $sms_api_id,
                $sms_mo_number
            );
        }

        # Send it!
        $Clickatell->SMS( (string)$to, $message);
    }

    echo $message;
}

# Check if the text is set
if ($text !== false)
{
    # Explode by comma
    if (strstr($text, ','))
    {
        $explode            = explode(',', $text);
        $searchLocation     = urlencode(trim($explode[0]));
        $term               = trim($explode[1]);

        # A little Christmas cracker ;-)
        if (strtolower($searchLocation) == 'bredasdorp')
        {
            sendSMS($from, "Dude, what were you thinking?!?!");
            exit();
        }

        # Should parameter 1 be a string continue as normal
        if (!is_numeric($searchLocation))
        {
            # Check on the term to see whether it is a type or a name search
            $searchValue = "&name=" . urlencode($term);
        }

        # otherwise take the numeric as is
        else
        {
            $searchValue = urlencode($term);
        }

    }

    # Failure, send format SMS
    else
    {
        sendSMS($from, "ERROR: Invalid Format. Please use the following format (Location, Name):
bellville, pizza
paarl, hospital");
        exit();
    }
}


# Secondary request
if (is_numeric($searchLocation))
{
    //echo "Search by value" . $searchValue;
    $locationUrl    = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $searchValue . "&sensor=true&region=za";
    $getLocation    = json_decode(file_get_contents($locationUrl), true);
    $primaryQuery   = false;
}

# Normal search
else
{
    //echo "Seach by location" . $searchLocation;
    $locationUrl    = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $searchLocation . "&sensor=true&region=za";
    $getLocation    = json_decode(file_get_contents($locationUrl), true);
    $primaryQuery   = true;
}

// print_r($getLocation);
// echo "$locationUrl<br /><br />";

# Failure, Google response not recognized
if (empty($getLocation['results']))
{
    sendSMS($from, "ERROR: Location Lookup Failed. Strange, that should have worked.");
    exit();
}

$found      = 0;
$foundIn    = false;
$userInfo   = "requests/" . $from . ".txt";
$dbInfo     = "requests/all.txt";
$results    = $getLocation['results'];

# Loop through the results and ensure that it contains something for SA
foreach ($results as $resId=>$result)
{
    foreach($result['address_components'] as $pid=>$possible)
    {
        if ($possible['types'][0] == "country")
        {
            if ($result['address_components'][$pid]['short_name'] == "ZA")
            {
                $found +=1;
                $foundIn = $resId;
                break;
            }
        }
    }
}

# Results were found
if ($found == 0)
{
    sendSMS($from, "ERROR: We couldn't find your specified location");
    exit();
}

# Get the coordinates
$lat = $results[$foundIn]['geometry']['location']['lat'];
$lng = $results[$foundIn]['geometry']['location']['lng'];

# Are they both set?
if ((empty($lng)) OR (empty($lat)))
{
    # First query
    if ($primaryQuery == true)
    {
        sendSMS($from, "ERROR: Location Coordinates Failed. Strange, that should have worked.");
        exit();
    }

    # Subsequent directions query
    else
    {
        sendSMS($from, "ERROR: Source address '" . $searchValue . "' appears to be invalid.");
        exit();
    }
}

# Check whether its the primary or secondary query
if ($primaryQuery == false)
{
    # User does not have any previous searches
    if (!file_exists($userInfo))
    {
        sendSMS($from, "ERROR: No previous query found. Please use the following format to search (Location, Name):
bellville, pizza
paarl, hospital");
        exit();
    }

    # File exists
    else
    {
        # Get data from the file
        $getPreviousInfo = json_decode(file_get_contents($userInfo), true);

        if ($getPreviousInfo !== false)
        {
            # Get the coords from the last call from the user
            $getDestinationInfo = end($getPreviousInfo);
            $getDestinationInfo = $getDestinationInfo[$searchLocation-1];
            //print_r($getDestinationInfo);

            # Get the lat and lng details from the stored data
            $destLat            = $getDestinationInfo['geometry']['location']['lat'];
            $destLng            = $getDestinationInfo['geometry']['location']['lng'];
            $directionsUrl      = "http://maps.googleapis.com/maps/api/directions/json?mode=driving&origin=" . $lat . "," . $lng . "&destination=" . $destLat . "," . $destLng . "&sensor=false";
            $getDirections      = json_decode(file_get_contents($directionsUrl), true);
            //print_r($getPlaces);

            # TODO, check for positive result on directions
            if (empty($getDirections['routes']))
            {
                sendSMS($from, "ERROR: No route found");
                exit();
            }

            # Mine the data from the feed
            $route              = current($getDirections['routes']);
            $legs               = current($route['legs']);
            $start              = ucfirst($searchValue);
            $dest               = $getDestinationInfo['name'];
            $dist               = str_replace(" ", "", $legs['distance']['text']);
            $dur                = str_replace(" ", "", $legs['duration']['text']);

            $dirMsg             = "From: ". $start ."
To: ". $dest . "
Distance: " . $dist . "
ETA: " . $dur . "\n\n";

            $steps              = $legs['steps'];
            $counter            = 1;
            $directions         = array();
            $arrayKey           = 0;
            $messages           = array();

            foreach ($steps as $step)
            {
                # Get the distance and eta
                $stepDist       = $step['distance']['text'];
                $stepDist       = str_replace(" ", "", $stepDist);
                $stepInst       = $step['html_instructions'];

                # Replace the destination location with the new log
                $stepInst       = str_replace('<div style="font-size:0.9em">', "\n", $stepInst);
                $stepInst       = strip_tags($stepInst);

                $stepNext       = $counter++.". ".$stepInst." (" . $stepDist.")\n";
                $stepLen        = strlen($stepNext);
                $msgLen         = strlen($dirMsg) + (int)$stepLen;

                if ($msgLen < 700)
                {
                    $dirMsg .= $stepNext;
                }

                else
                {
                    $arrayKey++;
                    $dirMsg = $stepNext;
                }

                $messages[$arrayKey]    = $dirMsg;
                $directions[]           = array('distance' => $stepDist, 'instruction' => $stepInst);
            }

            $messageCounter = 1;

            # Send longer messages, most of the times this is for directions response
            foreach ($messages as $message)
            {
                $messagesCounter++;
                $messageCount = "Message ".$messagesCounter." of ". count($messages) ."\n";

                if (count($messages) > 1)
                {
                    $message = $messageCount.$message;
                }

                sendSMS($from, $message);
            }
            exit();

            # Debug information
            // echo "Content Lenght: " . strlen($dirMsg) . "\n";
            // foreach(range(2, 6) as $s)
            // {
            //     echo $s . " part sms can contain max of " . ($s * 153) . " characters\n";
            // }
            // echo "\n\n";
        }
    }
    die();
}

$placeUrl       = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $lat . "," . $lng . "&radius=" . $radius . $searchValue . "&sensor=false&key=" . $apiKey;
//echo $placeUrl . "<br />";

$getPlaces      = json_decode(file_get_contents($placeUrl), true);
//print_r($getPlaces);

 # we need to cater for the Google api n+1 problem
if ((isset($getPlaces["status"])) AND ($getPlaces["status"] == "OVER_QUERY_LIMIT"))
{
    sendSMS($from, "ERROR: Account query limit reached!");
    exit();
}

if (empty($getPlaces['results']))
{
    sendSMS($from, "ERROR: No results found");
    exit();
}

$places     = array();
$counter    = 0;
$saveInfo   = array();

foreach ($getPlaces['results'] as $place)
{
    # Skip further iterations
    if ($counter == 5)
    {
        break;
    }

    # We will fill this array to display the information to the user
    $placeInfo          = array();
    $placeInfo['name']  = $place['name'];
    $placeInfo['addr']  = $place['vicinity'];

    # the rating may not exist, so check first
    if (!empty($place['rating']))
    {
        $placeInfo['rating'] = $place['rating'];
    }

    # Get the phone number in a separate call to the google api
    $placeDetailUrl     = "https://maps.googleapis.com/maps/api/place/details/json?reference=" . $place['reference'] . "&sensor=false&key=".$apiKey;
    $getPlaceDetail     = json_decode(file_get_contents($placeDetailUrl), true);
    $getTel             = "";
    //echo "$placeDetailUrl<br />";

    # If the number is not empty
    if (!empty($getPlaceDetail['result']['formatted_phone_number']))
    {
        # Add it to the tel index
        $placeInfo['tel']   = $getPlaceDetail['result']['formatted_phone_number'];
        $getTel             = $placeInfo['tel'];
    }

    # Add the array to the output to be displayed
    $places[]       = $placeInfo;
    $counter        += 1;

    $logInfo  = array(
        "timestamp"     => time(),
        "search"        => $searchValue,
        "location"      => $searchLocation,
        "name"          => $place['name'],
        "geometry"      => $place['geometry'],
        "reference"     => $place['reference'],
        "rating"        => $place['rating'],
        "tel"           => $getTel,
    );

    $saveInfo[]     = $logInfo;
}


# User file
# The file doesn't exist
if (!file_exists($userInfo))
{
    $content = array(0 => $saveInfo);

    # write it
    file_put_contents($userInfo, json_encode($content));
}

# Exists, read and append
else
{
    # Read it, append it, save it
    $content                = json_decode(file_get_contents($userInfo), true);
    $lastKey                = end(array_keys($content));
    $content[$lastKey+1]    = $saveInfo;
    file_put_contents($userInfo, json_encode($content));
}


# Global info array
# The file doesn't exist
if (!file_exists($dbInfo))
{
    $content = array(0 => $saveInfo);

    # write it
    file_put_contents($dbInfo, json_encode($content));
}

# Exists, read and append
else
{
    # Read it, append it, save it
    $content                = json_decode(file_get_contents($dbInfo), true);
    $lastKey                = end(array_keys($content));
    $content[$lastKey+1]    = $saveInfo;
    file_put_contents($dbInfo, json_encode($content));
}


# We will fill this up a bit later
$result = "";

# Loop through the places
foreach(range(0, 5) as $i)
{
    # Should the index be set, continue
    if (isset($places[$i]))
    {
        # Add the option number
        $result .= ($i + 1) . ". ";

        # Loop through the places indexes and get the per line information
        foreach($places[$i] as $key=>$value)
        {
            # Name
            if ($key == 'name')
            {
                # Add it to the results
                $result .= $value . "\n";
            }

            # Strip the whitespace for a telephone number
            elseif ($key == 'tel')
            {
                # Add it to the results
                $result .=  strtoupper($key) . ": " .  str_replace(' ', '', $value) . "\n";
            }

            # otherwise, business as usual
            else
            {
                # Add it to the results
                $result .=  strtoupper($key) . ": " .  $value . "\n";
            }
        }

        # Add a new line to the results to separate the entries
        $result .= "\n";
    }
}

$result .= "For directions, reply (Num, From Suburb) i.e:
1, Bellville";

# Debug information
echo "Content Lenght: " . strlen($result) . "\n";
foreach(range(2, 6) as $s)
{
    echo $s . " part sms can contain max of " . ($s * 153) . " characters\n";
}
echo "\n\n";

# All good, send the SMS with the details to the user
sendSMS($from, $result);