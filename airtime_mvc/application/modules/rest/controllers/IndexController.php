<?php 
/**
 * Api module's index controller. You should notice the "Api" Namespace
 */
class Rest_IndexController extends Zend_Controller_Action
{

    //private $_lang = array();

    public function init()
    {
        Logging::log("Rest_IndexController");
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $context = $this->_helper->getHelper('contextSwitch');
        $context->addActionContext('index', 'json')->initContext();
    }

   /*
    */
    public function indexAction()
    {
        Logging::log("Rest Index Action");

        $this->getResponse()->setHttpResponseCode(200)
            ->appendBody("all content");        
    }
    
    public function getAction()
    {
        $this->getResponse()->setHttpResponseCode(100)
            ->appendBody("getAction\n");
    }
    
    public function postAction()
    {
        $this->getResponse()->appendBody("From postAction()\n");
    }
    
    public function putAction()
    {
        $this->getResponse()->appendBody("From putAction()\n");
    }
    
    public function deleteAction()
    {
        $this->getResponse()->appendBody("From deleteAction()\n");
    }
    
}
