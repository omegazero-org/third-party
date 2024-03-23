<?php
/*
 * Copyright (C) 2023 warp03
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

class LoginOmzssoPlugin extends \RainLoop\Plugins\AbstractPlugin {

	const
		NAME     = "omzsso",
		VERSION  = "1.1",
		RELEASE  = "2023-06-10",
		REQUIRED = "2.28.0",
		CATEGORY = "Login",
		DESCRIPTION = "Login through account.omegazero.org";

	const
		LOGIN_URI = "https://account.omegazero.org/sm/capi/oauth2/authorize.php",
		TOKEN_URI = "https://account.omegazero.org/sm/capi/oauth2/token.php",
		USERINFO_URI = "https://account.omegazero.org/sm/capi/oidc/userinfo.php";

	public function Init() : void {
		$this->UseLangs(true);

		$this->addJs("login-omzsso.js");
		$this->addJs("injectscripts.js");

		$this->addPartHook("OAuthEndpoint", "oauthEndpoint");

		$this->addTemplate("Login.html");
		$this->addTemplate("AdminLogin.html", true);

		$this->addHook("filter.send-message", "sendMessageHook");

		spl_autoload_register(function($classname){
			if(str_starts_with($classname, "OAuth2\\")){
				include_once __DIR__ . "/OAuth2/src/" . strtr("\\{$classname}", "\\", DIRECTORY_SEPARATOR) . ".php";
			}
		});
	}

	public function configMapping() : array {
		return [
			\RainLoop\Plugins\Property::NewInstance("client_id")->SetLabel("Client ID")->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING),
			\RainLoop\Plugins\Property::NewInstance("client_secret")->SetLabel("Client Secret")->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING),
			\RainLoop\Plugins\Property::NewInstance("scope")->SetLabel("Scopes")->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING)->SetDefaultValue("openid"),
			\RainLoop\Plugins\Property::NewInstance("hpwh_mgr_secret")->SetLabel("hpwh_mgr_secret")->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING),
		];
	}

	public function oauthEndpoint(string ...$pathParts) : bool {
		$actions = $this->Manager()->Actions();
		$http = $actions->Http();
		$http->ServerNoCache();
		$redirectUri = str_replace("http:", "https:", $http->GetFullUrl() . "?OAuthEndpoint");
		if(isset($_GET["error"])){
			$errmsg = $_GET["error"];
			if(isset($_GET["error_description"]))
				$errmsg .= ": " . $_GET["error_description"];
			echo "OAuth2 login failed: " . $errmsg;
		}else if(isset($_GET["code"]) && isset($_GET["state"])){
			$res = $this->handleCallback($this->getOAuthClient($actions), $actions, $redirectUri);
			if($res != null)
				echo "OAuth2 login failed: " . $res;
		}else{
			$params = array(
				"scope" => \trim($this->Config()->Get("plugin", "scope", "")),
				"response_type" => "code",
				"state" => "1|"// . \RainLoop\Utils::GetConnectionToken()
			);
			$opUrl = $this->getOAuthClient($actions)->getAuthenticationUrl(static::LOGIN_URI, $redirectUri, $params);
			\MailSo\Base\Http::Location($opUrl);
		}
		return true;
	}

	protected function handleCallback(\OAuth2\Client $oAuthClient, object $actions, string $redirectUri) : ?string {
		$state = $_GET["state"];
		$stateParts = explode("|", $state, 2);
		if(count($stateParts) != 2)
			return "Invalid state";
		//if($stateParts[1] === \RainLoop\Utils::GetConnectionToken())
		//	return "ConnectionToken does not match";

		$authcodeRes = $oAuthClient->getAccessToken(static::TOKEN_URI, "authorization_code", ["code" => $_GET["code"], "redirect_uri" => $redirectUri]);

		$accessToken = !empty($authcodeRes["result"]["access_token"]) ? $authcodeRes["result"]["access_token"] : "";
		$refreshToken = !empty($authcodeRes["result"]["refresh_token"]) ? $authcodeRes["result"]["refresh_token"] : "";
		if(!$accessToken)
			return "access_token empty";

		$oAuthClient->setAccessToken($accessToken);
		$oAuthClient->setAccessTokenType(\OAuth2\Client::ACCESS_TOKEN_BEARER);
		$userinfo = $oAuthClient->fetch(static::USERINFO_URI);

		$accountsReq = curl_init();
		curl_setopt_array($accountsReq, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_URL => "http://tomee.web.services.vm.internal/manager.highperformancewebhosting.com/api/mail/webmaillogin?secret=" . $this->Config()->Get("plugin", "hpwh_mgr_secret", "")
					. "&ownerId=" . $userinfo["result"]["sub"]
		));
		$accountsRes = curl_exec($accountsReq);
		$accountsResCode = curl_getinfo($accountsReq, CURLINFO_HTTP_CODE);
		if(($curl_error = curl_error($accountsReq))){
			return "webmaillogin request failed: " . $curl_error;
		}else if($accountsResCode != 200){
			return "webmaillogin request returned status " . $accountsResCode;
		}else{
			$accountsJson = json_decode($accountsRes, true);
		}
		curl_close($accountsReq);

		$mainAccount = null;
		$additionalAccounts = [];
		foreach($accountsJson as $account){
			$email = $account["email"];
			$password = $account["webmailToken"] ?? "--NOPW--";
			if($mainAccount != null){
				$addAccount = \RainLoop\Model\AdditionalAccount::NewInstanceFromCredentials($actions, $email, $email, new \SnappyMail\SensitiveString($password));
				if(!$addAccount)
					return "Account model creation failed";
				$additionalAccounts[$email] = $addAccount->asTokenArray($mainAccount);
			}else{
				$mainAccount = \RainLoop\Model\MainAccount::NewInstanceFromCredentials($actions, $email, $email, new \SnappyMail\SensitiveString($password));
				if(!$mainAccount)
					return "Account model creation failed";
			}
		}
		if(!$mainAccount)
			return "User has no accounts";
		$actions->StorageProvider()->Put($mainAccount, RainLoop\Providers\Storage\Enumerations\StorageType::SESSION, RainLoop\Utils::GetSessionToken(), "true");
		if($additionalAccounts)
			$actions->SetAccounts($mainAccount, $additionalAccounts);

		$actions->SetAuthToken($mainAccount);

		\MailSo\Base\Http::Location("./");
		return null;
	}

	protected function getOAuthClient(object $actions) : ?\OAuth2\Client {
		$client = null;
		$client_id = \trim($this->Config()->Get("plugin", "client_id", ""));
		$client_secret = \trim($this->Config()->Get("plugin", "client_secret", ""));
		if($client_id && $client_secret){
			try{
				$client = new \OAuth2\Client($client_id, $client_secret);
				$sProxy = $actions->Config()->Get("labs", "curl_proxy", "");
				if(\strlen($sProxy)){
					$client->setCurlOption(CURLOPT_PROXY, $sProxy);
					$sProxyAuth = $actions->Config()->Get("labs", "curl_proxy_auth", "");
					if (\strlen($sProxyAuth)) {
						$client->setCurlOption(CURLOPT_PROXYUSERPWD, $sProxyAuth);
					}
				}
			}catch(\Exception $oException){
				$oActions->Logger()->WriteException($oException, \LOG_ERR);
			}
		}
		return $client;
	}

	public function sendMessageHook(object $message) : void {
		$msgid = $message->MessageId();
		$from = $message->GetFrom();
		$rcpt = $message->GetRcpt();
		if(!$rcpt || count($rcpt) < 1)
			return;
		$msgid = substr($msgid, 1, strpos($msgid, "@") - 1);
		$receivedVal = "by webmail.omegazero.org (SnappyMail) with HTTP id " . $msgid;
		if($from instanceof \MailSo\Mime\Email)
			$receivedVal .= " (envelope-from <" . $from->GetEmail() . ">)";
		if($rcpt[0] instanceof \MailSo\Mime\Email)
			$receivedVal .= " for <" . $rcpt[0]->GetEmail() . ">";
		$receivedVal .= "; " . date(DATE_RFC2822);
		$message->SetCustomHeader("received", $receivedVal);
	}
}
