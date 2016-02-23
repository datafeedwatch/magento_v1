<?php
class DataFeedWatch_Connector_Adminhtml_ConnectorbackendController extends Mage_Adminhtml_Controller_Action {
	protected $username = 'datafeedwatch';
	protected $firstname = 'Api Access';
	protected $lastname = 'DataFeedWatch';
	protected $email = 'magento@datafeedwatch.com';
	protected $register_url = 'https://my.datafeedwatch.com/platforms/magento/sessions/finalize';

    /**
     * currently the same as $register_url
     * @var string
     */
    protected $redirect_url = 'https://my.datafeedwatch.com/';

	public function indexAction() {
        $ready = Mage::getStoreConfig('datafeedwatch/settings/ready');
        if(!$ready){
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('connector')
                ->__('You need to pick your attributes and save your attribute settings before you can access My DataFeedWatch.'));

            $this->_redirect('*/adminhtml_settings/index');
            return;
        }
		$this->loadLayout();
		$this->_title($this->__("DataFeedWatch"));
		$this->renderLayout();
	}

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
        $this->getResponse()->setRedirect($this->redirect_url);
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
		return $this->register_url.'?shop='.Mage::getBaseUrl().'&token='.$api_key;
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
