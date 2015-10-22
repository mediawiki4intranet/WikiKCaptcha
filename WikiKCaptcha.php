<?php

/**
 * KCAPTCHA plug-in for MediaWiki ConfirmEdit extension
 *
 * Uses FancyCaptcha localisation messages
 * Uses KCAPTCHA by Kruglov Sergei, 2006 (www.captcha.ru, www.kruglov.ru)
 *
 * License: GNU GPLv3 or later
 * Copyright (c) Vitaliy Filippov, 2013+
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

/**
 * USAGE: Put the following lines into your LocalSettings.php:
 *
 * require_once "extensions/ConfirmEdit/ConfirmEdit.php";
 * require_once "extensions/WikiKCaptcha/WikiKCaptcha.php";
 * $wgCaptchaClass = 'WikiKCaptcha';
 */

// You can specify a writable file here to log captcha passing attempts
// Log will be in CSV format
$wgKCaptchaLogFile = false;

// If true, CAPTCHA answers will be hex-encoded before submitting using JS on HTML forms
$wgKCaptchaHex = true;

if (file_exists(dirname(__DIR__) . '/ConfirmEdit/FancyCaptcha.i18n.php')) // 1.19-1.22
	$wgExtensionMessagesFiles['FancyCaptcha'] = dirname(__DIR__) . '/ConfirmEdit/FancyCaptcha.i18n.php';
elseif (file_exists(dirname(__DIR__) . '/ConfirmEdit/i18n/fancy'))
	$wgMessagesDirs['FancyCaptcha'] = dirname(__DIR__) . '/ConfirmEdit/i18n/fancy'; // 1.23-1.24
else
	$wgMessagesDirs['FancyCaptcha'] = dirname(__DIR__) . '/ConfirmEdit/FancyCaptcha/i18n'; // 1.25+
$wgAutoloadClasses['WikiKCaptcha'] = __DIR__ . '/WikiKCaptcha.class.php';

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'WikiKCaptcha',
	'author' => array( 'Vitaliy Filippov', 'Sergei Kruglov' ),
	'version' => '1.0',
	'url' => 'http://wiki.4intra.net/WikiKCaptcha',
	'description' => 'KCAPTCHA plug-in for MediaWiki ConfirmEdit extension',
);
