# Find-Me-App
A Droidscript application that makes tracking of people possible.Backend built using PHP

The Server Side Directory should be hosted and requires atleast PHP >= 7.4

NOTE:
-
(If you're hosting the server side,you will need to modify the HTTPHOST constant in 'Find Me App.js' (http://localhost:8000) is what's present,so running it locally won't pose any issue)
Also,
The app is built with droidscript.(A JavaScript Framework)

The apk folder consists of the the .apk file which can be directly installed on any device and also the .spk file which can be imported into a Droidscript IDE and modified.
The keystore file in that directory what was used in signing the already present apk.

INNER WORKINGS OF THE APP:
-
Registration and Login feature (retrieve lost account not implement yet but it can.)

Remember me feature(to allow seamless authentication on successive restart of application)

Once logged in,you can add other accounts a either 'guardians' or 'users'. 'users' are other accounts you want to keep track of while 'guardians' are account that ought to keep track of you(your own account).

Ofcourse, that's not all.They're two different services that this app offers. 

The first one (SOS) gets your current location and sends it to your guardians.

The second one allows you to receive SOS updates about the 'users' account,

Logically,you need to activate both services to make this app work efficiently.

You can add 'users'/'guardians' and also remove them.You can also view the live map update of a 'user' you're tracking.

Note that,if a 'user' doesn't activate the SOS service from their own end,the guardians won't receive any update or be able to access the live map location of the user.
