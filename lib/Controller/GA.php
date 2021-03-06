<?php
/**
 * Created by Vadym Radvansky
 * Date: 2/26/14 9:20 AM
 */
namespace rvadym\google_api_access;
class Controller_GA extends \AbstractController {

    public $redirect_url;
    public $model_class = 'rvadym\\google_api_access\\Model_Access';
    public $scope = array();

    function init() {
        parent::init();
        if(!$this->getAccessModel()->loaded())throw $this->exception('Must load access_model first');
    }

    function checkAuth($cli=false) {
        $m = $this->getAccessModel();

        if (isset($_GET['code'])) {
            if ($cli) {
                throw $this->exception('Cannot authenticate() with CLI','rvadym\\google_api_access\\Exception_NotCLIAction');
            }
            $this->authenticate($_GET['code']); // just returned from google
            return true;
        }

        if ($m['token']) {
            $this->getClient()->setAccessToken($m['token']);
        }

        if ($this->getClient()->isAccessTokenExpired()) {
            if ($m['refresh_token']) {
                $this->refreshToken($m,$cli);
                // redirect
            } else if (!$cli) {
                return array('login_url'=>$this->createAuthUrl()); // need to go to google
            } else {
                throw $this->exception('Refresh token is not set! (CLI) 1','rvadym\\google_api_access\\Exception_NotCLIAction');
            }
        }

        if ($m['token']) {
            $this->getClient()->setAccessToken($m['token']); // <~ OK, target!
            return array();
        }

        if ($cli) {
            throw $this->exception('Refresh token is not set! (CLI) 2','rvadym\\google_api_access\\Exception_NotCLIAction');
        }
        return array('login_url'=>$this->createAuthUrl()); // need to go to google
    }

    private function createAuthUrl() {
        $this->getClient()->addScope($this->scope);
        return $this->getClient()->createAuthUrl();
    }

    private function authenticate($code) {
        $this->getClient()->authenticate($code);
        $this->saveAccess($this->getClient()->getAccessToken());
        return true;
    }

    private function refreshToken($m,$cli) {
        $this->getOAuth()->refreshToken($m['refresh_token']);
        $this->saveAccess($this->getOAuth()->getAccessToken());
    }

    private function saveAccess($token) {
        $m = $this->getAccessModel();
        if (!$m['name']) {
            $m->set('name','-- set name --');
        }
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

    function getOAuth() {
        return $this->getClient()->getAuth();
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
        $this->google_client->setClassConfig('Google_Auth_OAuth2','approval_prompt','force');
        $this->google_client->setClassConfig('Google_Auth_OAuth2','response_type','code');
        $this->google_client->setApplicationName("ATK4 Google API Addon");
        $this->google_client->setDeveloperKey($dev_key);
    }

    // access model singletone
    protected $access_model = null;
    function getAccessModel() {
        return $this->access_model;
    }
}
