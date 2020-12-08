<?php
$moduleName				= 'Free Shipping Notification';
$moduleVersion			= '1.0';

// Heading
$_['heading_title']		= 'DigitCart - ' . $moduleName . ' ' . $moduleVersion;

// Text
$_['text_module']		= 'Modules';
$_['text_extension']	= 'Extensions';
$_['text_success']		= 'Success: You have modified ' . $moduleName . ' module!';
$_['text_edit']			= 'Edit ' . $moduleName . ' Module';
$_['text_message']		= 'Add <strong>[amount]</strong> more to get free shipping!';

// Alert
$_['alert_info']		= 'Info';
$_['alert_success']		= 'Success';
$_['alert_warning']		= 'Warning';
$_['alert_danger']		= 'Danger';

// Entry
$_['entry_status']		= 'Status';
$_['entry_popup']		= 'Popup';
$_['entry_message']		= 'Message';
$_['entry_alert']		= 'Alert type';

// Help
$_['help_popup']		= 'Can show the free shipping message in a small popup when the cart total changes.';
$_['help_message']		= 'You can use [amount] variable in your message.<br>HTML allowed.';

// Error
$_['error_shipping']	= 'Warning: Free shipping module is not configured properly. please go to Extensions / Extensions / Shipping / Free Shipping and check it, "Free Shipping Notification" needs this settings to work:
<ul>
	<li>Free shipping module must be enabled.</li>
	<li>Free shipping total must be greater than zero.</li>
	<li>Free shipping Geo Zone must be set to All Zones.</li>
</ul>
';
$_['error_permission']	= 'Warning: You do not have permission to modify ' . $moduleName . ' module!';