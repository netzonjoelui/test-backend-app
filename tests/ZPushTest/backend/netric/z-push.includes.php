<?php
// Add includes for z-push because they don't use namespaces (sad)
ini_set(
    'include_path',
    ini_get('include_path') . PATH_SEPARATOR .
    dirname(__FILE__) . '/../../../../lib/ZPush');

// BEGIN: copied from lib/ZPush/index.php
include_once('lib/exceptions/exceptions.php');
include_once('lib/utils/utils.php');
include_once('lib/utils/compat.php');
include_once('lib/utils/timezoneutil.php');
include_once('lib/utils/stringstreamwrapper.php');
include_once('lib/core/zpushdefs.php');
include_once('lib/core/stateobject.php');
include_once('lib/core/interprocessdata.php');
include_once('lib/core/pingtracking.php');
include_once('lib/core/topcollector.php');
include_once('lib/core/loopdetection.php');
include_once('lib/core/asdevice.php');
include_once('lib/core/statemanager.php');
include_once('lib/core/devicemanager.php');
include_once('lib/core/zpush.php');
include_once('lib/core/zlog.php');
include_once('lib/interface/ibackend.php');
include_once('lib/interface/ichanges.php');
include_once('lib/interface/iexportchanges.php');
include_once('lib/interface/iimportchanges.php');
include_once('lib/interface/isearchprovider.php');
include_once('lib/interface/istatemachine.php');
include_once('lib/core/streamer.php');
include_once('lib/core/streamimporter.php');
include_once('lib/core/synccollections.php');
include_once('lib/core/hierarchycache.php');
include_once('lib/core/changesmemorywrapper.php');
include_once('lib/core/syncparameters.php');
include_once('lib/core/bodypreference.php');
include_once('lib/core/contentparameters.php');
include_once('lib/wbxml/wbxmldefs.php');
include_once('lib/wbxml/wbxmldecoder.php');
include_once('lib/wbxml/wbxmlencoder.php');
include_once('lib/syncobjects/syncobject.php');
include_once('lib/syncobjects/syncbasebody.php');
include_once('lib/syncobjects/syncbaseattachment.php');
include_once('lib/syncobjects/syncmailflags.php');
include_once('lib/syncobjects/syncrecurrence.php');
include_once('lib/syncobjects/syncappointment.php');
include_once('lib/syncobjects/syncappointmentexception.php');
include_once('lib/syncobjects/syncattachment.php');
include_once('lib/syncobjects/syncattendee.php');
include_once('lib/syncobjects/syncmeetingrequestrecurrence.php');
include_once('lib/syncobjects/syncmeetingrequest.php');
include_once('lib/syncobjects/syncmail.php');
include_once('lib/syncobjects/syncnote.php');
include_once('lib/syncobjects/synccontact.php');
include_once('lib/syncobjects/syncfolder.php');
include_once('lib/syncobjects/syncprovisioning.php');
include_once('lib/syncobjects/synctaskrecurrence.php');
include_once('lib/syncobjects/synctask.php');
include_once('lib/syncobjects/syncoofmessage.php');
include_once('lib/syncobjects/syncoof.php');
include_once('lib/syncobjects/syncuserinformation.php');
include_once('lib/syncobjects/syncdeviceinformation.php');
include_once('lib/syncobjects/syncdevicepassword.php');
include_once('lib/syncobjects/syncitemoperationsattachment.php');
include_once('lib/syncobjects/syncsendmail.php');
include_once('lib/syncobjects/syncsendmailsource.php');
include_once('lib/syncobjects/syncvalidatecert.php');
include_once('lib/syncobjects/syncresolverecipients.php');
include_once('lib/syncobjects/syncresolverecipient.php');
include_once('lib/syncobjects/syncresolverecipientsoptions.php');
include_once('lib/syncobjects/syncresolverecipientsavailability.php');
include_once('lib/syncobjects/syncresolverecipientscertificates.php');
include_once('lib/syncobjects/syncresolverecipientspicture.php');
include_once('lib/default/backend.php');
include_once('lib/default/searchprovider.php');
include_once('lib/request/request.php');
include_once('lib/request/requestprocessor.php');
// END: copied from lib/ZPush/index.php

define('ZPUSH_CONFIG', dirname(__FILE__) . '/../../../../config/zpush.config.php');