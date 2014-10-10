Port of the Postmark API in Kohana 2.3.4. It might be easily portable to Kohana 3.0.

Based on http://github.com/Znarkus/postmark-php/blob/master/Postmark.php

_________________
Example


$email = new Postmark();
$email->to('EMAIL ADDRESS', 'NAME')
	->subject('SUBJECT OF THE EMAIL')
	->tag('MIGHT BE ANYTHING YOU WANT')
	->messageHtml('A STRING, A VIEW or WHATEVER')
	->send();