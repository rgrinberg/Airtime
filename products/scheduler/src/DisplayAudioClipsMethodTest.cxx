/*------------------------------------------------------------------------------

    Copyright (c) 2004 Media Development Loan Fund
 
    This file is part of the LiveSupport project.
    http://livesupport.campware.org/
    To report bugs, send an e-mail to bugs@campware.org
 
    LiveSupport is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
  
    LiveSupport is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with LiveSupport; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
 
    Author   : $Author: fgerlits $
    Version  : $Revision: 1.3 $
    Location : $Source: /home/paul/cvs2svn-livesupport/newcvsrepo/livesupport/products/scheduler/src/DisplayAudioClipsMethodTest.cxx,v $

------------------------------------------------------------------------------*/

/* ============================================================ include files */

#ifdef HAVE_CONFIG_H
#include "configure.h"
#endif

#if HAVE_UNISTD_H
#include <unistd.h>
#else
#error "Need unistd.h"
#endif


#include <string>
#include <iostream>
#include <vector>
#include <XmlRpcValue.h>

#include "LiveSupport/Db/ConnectionManagerFactory.h"
#include "LiveSupport/Storage/StorageClientFactory.h"
#include "LiveSupport/Authentication/AuthenticationClientFactory.h"

#include "DisplayAudioClipsMethod.h"
#include "DisplayAudioClipsMethodTest.h"


using namespace LiveSupport::Db;
using namespace LiveSupport::Storage;
using namespace LiveSupport::Scheduler;
using namespace LiveSupport::Authentication;


/* ===================================================  local data structures */


/* ================================================  local constants & macros */

CPPUNIT_TEST_SUITE_REGISTRATION(DisplayAudioClipsMethodTest);

/**
 *  The name of the configuration file for the storage client factory.
 */
const std::string DisplayAudioClipsMethodTest::storageClientConfig =
                                                    "etc/storageClient.xml";

/**
 *  The name of the configuration file for the connection manager factory.
 */
const std::string DisplayAudioClipsMethodTest::connectionManagerConfig =
                                          "etc/connectionManagerFactory.xml";

/**
 *  The name of the configuration file for the authentication client factory.
 */
const std::string DisplayAudioClipsMethodTest::authenticationClientConfig =
                                          "etc/authenticationClient.xml";


/* ===============================================  local function prototypes */


/* =============================================================  module code */

/*------------------------------------------------------------------------------
 *  Configure a Configurable with an XML file.
 *----------------------------------------------------------------------------*/
void
DisplayAudioClipsMethodTest :: configure(
            Ptr<Configurable>::Ref      configurable,
            const std::string           fileName)
                                                throw (std::invalid_argument,
                                                       xmlpp::exception)
{
    Ptr<xmlpp::DomParser>::Ref  parser(new xmlpp::DomParser(fileName, true));
    const xmlpp::Document * document = parser->get_document();
    const xmlpp::Element  * root     = document->get_root_node();

    configurable->configure(*root);
}

                                                        
/*------------------------------------------------------------------------------
 *  Set up the test environment
 *----------------------------------------------------------------------------*/
void
DisplayAudioClipsMethodTest :: setUp(void)                         throw ()
{
    Ptr<AuthenticationClientFactory>::Ref acf;
    try {
        Ptr<StorageClientFactory>::Ref scf
                                        = StorageClientFactory::getInstance();
        configure(scf, storageClientConfig);

        Ptr<ConnectionManagerFactory>::Ref cmf
                                    = ConnectionManagerFactory::getInstance();
        configure(cmf, connectionManagerConfig);

        acf = AuthenticationClientFactory::getInstance();
        configure(acf, authenticationClientConfig);

    } catch (std::invalid_argument &e) {
        CPPUNIT_FAIL("semantic error in configuration file");
    } catch (xmlpp::exception &e) {
        CPPUNIT_FAIL("error parsing configuration file");
    } catch (std::exception &e) {
        CPPUNIT_FAIL(e.what());
    }
    
    authentication = acf->getAuthenticationClient();
    if (!(sessionId = authentication->login("root", "q"))) {
        CPPUNIT_FAIL("could not log in to authentication server");
    }
}


/*------------------------------------------------------------------------------
 *  Clean up the test environment
 *----------------------------------------------------------------------------*/
void
DisplayAudioClipsMethodTest :: tearDown(void)                      throw ()
{
    authentication->logout(sessionId);
    sessionId.reset();
    authentication.reset();
}


/*------------------------------------------------------------------------------
 *  Just a very simple smoke test
 *----------------------------------------------------------------------------*/
void
DisplayAudioClipsMethodTest :: firstTest(void)
                                                throw (CPPUNIT_NS::Exception)
{
    Ptr<DisplayAudioClipsMethod>::Ref method(new DisplayAudioClipsMethod());
    XmlRpc::XmlRpcValue       parameter;
    XmlRpc::XmlRpcValue       rootParameter;
    rootParameter.setSize(1);
    XmlRpc::XmlRpcValue       result;
    XmlRpc::XmlRpcValue       audioClip;       

    result.clear();
    parameter["sessionId"]  = sessionId->getId();
    rootParameter[0]        = parameter;
    method->execute(rootParameter, result);
    CPPUNIT_ASSERT(result.size() == 2);

    audioClip = result[0];
    CPPUNIT_ASSERT(int(audioClip["id"]) == 10001);
    CPPUNIT_ASSERT(int(audioClip["playlength"]) == 60 * 60);

    audioClip = result[1];
    CPPUNIT_ASSERT(int(audioClip["id"]) == 10002);
    CPPUNIT_ASSERT(int(audioClip["playlength"]) == 30 * 60);
}

