<?php
class Rest_Acl_Plugin extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        global $CC_CONFIG;
        if ($this->_request->getModuleName() == "rest") {
            $api_key = $request->getHeader('api_key');
            
            if (!in_array($api_key, $CC_CONFIG["apiKey"]))
            {
                $this->getResponse()
                    ->setHttpResponseCode(403)
                    ->appendBody("Invalid API Key\n");
                
                $request->setModuleName('default')
                    ->setControllerName('error')
                    ->setActionName('denied')
                    ->setDispatched(true);
            }
        }
    }
}