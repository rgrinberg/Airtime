<?php 
/** 
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 */  
class Rest_Bootstrap extends Zend_Application_Module_Bootstrap  
{  
    protected function _bootstrap()  
    {  
        Logging::log("Rest_Bootstrap");
    }  

}