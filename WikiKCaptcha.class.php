<?php

/**
 * KCAPTCHA plug-in for MediaWiki ConfirmEdit extension
 *
 * Uses FancyCaptcha localisation messages
 * Uses KCAPTCHA by Kruglov Sergei, 2006 (www.captcha.ru, www.kruglov.ru)
 *
 * License: GNU GPLv3 or later
 * Copyright (c) Vitaliy Filippov, 2013
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

class WikiKCaptcha extends SimpleCaptcha {

	function keyMatch( $answer, $info ) {
		global $wgKCaptchaLogFile;
		$real = @$info['answer'];
		if ( $wgKCaptchaLogFile ) {
			global $wgTitle, $wgRequest, $wgUser;
			$action = $wgRequest->getVal( 'action' );
			if ( $action == 'submitlogin' ) {
				$username = $wgRequest->getVal( 'wpName' );
			} else {
				$username = str_replace( ' ', '_', $wgUser->getName() );
			}
			$msg = array(
				date( 'Y-m-d H:i:s' ), wfGetIP(),
				$username, $wgTitle->getPrefixedDBKey(),
				$action, $real, $answer
			);
			foreach ( $msg as $m ) {
				if ( strpos( $m, ',' ) !== false ) {
					$m = '"' . str_replace( '"', '""', $m ) . '"';
				}
			}
			$msg = implode( ',', $msg ) . "\n";
			if ( !@filesize( $wgKCaptchaLogFile ) ) {
				$msg = "Date,IP,User,Title,Action,Keystring,Answer\n$msg";
			}
			file_put_contents( $wgKCaptchaLogFile, $msg, FILE_APPEND );
		}
		return $real && $answer === $real;
	}

	function addCaptchaAPI( &$resultArr ) {
		$index = $this->storeCaptcha( array( 'viewed' => false, 'nohex' => true ) );
		$title = SpecialPage::getTitleFor( 'Captcha', 'image' );
		$resultArr['captcha']['type'] = 'image';
		$resultArr['captcha']['mime'] = 'image/jpg';
		$resultArr['captcha']['id'] = $index;
		$resultArr['captcha']['url'] = $title->getLocalUrl( 'wpCaptchaId=' . urlencode( $index ) );
	}

	/**
	 * Insert the captcha prompt into the edit form.
	 */
	function getForm( OutputPage $out = NULL ) {
		global $wgKCaptchaHex;

		// Generate a random key for use of this captcha image in this session.
		// This is needed so multiple edits in separate tabs or windows can
		// go through without extra pain.
		$index = $this->storeCaptcha( array( 'viewed' => false ) );

		$title = SpecialPage::getTitleFor( 'Captcha', 'image' );

		$params = array(
			'type' => 'text',
			'autocorrect' => 'off',
			'autocapitalize' => 'off',
			'required' => 'required',
			'tabindex' => 1,
		);

		$str = '<table><tr><td>' .
			Html::element( 'img', array(
				'src'    => $title->getLocalUrl( 'wpCaptchaId=' . urlencode( $index ) ),
				'alt'    => 'Image' ) ) .
			'</td><td>' .
			Html::element( 'input', array(
				'type'  => 'hidden',
				'name'  => 'wpCaptchaId',
				'id'    => 'wpCaptchaId',
				'value' => $index ) );
		if ( !empty( $wgKCaptchaHex ) ) {
			$str .= Html::element( 'input', array(
				'type'  => 'hidden',
				'name'  => 'wpCaptchaWord',
				'id'    => 'wpCaptchaWord' ) );
			$params['onkeyup'] = $params['onchange'] = 'var v = ""; for (var i = 0; i < this.value.length; i++) { '.
				'var c = this.value.charCodeAt(i).toString(16); if (c.length == 1) { v += "0"; } v += c; '.
				'} document.getElementById("wpCaptchaWord").value = v.toLowerCase();';
		} else {
			$params['name'] = $params['id'] = 'wpCaptchaWord';
		}
		$str .= Html::element( 'input', $params ) . // tab in before the edit textarea
			'</td></tr></table>';
		return $str;
	}

	function showImage() {
		global $wgOut, $IP, $wgKCaptchaHex;
		$wgOut->disable();
		$info = $this->retrieveCaptcha();
		if ( !$info ) {
			wfHttpError( 500, 'Internal Error', 'Requested bogus captcha image' );
		} elseif ( $info['viewed'] ) {
			// Bots like to peck same captcha ID many times
			// We deny it by clearing the answer so the check will fail after two requests
			$info['answer'] = false;
			$this->storeCaptcha( $info );
			require_once( "$IP/includes/StreamFile.php" );
			wfStreamFile( __DIR__.'/pecking.png' );
		} else {
			require __DIR__.'/util/kcaptcha.php';
			$c = new KCAPTCHA;
			$info['viewed'] = wfTimestampNow();
			// Hex answers are suppressed in API as they can break it
			$info['answer'] = !empty( $wgKCaptchaHex ) && empty( $info['nohex'] )
				? strtolower( bin2hex( $c->getKeyString() ) ) : $c->getKeyString();
			$this->storeCaptcha( $info );
		}
		return false;
	}

	function getMessage( $action ) {
		$name = 'fancycaptcha-' . $action;
		$text = wfMessage( $name )->text();
		return wfMessage( $name, $text )->isDisabled() ? wfMessage( 'fancycaptcha-edit' )->text() : $text;
	}
}
