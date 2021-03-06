<?php
/**
  Author: Wing Ming Chan
  Copyright (c) 2016 Wing Ming Chan <chanw@upstate.edu>
  MIT Licensed
  Modification history:
   8/15/2016 Added comments to work with ReflectionUtility.
   7/6/2015 Added getPreferences, readPreferences, and editPreferences.
   6/23/2015 Reverted the signature of performWorkflowTransition.
   5/26/2015 Added namespace
   5/21/2015 Added more comments
   5/5/2015 Added type hints to several methods
   12/10/2014 Fixed a bug in createId
   10/5/2014 Added getAsset
   8/15/2014 Modified createId to take care of whitespace and /
   7/18/2014 Modified createId to take care of assets in Global
   7/14/2014 Added getUrl
   6/6/2014 Fixed a bug in publish and unpublish
   4/17/2014 Modified the signature of retrieve so that the property can be empty
   3/24/2014 Modified createId to throw exceptions and added isHexString
   2/26/2014 Removed workflowConfiguration from property, and twitter feed block from property and type
   2/24/2014 Fixed a typo in the Property class
   1/8/2014 Changed all property strings to constants, added the $types array and a getType method
   10/30/2013 Fixed a bug in __call
   10/29/2013 Added storeResults
   10/28/2013 Added/modified all documentation comments
   10/26/2013 Added retrieve
   10/25/2013 Added the enhanced __call method to generate read and get
   10/21/2013 Added all operation methods
 */
namespace cascade_ws_AOHS;

use cascade_ws_constants as c;
use cascade_ws_utility   as u;
use cascade_ws_asset     as a;
use cascade_ws_exception as e;

/**
<p>This class encapsulates the WSDL URL, the authentication object, and the SoapClient object, 
and provides services of all operations defined in the WSDL.</p>
*/
class AssetOperationHandlerService
{
    const DEBUG = false;
    const DUMP  = false;
/**
The constructor.
@param string $url The url of the WSDL
@param stdClass $auth The authentication object
@throws ServerException if the server connection cannot be established
<documentation><description>The constructor.</description>
<example>$service = new aohs\AssetOperationHandlerService( $wsdl, $auth );</example></documentation>
*/
    public function __construct(/* string */$url, \stdClass $auth )
    {
        $this->url            = $url;
        $this->auth           = $auth;
        $this->message        = '';
        $this->success        = '';
        $this->createdAssetId = '';
        $this->lastRequest    = '';
        $this->lastResponse   = '';
        
        foreach( $this->properties as $property )
        {
            // turn a property name like 'publishSet' to 'PublishSet'
            $property = ucwords( $property );
            // populate the two arrays for dynamic generation of methods
            // attach the prefixes 'read' and 'get'
            $this->read_methods[] = 'read' . $property;
            $this->get_methods[]  = 'get'  . $property;
        }
        
        try
        {
            $this->soapClient = new \SoapClient( $this->url, array( 'trace' => 1 ) );
        }
        catch( \Exception $e )
        {
            throw new e\ServerException( S_SPAN . $e->getMessage() . E_SPAN );
        }
    }
    
/**
Dynamically generates the read and get methods.
@param string $func The function name
@param array $params The parameters fed into the function
@method stdClass getAssetFactory() Returns the asset factory read
@method readAssetFactory( stdClass ) Reads an asset factory
<documentation><description>Dynamically generates the read and get methods.</description>
</documentation>
*/
    function __call(/* string */$func,/* array */$params )
    {
        // derive the property name from method name
        if( strpos( $func, 'read' ) === 0 )
            $property = substr( $func, 4 );
        else if( strpos( $func, 'get' ) === 0 )
            $property = substr( $func, 3 );
        
        $property = ucwords( $property );
        
        // read methods
        if( in_array( $func, $this->read_methods ) )
        {
            $read_param = new \stdClass();
            $read_param->authentication = $this->auth;
            $read_param->identifier     = $params[ 0 ];
    
            $this->reply = $this->soapClient->read( $read_param );
        
            if( ( $this->reply->readReturn->success == 'true' ) && 
                  isset( $this->reply->readReturn->asset->$property ) )
            {
                // store the property
                $this->read_assets[ $property ] = $this->reply->readReturn->asset->$property; 
            }
   
            $this->storeResults( $this->reply->readReturn );
        }
        // get methods
        else if( in_array( $func, $this->get_methods ) )
        {
            // could be NULL
            return $this->read_assets[ $property ];
        }
    }
    
/**
Batch-executes the operations.
@param array $operations The array of operations
<documentation><description>Batch-executes the operations.</description>
<example></example></documentation>
*/
    function batch(/* array */$operations )
    {
        $batch_param                 = new \stdClass();
        $batch_param->authentication = $this->auth;
        $batch_param->operation      = $operations;
        
        $this->reply = $this->soapClient->batch( $batch_param );
        // the returned object is an array
        $this->storeResults();
    }
    
/**
Checks in an asset with the given identifier.
@param stdClass $identifier The identifier of the asset to be checked in
@param string   $comments The comments to be added
<documentation><description>Checks in an asset with the given identifier.</description>
<example></example></documentation>
*/
    function checkIn( \stdClass $identifier,/* string */$comments='' )
    {
        $checkin_param                 = new \stdClass();
        $checkin_param->authentication = $this->auth;
        $checkin_param->identifier     = $identifier;
        $checkin_param->comments       = $comments;
        
        $this->reply = $this->soapClient->checkIn( $checkin_param );
        $this->storeResults( $this->reply->checkInReturn );
    }
    
/**
Checks out an asset with the given identifier.
@param stdClass $identifier The identifier of the asset to be checked out
<documentation><description>Checks out an asset with the given identifier.</description>
<example></example></documentation>
*/
    function checkOut( \stdClass $identifier )
    {
        $checkout_param                 = new \stdClass();
        $checkout_param->authentication = $this->auth;
        $checkout_param->identifier     = $identifier;
        
        $this->reply = $this->soapClient->checkOut( $checkout_param );
        $this->storeResults( $this->reply->checkOutReturn );
    }
    
/**
Copies the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be copied
@param stdClass $newIdentifier The new identifier of the new object
@param string   $newName The new name assigned to the new object
@param bool     $doWorkflow Whether to do any workflow
<documentation><description>Copies the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function copy( \stdClass $identifier, \stdClass $newIdentifier,/* string */$newName,/* bool */$doWorkflow ) 
    {
        $copy_params                 = new \stdClass();
        $copy_params->authentication = $this->auth;
        $copy_params->identifier     = $identifier;
        $copy_params->copyParameters = new \stdClass();
        $copy_params->copyParameters->destinationContainerIdentifier = $newIdentifier;
        $copy_params->copyParameters->newName                        = $newName;
        $copy_params->copyParameters->doWorkflow                     = $doWorkflow;
        
        $this->reply = $this->soapClient->copy( $copy_params );
        $this->storeResults( $this->reply->copyReturn );
    }
    
/**
Creates the asset.
@param stdClass $asset The asset to be created
@return string The ID of the newly created asset
<documentation><description>Creates the asset.</description>
<example></example></documentation>
*/
    public function create( \stdClass $asset ) 
    {
        $create_params                 = new \stdClass();
        $create_params->authentication = $this->auth;
        $create_params->asset          = $asset;
        
        $this->reply = $this->soapClient->create( $create_params );
        $this->storeResults( $this->reply->createReturn );
        
        return $this->reply->createReturn->createdAssetId;
    }
    
/**
Creates an id object for an asset.
@param string $type The type of the asset
@param string $id_path Either the id or the path of an asset
@param string $siteName The site name
@return stdClass The identifier
<documentation><description>Creates an id object for an asset.</description>
<example></example></documentation>
*/
    public function createId(/* string */$type,/* string */$id_path,/* string */$site_name = NULL )/* : \stdClass */
    {
        if( !( is_string( $type ) && ( is_string( $id_path ) || is_int( $id_path ) ) ) )
            throw new e\UnacceptableValueException( "Only strings are accepted in createId." );
            
        $non_digital_id_types = array(
            c\T::GROUP, c\T::ROLE, c\T::SITE, c\T::USER
        );
        
        $id_path = trim( $id_path );
        
        if( strlen( $id_path ) > 1 )
        {
            $id_path = trim( $id_path );
            $id_path = trim( $id_path, '/' );
        }
    
        $identifier = new \stdClass();
        
        if( $this->isHexString( $id_path ) )
        {
            // if id string is passed in, ignore site name
            $identifier->id = $id_path;
        }
        else if( in_array( $type, $non_digital_id_types ) )
        {
            if( $type != c\T::SITE ) // not a site
            {
                $identifier->id = $id_path;
            }
            else // a site
            {
                $identifier->path       = new \stdClass();
                $identifier->path->path = $id_path;
            }
        }
        else if( u\StringUtility::startsWith( $id_path, "ROOT_" ) )
        {
            $identifier->id = $id_path;
        }
        // asset in Global
        else if( $site_name == NULL )
        {
            $identifier->path           = new \stdClass();
            $identifier->path->path     = $id_path;
            $identifier->path->siteName = $site_name;
        }
        else
        {
            if( trim( $site_name ) == "" )
            {
                throw new e\EmptyValueException( 
                    S_SPAN . c\M::EMPTY_SITE_NAME . E_SPAN );
            }
            $identifier->path           = new \stdClass();
            $identifier->path->path     = $id_path;
            $identifier->path->siteName = $site_name;
        }
        $identifier->type = $type;
        return $identifier;
    }
    
/**
Creates an id object for an asset.
@param string $id The id string of an asset
@param string $type The type of the asset
@return stdClass The identifier
<documentation><description>Creates an id object for an asset.</description>
<example></example></documentation>
*/
    public function createIdWithIdType(/* string */$id,/* string */$type )/* : \stdClass */
    {
        return $this->createId( $type, $id );
    }

/**
Creates an id object for an asset.
@param string $path The path and name of an asset
@param string $siteName The site name
@param string $type The type of the asset
@return stdClass The identifier
<documentation><description>Creates an id object for an asset.</description>
<example></example></documentation>
*/
    public function createIdWithPathSiteNameType(/* string */$path,/* string */$site_name,/* string */$type )
    {
        return $this->createId( $type, $path, $site_name );
    }

/**
Creates a file stdClass object.
@param string $parentFolderId the id object of the parent folder
@param string $siteName The site name
@param string $name The name of the file
@param binary $data The data of the file
@return stdClass The file object
<documentation><description>Creates a file stdClass object.</description>
<example></example></documentation>
*/
    public function createFileWithParentIdSiteNameNameData(
        /* string */$parentFolderId,/* string */$siteName,/* string */$name, $data )/* : \stdClass */
    {
        $file                 = new \stdClass();
        $file->parentFolderId = $parentFolderId;
        $file->siteName       = $siteName;
        $file->name           = $name;
        $file->data           = $data;
        return $file;
    }

/**
Deletes the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be deleted
<documentation><description>Deletes the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function delete( \stdClass $identifier )
    {
        $delete_params                 = new \stdClass();
        $delete_params->authentication = $this->auth;
        $delete_params->identifier     = $identifier;
        
        $this->reply = $this->soapClient->delete( $delete_params );
        $this->storeResults( $this->reply->deleteReturn );
    }
    
/**
Deletes the message with the given identifier.
@param stdClass $identifier The identifier of the message to be deleted
<documentation><description>Deletes the message with the given identifier.</description>
<example></example></documentation>
*/
    public function deleteMessage( \stdClass $identifier )
    {
        $delete_message_params                 = new \stdClass();
        $delete_message_params->authentication = $this->auth;
        $delete_message_params->identifier     = $identifier;
        
        $this->reply = $this->soapClient->deleteMessage( $delete_message_params );
        $this->storeResults( $this->reply->deleteMessageReturn );
    }
    
/**
Edits the given asset.
@param stdClass $asset The asset to be edited
<documentation><description>Edits the given asset.</description>
<example></example></documentation>
*/
    public function edit( \stdClass $asset )
    {
        $edit_params                 = new \stdClass();
        $edit_params->authentication = $this->auth;
        $edit_params->asset          = $asset;
        
        $this->reply = $this->soapClient->edit( $edit_params );
        $this->storeResults( $this->reply->editReturn );
    }
    
/**
Edits the given accessRightsInformation.
@param stdClass $accessRightsInformation the accessRightsInformation to be edited
@param bool     $applyToChildren Whether to apply the settings to children
<documentation><description>Edits the given accessRightsInformation.</description>
<example></example></documentation>
*/
    public function editAccessRights( \stdClass $accessRightsInformation,/* bool */$applyToChildren )
    {
        $edit_params                          = new \stdClass();
        $edit_params->authentication          = $this->auth;
        $edit_params->accessRightsInformation = $accessRightsInformation;
        $edit_params->applyToChildren         = $applyToChildren;

        $this->reply = $this->soapClient->editAccessRights( $edit_params );
        $this->storeResults( $this->reply->editAccessRightsReturn );
    }
    
/**
Edits the preferences.
@param string $name The name of the preference
@param string $name The value of the preference
<documentation><description>Edits the preferences.</description>
<example></example></documentation>
*/
    public function editPreferences(/* string */$name,/* string */$value ) 
    {
        $edit_preferences_param                    = new \stdClass();
        $edit_preferences_param->authentication    = $this->auth;
        $edit_preferences_param->preference        = new \stdClass();
        $edit_preferences_param->preference->name  = $name;
        $edit_preferences_param->preference->value = $value;
        
        $this->reply = $this->soapClient->editPreference( $edit_preferences_param );
        $this->storeResults( $this->reply->editPreferenceReturn );
    }
    
/**
Edits the given workflowSettings.
@param stdClass $workflowSettings The workflowSettings to be edited
@param bool     $applyInheritWorkflowsToChildren Whether to apply inherited workflows to children
@param bool     $applyRequireWorkflowToChildren Whether to apply required workflows to children
<documentation><description>Edits the given workflowSettings.</description>
<example></example></documentation>
*/
    public function editWorkflowSettings( 
        \stdClass $workflowSettings,/* bool */$applyInheritWorkflowsToChildren,/* bool */$applyRequireWorkflowToChildren )
    {
        $edit_params                   = new \stdClass();
        $edit_params->authentication   = $this->auth;
        $edit_params->workflowSettings = $workflowSettings;
        $edit_params->applyInheritWorkflowsToChildren = $applyInheritWorkflowsToChildren;
        $edit_params->applyRequireWorkflowToChildren  = $applyRequireWorkflowToChildren;
        
        $this->reply = $this->soapClient->editWorkflowSettings( $edit_params );
        $this->storeResults( $this->reply->editWorkflowSettingsReturn );
    }
    
/**
Creates an asset object, bridging this class and the Asset classes.
@param string $type The type of the asset
@param string $id_path Either the ID string or the path of the asset
@param binary $site_name The site name
@return a\Asset The asset object
@throw Exception if the asset cannot be retrieved
<documentation><description>Creates an asset object, bridging this class and the Asset classes.</description>
<example></example></documentation>
*/
    public function getAsset(/* string */$type,/* string */$id_path,/* string */$site_name=NULL )/* : a\Asset */
    {
        if( !in_array( $type, c\T::getTypeArray() ) )
            throw new e\NoSuchTypeException( 
                S_SPAN . "The type $type does not exist." . E_SPAN );
            
        $class_name = c\T::$type_class_name_map[ $type ]; // get class name
        $class_name = a\Asset::NAME_SPACE . "\\" . $class_name;
        
        try
        {
            return new $class_name( // call constructor
                $this, 
                $this->createId( $type, $id_path, $site_name ) );
        }
        catch( \Exception $e )
        {
            if( self::DEBUG && self::DUMP ) { u\DebugUtility::out( $e->getMessage() ); }
            throw $e;
        }        
    }
    
/**
Gets the audits object after the call of readAudits().
@return stdClass The audits object
<documentation><description>Gets the audits object after the call of readAudits().</description>
<example>u\DebugUtility::dump( $service->getAudits();</example></documentation>
*/
    public function getAudits()/* : \stdClass */
    {
        return $this->audits;
    }
    
/**
Gets the ID of an asset newly created.
@return string The ID string
<documentation><description>Gets the ID of an asset newly created.</description>
<example>echo $service->getCreatedAssetId();</example></documentation>
*/
    public function getCreatedAssetId()/* : string */
    {
        return $this->createdAssetId;
    }
    
/**
Gets the last request XML.
@return string The last request XML
<documentation><description>Gets the last request XML.</description>
<example>echo u\XMLUtility::replaceBrackets( $service->getLastRequest() );</example></documentation>
*/
    public function getLastRequest()/* : string */
    {
        return $this->lastRequest;
    }
    
/**
Gets the last response.
@return string The last response
<documentation><description>Gets the last response.</description>
<example>echo S_PRE, u\XMLUtility::replaceBrackets( $service->getLastResponse() ), E_PRE;</example></documentation>
*/
    public function getLastResponse()/* : string */
    {
        return $this->lastResponse;
    }
    
/**
Gets the messages object after the call of listMessages().
@return stdClass The listed messages
<documentation><description>Gets the messages object after the call of listMessages().</description>
<example>$service->listMessages();
u\DebugUtility::dump( $service->getListedMessages() );
</example></documentation>
*/
    public function getListedMessages()/* : \stdClass */
    {
        return $this->listed_messages;
    }
    
/**
Gets the message after an operation.
@return string The message
<documentation><description>Gets the message after an operation.</description>
<example>echo $service->getMessage();</example></documentation>
*/
    public function getMessage()/* : string */
    {
        return $this->message;
    }
    
/**
Gets the preferences after the call of readPreferences().
@return stdClass The preferences object
<documentation><description>Gets the preferences after the call of readPreferences().</description>
<example>$service->readPreferences();
u\DebugUtility::dump( $service->getPreferences() );</example></documentation>
*/
    public function getPreferences()/* : \stdClass */
    {
        return $this->preferences;
    }
    
/**
Gets the accessRightInformation object after the call of readAccessRightInformation().
@return stdClass The accessRightsInformation object
<documentation><description>Gets the accessRightInformation object after the call of readAccessRightInformation().</description>
<example></example></documentation>
*/
    public function getReadAccessRightInformation()/* : \stdClass */
    {
        return $this->reply->readAccessRightsReturn->accessRightsInformation;
    }
    
/**
Gets the asset object after the call of read().
@return stdClass The asset read
<documentation><description>Gets the asset object after the call of read().</description>
<example></example></documentation>
*/
    public function getReadAsset()/* : \stdClass */
    {
        return $this->reply->readReturn->asset;
    }
    
/**
Gets the file object after the call of read().
@return stdClass The file read
<documentation><description>Gets the file object after the call of read().</description>
<example></example></documentation>
*/
    public function getReadFile()/* : \stdClass */
    {
        return $this->reply->readReturn->asset->file;
    }
       
/**
Gets the workflow object after the call of readWorkflow().
@return stdClass The workflow read
<documentation><description>Gets the workflow object after the call of readWorkflow().</description>
<example></example></documentation>
*/
    public function getReadWorkflow()/* : \stdClass */
    {
        return $this->reply->readWorkflowInformationReturn->workflow;
    }
    
/**
Gets the workflowSettings object after the call of readWorkflowSettings().
@return stdClass The workflowSettings object
<documentation><description></description>
<example></example></documentation>
*/
    public function getReadWorkflowSettings()/* : \stdClass */
    {
        return $this->reply->readWorkflowSettingsReturn->workflowSettings;
    }
    
/**
Gets the response object after an operation.
@return stdClass The response object
<documentation><description>Gets the workflowSettings object after the call of readWorkflowSettings().</description>
<example></example></documentation>
*/
    public function getReply()/* : \stdClass */
    {
        return $this->reply;
    }
    
/**
Gets the searchMatches object after the call of search().
@return stdClass The searchMatches object
<documentation><description>Gets the searchMatches object after the call of search().</description>
<example></example></documentation>
*/
    public function getSearchMatches()/* : \stdClass */
    {
        return $this->searchMatches;
    }
    
/**
Returns the string 'true' or 'false' after an operation.
@return string The string 'true' or 'false'
<documentation><description>Returns the string 'true' or 'false' after an operation.</description>
<example></example></documentation>
*/
    public function getSuccess()/* : string */
    {
        return $this->success;
    }
    
/**
Gets the type of an asset.
@param string $id_string The 32-digit hex id string
@return string The type string
<documentation><description>Gets the type of an asset.</description>
<example></example></documentation>
*/
    public function getType(/* string */$id_string )/* : string */
    {
        $type_count = count( $this->types );
        
        for( $i = 0; $i < $type_count; $i++ )
        {
            $id = $this->createId( $this->types[ $i ], $id_string );
            $operation = new \stdClass();
            $read_op   = new \stdClass();
    
            $read_op->identifier = $id;
            $operation->read     = $read_op;
            $operations[]        = $operation;
        }
        
        $this->batch( $operations );
        
        $reply_array = $this->getReply()->batchReturn;
        
        for( $j = 0; $j < $type_count; $j++ )
        {
            if( $reply_array[ $j ]->readResult->success == 'true' )
            {
                foreach( c\T::$type_property_name_map as $type => $property )
                {
                    if( isset( $reply_array[ $j ]->readResult->asset->$property ) )
                        return $type;
                }
            }
        }
        
        return "The id does not match any asset type.";
    }
    
/**
Gets the WSDL URL string.
@return string The WSDL URL string
<documentation><description>Gets the WSDL URL string.</description>
<example></example></documentation>
*/
    public function getUrl()/* : string */
    {
        return $this->url;
    }
    
/**
Returns a bool indicating whether the string is a 32-digit hex string.
@param string $string The input string
@return bool Whether the input string is a hex string
<documentation><description>Returns a bool indicating whether the string is a 32-digit hex string.</description>
<example></example></documentation>
*/
    public function isHexString(/* string */$string )/* : string */
    {
        $pattern = "/[0-9a-f]{32}/";
        $matches = array();
        
        preg_match( $pattern, $string, $matches );
        
        if( isset( $matches[ 0 ] ) )
            return $matches[ 0 ] == $string;
        return false;
    }

/**
Returns true if an operation is successful.
@return bool The result of an operation
<documentation><description>Returns true if an operation is successful.</description>
<example></example></documentation>
*/
    public function isSuccessful()/* : bool */
    {
        return $this->success == 'true';
    }
    
/**
Lists all messages.
<documentation><description>Lists all messages.</description>
<example></example></documentation>
*/
    public function listMessages()
    {
        $list_messages_params                 = new \stdClass();
        $list_messages_params->authentication = $this->auth;
        
        $this->reply = $this->soapClient->listMessages( $list_messages_params );
        $this->storeResults( $this->reply->listMessagesReturn );
        
        if( $this->isSuccessful() )
        {
            $this->listed_messages = $this->reply->listMessagesReturn->messages;
        }
    }
    
/**
Lists all sites.
<documentation><description>Lists all sites.</description>
<example></example></documentation>
*/
    public function listSites()
    {
        $list_sites_params                 = new \stdClass();
        $list_sites_params->authentication = $this->auth;
        
        $this->reply = $this->soapClient->listSites( $list_sites_params );
        $this->storeResults( $this->reply->listSitesReturn );
    }
    
/**
Lists all subscribers of an asset.
@param stdClass $identifier The identifier of the asset
<documentation><description>Lists all subscribers of an asset.</description>
<example></example></documentation>
*/
    public function listSubscribers( \stdClass $identifier )
    {
        $list_subscribers_params                 = new \stdClass();
        $list_subscribers_params->authentication = $this->auth;
        $list_subscribers_params->identifier     = $identifier;
        
        $this->reply = $this->soapClient->listSubscribers( $list_subscribers_params );
        $this->storeResults( $this->reply->listSubscribersReturn );
    }
    
/**
Marks a message as 'read' or 'unread'.
@param stdClass $identifier The identifier of the message
@param string   $markType The string 'read' or 'unread'
<documentation><description>Marks a message as 'read' or 'unread'.</description>
<example></example></documentation>
*/
    public function markMessage( \stdClass $identifier,/* string */$markType )
    {
        $mark_message_params                 = new \stdClass();
        $mark_message_params->authentication = $this->auth;
        $mark_message_params->identifier     = $identifier;
        $mark_message_params->markType       = $markType;
        
        $this->reply = $this->soapClient->markMessage( $mark_message_params );
        $this->storeResults( $this->reply->markMessageReturn );
    }
    
/**
Moves the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be moved
@param stdClass $newIdentifier The new container identifier
@param string   $newName The new name assigned to the object moved
@param bool     $doWorkflow Whether to do workflow
<documentation><description>Moves the asset with the given identifier.</description>
<example></example></documentation>
*/
    function move( \stdClass $identifier, \stdClass $newIdentifier=NULL,/* string */$newName="",/* bool */$doWorkflow=false ) 
    {
        $move_params                 = new \stdClass();
        $move_params->authentication = $this->auth;
        $move_params->identifier     = $identifier;
        $move_params->moveParameters = new \stdClass();
        $move_params->moveParameters->destinationContainerIdentifier = $newIdentifier;
        $move_params->moveParameters->newName                        = $newName;
        $move_params->moveParameters->doWorkflow                     = $doWorkflow;
        
        $this->reply = $this->soapClient->move( $move_params );
        $this->storeResults( $this->reply->moveReturn );
    }
    
/**
Performs the workflow transition.
@param string   $workflowId The current workflow ID
@param string   $actionIdentifier The identifier of the action
@param string   $transitionComment The comments
<documentation><description>Performs the workflow transition.</description>
<example></example></documentation>
*/
    public function performWorkflowTransition( 
        /* string */$workflowId,/* string */$actionIdentifier,/* string */$transitionComment='' )
    {
        $workflowTransitionInformation                    = new \stdClass();
        $workflowTransitionInformation->workflowId        = $workflowId;
        $workflowTransitionInformation->actionIdentifier  = $actionIdentifier;
        $workflowTransitionInformation->transitionComment = $transitionComment;
        
        $transition_params                                = new \stdClass();
        $transition_params->authentication                = $this->auth;
        $transition_params->workflowTransitionInformation = $workflowTransitionInformation;
        
        $this->reply = $this->soapClient->performWorkflowTransition( $transition_params );
        $this->storeResults( $this->reply->performWorkflowTransitionReturn );
    }
    
/**
Prints the XML of the last request.
<documentation><description>Prints the XML of the last request.</description>
<example></example></documentation>
*/
    public function printLastRequest()
    {
        print_r( $this->lastRequest );
    }
    
/**
Prints the XML of the last response.
<documentation><description>Prints the XML of the last response.</description>
<example></example></documentation>
*/
    public function printLastResponse()
    {
        print_r( $this->lastResponse );
    }
    
/**
Publishes the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be published
@param Destination $destination The destination(s) where the asset should be published
<documentation><description>Publishes the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function publish( \stdClass $identifier,/* a\Destination */$destination=NULL ) 
    {
        $publish_param = new \stdClass();
        $publish_info  = new \stdClass();
        $publish_param->authentication = $this->auth;
        $publish_info->identifier      = $identifier;
        
        if( isset( $destination ) )
        {
            if( is_array( $destination ) )
                $publish_info->destinations = $destination;
            else
                $publish_info->destinations = array( $destination );
        }
        
        $publish_info->unpublish           = false;
        $publish_param->publishInformation = $publish_info;
        
        $this->reply = $this->soapClient->publish( $publish_param );
        $this->storeResults( $this->reply->publishReturn );
    }
    
/**
Reads the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be read
<documentation><description>Reads the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function read( \stdClass $identifier ) 
    {
        if( self::DEBUG ) { u\DebugUtility::dump( $identifier ); }
        
        $read_param                 = new \stdClass();
        $read_param->authentication = $this->auth;
        $read_param->identifier     = $identifier;
        
        $this->reply = $this->soapClient->read( $read_param );
        $this->storeResults( $this->reply->readReturn );
    }

/**
Reads the access rights of the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be read
<documentation><description>Reads the access rights of the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function readAccessRights( \stdClass $identifier ) 
    {
        $read_param                 = new \stdClass();
        $read_param->authentication = $this->auth;
        $read_param->identifier     = $identifier;
        
        $this->reply = $this->soapClient->readAccessRights( $read_param );
        $this->storeResults( $this->reply->readAccessRightsReturn );
    }
    
/**
Reads the audits of the asset with the given parameters.
@param stdClass $params The parameters of readAudits
<documentation><description>Reads the audits of the asset with the given parameters.</description>
<example></example></documentation>
*/
    public function readAudits( \stdClass $params ) 
    {
        $read_audits_param                  = new \stdClass();
        $read_audits_param->authentication  = $this->auth;
        $read_audits_param->auditParameters = $params;
        
        $this->reply = $this->soapClient->readAudits( $read_audits_param );
        $this->storeResults( $this->reply->readAuditsReturn );
        $this->audits  = $this->reply->readAuditsReturn->audits;
    }
    
/**
Reads the preferences.
<documentation><description>Reads the preferences.</description>
<example></example></documentation>
*/
    public function readPreferences() 
    {
        $read_preferences_param                  = new \stdClass();
        $read_preferences_param->authentication  = $this->auth;
        
        $this->reply = $this->soapClient->readPreferences( $read_preferences_param );
        $this->storeResults( $this->reply->readPreferencesReturn );
        $this->preferences  = $this->reply->readPreferencesReturn->preferences;
    }
    
/**
Reads the workflow information associated with the given identifier.
@param stdClass $identifier The identifier of the object to be read
<documentation><description>Reads the workflow information associated with the given identifier.</description>
<example></example></documentation>
*/
    public function readWorkflowInformation( \stdClass $identifier ) 
    {
        $read_param                 = new \stdClass();
        $read_param->authentication = $this->auth;
        $read_param->identifier     = $identifier;
        
        $this->reply = $this->soapClient->readWorkflowInformation( $read_param );
        $this->storeResults( $this->reply->readWorkflowInformationReturn );
    }    
    
/**
Reads the workflow settings associated with the given identifier.
@param stdClass $identifier The identifier of the object to be read
<documentation><description>Reads the workflow settings associated with the given identifier.</description>
<example></example></documentation>
*/
    public function readWorkflowSettings( \stdClass $identifier ) 
    {
        $read_param                 = new \stdClass();
        $read_param->authentication = $this->auth;
        $read_param->identifier     = $identifier;
        
        $this->reply = $this->soapClient->readWorkflowSettings( $read_param );
        $this->storeResults( $this->reply->readWorkflowSettingsReturn );
    }
    
/**
Retrieves a property of an asset.
@param stdClass $id The id of the property
@param string $property The property name
@return stdClass The property or NULL
<documentation><description>Retrieves a property of an asset.</description>
<example></example></documentation>
*/

    function retrieve( \stdClass $id,/* string */$property="" )
    {
        if( $property == "" )
        {
            $property = c\T::$type_property_name_map[ $id->type ];
        }
        
        $read_param                 = new \stdClass();
        $read_param->authentication = $this->auth;
        $read_param->identifier     = $id;

        $this->reply = $this->soapClient->read( $read_param );
        $this->storeResults( $this->reply->readReturn );

        if( isset( $this->reply->readReturn->asset ) )
            return $this->reply->readReturn->asset->$property;
        return NULL;
    }
    
/**
Searches for some entity.
@param stdClass $searchInfo The searchInfo object
<documentation><description>Searches for some entity.</description>
<example></example></documentation>
*/
    public function search( \stdClass $searchInfo ) 
    {
        $search_info_param                    = new \stdClass();
        $search_info_param->authentication    = $this->auth;
        $search_info_param->searchInformation = $searchInfo;
        
        $this->reply = $this->soapClient->search( $search_info_param );
        $this->searchMatches = $this->reply->searchReturn->matches;
        $this->storeResults( $this->reply->searchReturn );
    }        
    
/**
Sends a message.
@param stdClass $message The message object to be sent
<documentation><description>Sends a message.</description>
<example></example></documentation>
*/
    public function sendMessage( \stdClass $message ) 
    {
        $send_message_param                 = new \stdClass();
        $send_message_param->authentication = $this->auth;
        $send_message_param->message        = $message;
        
        $this->reply = $this->soapClient->sendMessage( $send_message_param );
        $this->storeResults( $this->reply->sendMessageReturn );
    }    
    
/**
Copies the site with the given identifier.
@param string $original_id The ID string of the site to be copied
@param string $original_name The name of the site to be copied
@param string $new_name The name assigned to the new site
<documentation><description>Copies the site with the given identifier.</description>
<example></example></documentation>
*/
    function siteCopy(/* string */$original_id,/* string */$original_name,/* string */$new_name ) 
    {
        $site_copy_params                   = new \stdClass();
        $site_copy_params->authentication   = $this->auth;
        $site_copy_params->originalSiteId   = $original_id;
        $site_copy_params->originalSiteName = $original_name;
        $site_copy_params->newSiteName      = $new_name;

        $this->reply = $this->soapClient->siteCopy( $site_copy_params );
        $this->storeResults( $this->reply->siteCopyReturn );
    }
    
/**
Unpublishes the asset with the given identifier.
@param stdClass $identifier The identifier of the object to be unpublished
@param Destination $destination The destination where the asset should be unpublished
<documentation><description>Unpublishes the asset with the given identifier.</description>
<example></example></documentation>
*/
    public function unpublish( \stdClass $identifier,/* a\Destination */$destination=NULL ) 
    {
        $publish_param = new \stdClass();
        $publish_info  = new \stdClass();
        $publish_param->authentication = $this->auth;
        $publish_info->identifier      = $identifier;
        
        if( isset( $destination ) )
        {
            if( is_array( $destination ) )
                $publish_info->destinations = $destination;
            else
                $publish_info->destinations = array( $destination );
        }
        
        $publish_info->unpublish           = true;
        $publish_param->publishInformation = $publish_info;
        
        $this->reply = $this->soapClient->publish( $publish_param );
        $this->storeResults( $this->reply->publishReturn );
    }
    
    // helper function
    private function storeResults( $return=NULL )
    {
        if( isset( $return ) )
        {
            $this->success  = $return->success;
            $this->message  = $return->message;
        }
        $this->lastRequest  = $this->soapClient->__getLastRequest();
        $this->lastResponse = $this->soapClient->__getLastResponse();
    }

    // from the constructor
    /*@var string The url */
    private $url;
    /*@var stdClass The authentication */
    private $auth;
    /*@var SoapClient The SoapClient */
    private $soapClient;
    
    // from the response
    /*@var string The message of the response */
    private $message;
    /*@var string The string 'true' or 'false' */
    private $success;
    /*@var string The id string of a created asset */
    private $createdAssetId;
    /*@var string The XML of the last request */
    private $lastRequest;
    /*@var string The XML of the last response */
    private $lastResponse;
    /*@var stdClass The object returned from an operation */
    private $reply;
    /*@var stdClass The audits object */
    private $audits;
    /*@var stdClass The searchMatches object */
    private $searchMatches;
    /*@var stdClass The listed messages */
    private $listed_messages;
    
    private $preferences;
    
    // 42 properties
    // property array to generate methods
    /*@var array The array of property names */
    private $properties = array(
        c\P::ASSETFACTORY,
        c\P::ASSETFACTORYCONTAINER,
        c\P::CONNECTORCONTAINER,
        c\P::CONTENTTYPE,
        c\P::CONTENTTYPECONTAINER,
        c\P::DATADEFINITION,
        c\P::DATADEFINITIONCONTAINER,
        c\P::DATABASETRANSPORT,
        c\P::DESTINATION,
        c\P::FACEBOOKCONNECTOR,
        c\P::FEEDBLOCK,
        c\P::FILE,
        c\P::FILESYSTEMTRANSPORT,
        c\P::FOLDER,
        c\P::FTPTRANSPORT,
        c\P::GOOGLEANALYTICSCONNECTOR,
        c\P::GROUP,
        c\P::INDEXBLOCK,
        c\P::METADATASET,
        c\P::METADATASETCONTAINER,
        c\P::PAGE,
        c\P::PAGECONFIGURATIONSET,
        c\P::PAGECONFIGURATIONSETCONTAINER,
        c\P::PUBLISHSET,
        c\P::PUBLISHSETCONTAINER,
        c\P::REFERENCE,
        c\P::ROLE,
        c\P::SCRIPTFORMAT,
        c\P::SITE,
        c\P::SITEDESTINATIONCONTAINER,
        c\P::SYMLINK,
        c\P::TARGET,
        c\P::TEMPLATE,
        c\P::TEXTBLOCK,
        c\P::TRANSPORTCONTAINER,
        c\P::USER,
        c\P::WORDPRESSCONNECTOR,
        c\P::WORKFLOWDEFINITION,
        c\P::WORKFLOWDEFINITIONCONTAINER,
        c\P::XHTMLDATADEFINITIONBLOCK,
        c\P::XMLBLOCK,
        c\P::XSLTFORMAT
    );
    
    // 46 types
    /*@var array The array of types of assets */
    private $types = array(
        c\T::ASSETFACTORY,
        c\T::ASSETFACTORYCONTAINER,
        c\T::CONNECTORCONTAINER,
        c\T::CONTENTTYPE,
        c\T::CONTENTTYPECONTAINER,
        c\T::DATADEFINITION,
        c\T::DATADEFINITIONCONTAINER,
        c\T::DESTINATION,
        c\T::FACEBOOKCONNECTOR,
        c\T::FEEDBLOCK,
        c\T::FILE,
        c\T::FOLDER,
        c\T::GOOGLEANALYTICSCONNECTOR,
        c\T::GROUP,
        c\T::INDEXBLOCK,
        c\T::MESSAGE,
        c\T::METADATASET,
        c\T::METADATASETCONTAINER,
        c\T::PAGE,
        c\T::PAGECONFIGURATION,
        c\T::PAGECONFIGURATIONSET,
        c\T::PAGECONFIGURATIONSETCONTAINER,
        c\T::PAGEREGION,
        c\T::PUBLISHSET,
        c\T::PUBLISHSETCONTAINER,
        c\T::REFERENCE,
        c\T::ROLE,
        c\T::SCRIPTFORMAT,
        c\T::SITE,
        c\T::SITEDESTINATIONCONTAINER,
        c\T::SYMLINK,
        c\T::TARGET,
        c\T::TEMPLATE,
        c\T::TEXTBLOCK,
        c\T::TRANSPORTDB,
        c\T::TRANSPORTFS,
        c\T::TRANSPORTFTP,
        c\T::TRANSPORTCONTAINER,
        c\T::USER,
        c\T::WORDPRESSCONNECTOR,
        c\T::WORKFLOW,
        c\T::WORKFLOWDEFINITION,
        c\T::WORKFLOWDEFINITIONCONTAINER,
        c\T::XHTMLDATADEFINITIONBLOCK,
        c\T::XMLBLOCK,
        c\T::XSLTFORMAT
    );
    
    /*@var array The array of readX names */
    private $read_methods = array();
    /*@var array The array of getX names */
    private $get_methods  = array();
    /*@var array The array to store property stdClass objects */
    private $read_assets  = array();
}
?>