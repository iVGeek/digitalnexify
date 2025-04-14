* INSTALLATION INSTRUCTIONS PESAPAL WHMCS V1.4.4*

The PesaPal gateway module requires the use of modified templates that are provided with this distribution. 

Please follow the instructions below to setup the PesaPal gateway module. 



The templates provided are based on WHMCS 7.1.X ++ and the default theme.

* 

Upload pesapal.php AND the pesapal folder to your whmcs's modules/gateways folder.

* 

Upload the callback/pesapal.php file to your whmcs's modules/gateways/callback folder.

* 

<ignore>Upload templates/pesapal_callback.tpl and templates/pesapal_iframe.tpl to your active theme template directory. This usually under : System settings>General Settings> System Theme(Scroll or Ctrl+F theme)</ignore>

These templates are based off of the default template in 8.6.1

Enable the PesaPal module in the WHMCS admin area by going to Addons-> Apps & Integrations->Browse->Payments(On the left)->View All->Under Additional Apps click on pesapal to activate then manage. Paste in your administrator username, Consumer Key and Consumer Secret.

To get your consumer Key and Consumer Secret, Open a business account on www.pesapal.com or Pesapal test credentials. 

If you opened an account on www.pesapal.com(live account), the key and secret have been sent to the email address you registered with.

Find test credentials here, https://developer.pesapal.com/api3-demo-keys.txt

* 


Ensure when you are done testing the plugin using the demo/sandbox account you switch to the live API.

*

Save configurations.



NB:// Do not change display name in the configuration
 

If you have any questions, recommendations or need installation assistance, please send us an email to developer@pesapal.com
    
