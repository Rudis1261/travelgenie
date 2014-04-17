Travel Genie
============

Overview
---------

Clickatell Hack-Athon winning application.

Objective
---------

We were given 24 hours to write an application to showcase what can be done with our services.


What did we do?
---------------

My team wanted to create a SMS based version of Google Places / Google Maps. So we signed up for a Clickatell Account. Added an HTTP API. Received our Two-Way number as part of the competition rules and set off to work. We had allot of fun building this mini application, maybe this will show you what can be done with Clickatell and plain Jane SMS.

**The flow of the application is as follows**

- SMS from phone (suburb, place of interest) => Clicaktell Two Way number (27816220028)
- Clickatell forwards this information to our Callback URL specified in our Clickatell account.
- The script receives the information via a GET request
- We process this information somewhat, to see what type of request it is and whether it's valid.
- We proceed to get the GPS Coordinates (Longitude and Latitude) with a Google Maps query.
- Then do a subsequent Google Places lookup with the Coordinates we receive from the Google Maps API with the terms we received.
- We then process all the responses we receive from Google Places and give a number of these back to the user, by sending them an SMS with the details of the various attractions.
- The person can then reply with the number of the entry of interest, and where they would like directions from.
- We process this response once more and provides the user with the directions from where they specified to the attraction.

Requirements
------------

For you to try this out yourself you will need a couple of things.
- Clickatell Account with some SMS credits. "Central API account"

https://www.clickatell.com/register/?productid=1

- Clickatell Two-Way number to receive and forward the SMS

https://www.clickatell.com/pricing-and-coverage/advanced-pricing-advanced-coverage/

- Google Places Token

https://developers.google.com/places/

https://code.google.com/apis/console/?noredirect


Comments and questions
----------------------


Want to know more, and don't quite understand what the hell we did. Feel free to contact me iam@thatguy.co.za


[Brocure PDF](https://github.com/drpain/travelgenie/raw/master/Brocure.pdf)