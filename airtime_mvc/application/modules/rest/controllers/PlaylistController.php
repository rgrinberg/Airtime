<?php
/**
 * Api module's index controller. You should notice the "Api" Namespace
 */
class Rest_PlaylistController extends Zend_Controller_Action
{

    //private $_lang = array();

    public function init()
    {
        Logging::log("Rest_PlaylistController");
        /* Initialize action controller here */
        $context = $this->_helper->getHelper('contextSwitch');
        $context->addActionContext('index', 'json')->initContext();;

        //$this->_lang = Zend_Registry::get("Custom_language");
    }

   /**
    * Get a list of all playlists.
    */
    public function indexAction()
    {
        Logging::log("Rest Playlist Index Action");

        // disable the view and the layout
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $jsonStr = json_encode(array("playlist"=>"foo"));
        echo $jsonStr;

    }
}