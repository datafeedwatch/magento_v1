<?php

class DataFeedWatch_Connector_Adminhtml_ConnectorsettingsController extends Mage_Adminhtml_Controller_Action{

    public function indexAction(){
        if($this->_request->isPost()) {
            $additional_attributes = $this->_request->getParam('additional_attributes');
            if ($additional_attributes) {
                Mage::getModel('core/config')->saveConfig('datafeedwatch/settings/attributes', serialize($additional_attributes));
            }

            $url_type = $this->_request->getParam('url_type');
            if($url_type){
                Mage::getModel('core/config')->saveConfig('datafeedwatch/settings/url_type', $url_type);
            }

            $logic = $this->_request->getParam('attribute_logic');
            if($logic){
                Mage::getModel('core/config')->saveConfig('datafeedwatch/settings/attribute_logic', serialize($logic));
            }

			Mage::getModel('core/config')->saveConfig('datafeedwatch/settings/last_save', date('Y-m-d H:i:s'));
            Mage::getModel('core/config')->saveConfig('datafeedwatch/settings/ready', 1);

            //also clean config cache
            $cacheType = 'config';
            Mage::app()->getCacheInstance()->cleanType($cacheType);
            Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $cacheType));

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('connector')->__('Settings were successfully saved.'));
            $this->_redirect('*/*/*');
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    protected $username = 'datafeedwatch';
    protected $firstname = 'Api Access';
    protected $lastname = 'DataFeedWatch';
    protected $email = 'magento@datafeedwatch.com';
    protected $register_url = 'https://my.datafeedwatch.com/platforms/magento/sessions/finalize';
    protected $register_url_dev = 'https://my.preview.datafeedwatch.com/platforms/magento/sessions/finalize';

    /**
     * currently the same as $register_url
     * @var string
     */
    protected $redirect_url = 'https://my.datafeedwatch.com/';
    protected $redirect_url_dev = 'https://my.preview.datafeedwatch.com/';

    public function createuserAction() {

        //Create Api Role for datafeedwatch user
        $role = $this->_createApiRole();

        //Prepare Api Key
        $api_key = $this->_generateApiKey();

        //send the api key to DFW
        file_get_contents($this->_registerUrl($api_key));

	    //Get Api User
	    $user = $this->getUser();
	    if ($user->getId()) {
		    //Update Api User
		    $user = $this->_updateApiUser($api_key, $user);
	    } else {
		    //Create Api User
		    $user = $this->_createApiUser($api_key);
	    }

        //Assign Api User to the Api Role
        $user->setRoleId($role->getId())->setUserId($user->getId());
        $user->add();

        //redirect to register token url in DFW
        $this->getResponse()->setRedirect($this->_registerUrl($api_key));
        return;
    }

    public function redirectAction(){
        if (stristr(Mage::getUrl(),'http://datafeedwatch.stronazen.pl/') || stristr(Mage::getUrl(),'http://datafeedwatch.codingmice.com/')) {
            $this->getResponse()->setRedirect($this->redirect_url_dev);
        } else {
            $this->getResponse()->setRedirect($this->redirect_url);
        }
        return;
    }

    public function getUser() {
        $model = Mage::getModel('api/user');
        return $model->load($this->email, 'email');
    }

    private function _generateApiKey() {
        return sha1(time()+substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32));
    }

    private function _registerUrl($api_key) {
        if (stristr(Mage::getUrl(),'http://datafeedwatch.stronazen.pl/') || stristr(Mage::getUrl(),'http://datafeedwatch.codingmice.com/')) {
            return $this->register_url_dev.'?shop='.Mage::getBaseUrl().'&token='.$api_key;
        } else {
            return $this->register_url.'?shop='.Mage::getBaseUrl().'&token='.$api_key;
        }
    }

    private function _createApiRole(){
        $role = Mage::getModel('api/roles')->load($this->lastname, 'role_name');
        if ($role->isObjectNew()) {
            $role = $role
                ->setName($this->lastname)
                ->setPid(false)
                ->setRoleType('G')
                ->save();

            $resource = array("all");

            Mage::getModel("api/rules")
                ->setRoleId($role->getId())
                ->setResources($resource)
                ->saveRel();
        }
        return $role;
    }

    private function _createApiUser($api_key){
        $data = array(
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'is_active' => 1,
            'api_key' => $api_key,
            'api_key_confirmation' => $api_key,
        );

        $user = Mage::getModel('api/user');
        $user->setData($data);
        $user->save();
        return $user;
    }

	/**
	 * @param $api_key  string
	 * @param $user     Mage_Api_Model_User
	 *
	 * @return Mage_Api_Model_User
	 */

	private function _updateApiUser($api_key, $user) {
		$data = array(
			'username' => $this->username,
			'firstname' => $this->firstname,
			'lastname' => $this->lastname,
			'is_active' => 1,
			'api_key' => $api_key,
			'api_key_confirmation' => $api_key,
		);

		$user->setData($data);
		$user->save();
		return $user;
	}
}