<?php

class Clickatell
{
    # Set the required variables
    public $user;
    public $password;
    public $api_id ;
    public $mo;
    public $endpoint    = "http://api.clickatell.com";

    # Construct with the details
    public function __construct($user, $password, $api_id, $mo=false)
    {
        # Set the values
        $this->user         = $user;
        $this->password     = $password;
        $this->api_id       = $api_id;

        if ($mo !== false)
        {
            $this->mo = "&from=" . $mo . "&mo=1";
        }
    }

    # Function to send the SMS
    public function SMS($number, $message)
    {
        # We will fill this and use it later on
        $to         = array();
        $message    = rawurlencode($message);

        # Array
        if (is_array($number))
        {
            foreach($number as $each)
            {
                $to[] = trim($each);
            }
        }

        # Comma separated
        elseif (strstr($number, ','))
        {
            $numbers = explode(',', $number);
            foreach($numbers as $each)
            {
                $to[] = trim($each);
            }
        }

        # Single number
        else
        {
            $to[] = $number;
        }

        # Ok so let's string it back together
        $to = implode(',', $to);

        # Define the URL
        $url = "$this->endpoint/http/sendmsg?user=" . $this->user . "&password=" . $this->password . "&api_id=" . $this->api_id . "&concat=6&to=" . $to . "&text=" . $message . $this->mo;

        # Do it
        $ret = file($url);

        # Check if the send was successfully
        $send = explode(":",$ret[0]);

        # Did the SMS succeed?
        if ($send[0] == "ID")
        {
            # Return the
            return trim($send[1]);
        }

        return false;
    }
}