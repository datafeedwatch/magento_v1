<?php
class DataFeedWatch_Connector_Model_Api_User
    extends Mage_Api_Model_User
{
    const API_KEY_SHUFFLE_STRING    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const API_KEY_LENGTH            = 32;
    const USER_NAME                 = 'datafeedwatch';
    const USER_FIRST_NAME           = 'Api Access';
    const USER_LAST_NAME            = 'DataFeedWatch';
    const USER_EMAIL                = 'magento@datafeedwatch.com';
    const USER_IS_ACTIVE            = 1;
    const ROLE_NAME                 = 'DataFeedWatch';
    const ROLE_TYPE                 = 'G';
    const ROLE_PID                  = false;
    const RULE_RESOURCES            = 'all';

    /** @var string $decodedApiKey */
    private $decodedApiKey;

    public function createDfwUser()
    {
        $role = $this->createDfwUserRole();
        $this->generateApiKey();
        $this->addUserData();
        $this->save();
        $this->setRoleId($role->getId())->setUserId($this->getId());
        $this->add();
        $this->sendNewApiKeyToDfw();
    }

    /**
     * @return string
     */
    public function getDecodedApiKey()
    {
        return $this->decodedApiKey;
    }

    /**
     * @return DataFeedWatch_Connector_Model_Api_User
     */
    public function loadDfwUser()
    {
        return $this->load(self::USER_EMAIL, 'email');
    }

    /**
     * @return Mage_Api_Model_Roles
     * @throws Exception
     */
    protected function createDfwUserRole()
    {
        $role = Mage::getModel('api/roles')->load(self::ROLE_NAME, 'role_name');

        $data = array(
            'name'      => self::ROLE_NAME,
            'pid'       => self::ROLE_PID,
            'role_type' => self::ROLE_TYPE,
        );

        $role->addData($data);
        $role->save();

        Mage::getModel('api/rules')
            ->setRoleId($role->getId())
            ->setResources(array(self::RULE_RESOURCES))
            ->saveRel();

        return $role;
    }

    protected function generateApiKey()
    {
        $this->decodedApiKey = sha1(time() . substr(str_shuffle(self::API_KEY_SHUFFLE_STRING), 0, self::API_KEY_LENGTH));
    }

    protected function addUserData()
    {
        $data = array(
            'username'              => self::USER_NAME,
            'firstname'             => self::USER_FIRST_NAME,
            'lastname'              => self::USER_LAST_NAME,
            'is_active'             => self::USER_IS_ACTIVE,
            'api_key'               => $this->decodedApiKey,
            'email'                 => self::USER_EMAIL,
            'api_key_confirmation'  => $this->decodedApiKey,
        );

        $this->addData($data);
    }

    protected function sendNewApiKeyToDfw()
    {
        file_get_contents($this->getRegisterUrl());
    }

    public function getRegisterUrl()
    {
        $registerUrl = sprintf('%splatforms/magento/sessions/finalize',
            $this->_helper()->getDataFeedWatchUrl());

        return $registerUrl . '?shop=' . Mage::getBaseUrl() . '&token=' . $this->getDecodedApiKey();
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function _helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}