<?php
/**
 * Created by Vadym Radvansky
 * Date: 2/26/14 9:20 AM
 */
namespace rvadym\google_api_access;
class Controller_GA extends \AbstractController {

    public $redirect_url;

    function init() {
        parent::init();
    }

    function checkAuth() {
        $m = $this->getAccessModel();
        $m->tryLoadAny();
        if ($this->getOAuth()->isAccessTokenExpired()) {
            if (isset($_GET['code'])) {
                $this->getClient()->authenticate($_GET['code']);
                $this->saveAccess($this->getClient()->getAccessToken());
                $this->api->redirect($this->api->url());
            }
            else if ($m->loaded() && isset($m['token'])) {
                $this->getClient()->setAccessToken($m['token']);
                if ($this->getClient()->isAccessTokenExpired()) {
                    $this->getOAuth()->refreshToken($m['refresh_token']);
                    $this->saveAccess($this->getOAuth()->getAccessToken());
                    $this->api->redirect($this->api->url());
                }
            }
            else {
                $this->getClient()->addScope(array(
                    'https://www.googleapis.com/auth/analytics',
                    //'https://www.googleapis.com/auth/analytics.edit',
                    'https://www.googleapis.com/auth/analytics.readonly',
                ));
                return array('login_url'=>$this->getClient()->createAuthUrl());
            }
            return array();
        }
    }

    private function saveAccess($token) {
        $m = $this->getAccessModel();
        $m->set('name','this is name');
        $m->set('token',$token);
        if ($refresh_token = $m->getRefreshToken($token,false)) {
            $m->set('refresh_token',$refresh_token);
        }
        $m->save();
    }

    function getAction($view,$arr,$text='Login with Google account using OAuth 2.0') {
        if (array_key_exists('login_url',$arr)) {
            $view->add('View')->setElement('a')->setAttr('href',$arr['login_url'])->set($text);
        }
    }

    function addRequestRefreshToken() {
        $this->getClient()->setAccessType('offline');
        return $this;
    }

    // google oAuth singletone
    private $oAuth = null;
    function getOAuth() {
        if (!$this->oAuth) {
            $this->_getOAuth();
        }
        return $this->oAuth;
    }
    private function _getOAuth() {
        $this->oAuth = new \Google_Auth_OAuth2($this->getClient());
        $this->getClient()->setClassConfig($this->oAuth,'approval_prompt','force');
        $this->getClient()->setClassConfig($this->oAuth,'response_type','code');
    }

    // google client singletone
    private $google_client = null;
    function getClient() {
        if (!$this->google_client) {
            $this->_getClient();
        }
        return $this->google_client;
    }
    private function _getClient() {
        $dev_key       = $this->api->getConfig('rvadym_google_api_access/API key');
        $client_id     = $this->api->getConfig('rvadym_google_api_access/Client ID');
        $client_secret = $this->api->getConfig('rvadym_google_api_access/Client secret');

        $this->google_client = new \Google_Client();
        $this->google_client->setClientId($client_id);
        $this->google_client->setClientSecret($client_secret);
        $this->google_client->setRedirectUri($this->redirect_url);
        $this->google_client->setAccessType('offline');
        $this->google_client->setApplicationName("ATK4 Google API Addon");
        $this->google_client->setDeveloperKey($dev_key);
    }

    // access model singletone
    private $access_model = null;
    function getAccessModel() {
        if (!$this->access_model) {
            $this->_getAccessModel();
        }
        return $this->access_model;
    }
    private function _getAccessModel() {
        $this->access_model = $this->add('rvadym\\google_api_access\\Model_Access');
    }
}