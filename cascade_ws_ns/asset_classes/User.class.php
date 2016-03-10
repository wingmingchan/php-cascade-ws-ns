<?php 
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 1/26/2016 Added leaveGroup and isInGroup.
  * 5/28/2015 Added namespaces.
 */
namespace cascade_ws_asset;

use cascade_ws_constants as c;
use cascade_ws_AOHS as aohs;
use cascade_ws_utility as u;
use cascade_ws_exception as e;
use cascade_ws_property as p;

class User extends Asset
{
    const DEBUG = false;
    const TYPE  = c\T::USER;
    
    public function disable()
    {
        $this->getProperty()->enabled = false;
        return $this;
    }

    public function edit()
    {
        $asset                                    = new \stdClass();
        $asset->{ $p = $this->getPropertyName() } = $this->getProperty();
        // edit asset
        $service = $this->getService();
        $service->edit( $asset );
        
        if( !$service->isSuccessful() )
        {
            throw new e\EditingFailureException( 
                S_SPAN . c\M::EDIT_ASSET_FAILURE . E_SPAN . $service->getMessage() );
        }
        return $this->reloadProperty();
    }
    
    public function enable()
    {
        $this->getProperty()->enabled = true;
        return $this;
    }

    public function getAuthType()
    {
        return $this->getProperty()->authType;
    }
    
    public function getDefaultGroup()
    {
        return $this->getProperty()->defaultGroup;
    }
    
    public function getDefaultSiteId()
    {
        return $this->getProperty()->defaultSiteId;
    }
    
    public function getDefaultSiteName()
    {
        return $this->getProperty()->defaultSiteName;
    }
    
    public function getEnabled()
    {
        return $this->getProperty()->enabled;
    }
    
    public function getId()
    {
        return $this->getProperty()->username;
    }
    
    public function getEmail()
    {
        return $this->getProperty()->email;
    }
    
    public function getFullName()
    {
        return $this->getProperty()->fullName;
    }
    
    public function getGroups()
    {
        return $this->getProperty()->groups;
    }
    
    public function getName()
    {
        return $this->getProperty()->username;
    }
    
    public function getRole()
    {
        return $this->getProperty()->role;
    }
    
    public function getPassword()
    {
        return $this->getProperty()->password;
    }
    
    public function getUserName()
    {
        return $this->getProperty()->username;
    }
    
    public function isInGroup( Group $group )
    {
        $users = $group->getUsers();
        
        if( strpos( $users, Group::DELIMITER . $this->getProperty()->username . Group::DELIMITER ) !== false )
            return true;
            
        return false;
    }
    
    public function joinGroup( Group $g )
    {
        $g->addUser( Asset::getAsset( $this->getService(),
            User::TYPE,
            $this->getProperty()->username ) )->edit();
        return $this;
    }
    
    public function leaveGroup( Group $g )
    {
        $g->removeUser( Asset::getAsset( $this->getService(),
            User::TYPE,
            $this->getProperty()->username ) )->edit();
        return $this;
    }
    
    public function setDefaultGroup( Group $group=NULL )
    {
        if( isset( $group ) )
        {
            $this->getProperty()->defaultGroup   = $group->getName();
        }
        return $this;
    }
    
    public function setDefaultSite( Site $site=NULL )
    {
        if( isset( $site ) )
        {
            $this->getProperty()->defaultSiteId   = $site->getId();
            $this->getProperty()->defaultSiteName = $site->getName();
        }
        else
        {
            $this->getProperty()->defaultSiteId   = NULL;
            $this->getProperty()->defaultSiteName = NULL;
        }
        return $this;
    }
    
    public function setEnabled( $bool )
    {
        if( !c\BooleanValues::isBoolean( $bool ) )
            throw new e\UnacceptableValueException( "The value $bool must be a boolean." );

        $this->getProperty()->enabled = $bool;
        return $this;
    }
    
    public function setEmail( $email )
    {
        if( trim( $email ) == '' )
            throw new e\EmptyValueException( 
                S_SPAN . c\M::EMPTY_EMAIL . E_SPAN );

        $this->getProperty()->email = $email;
        return $this;
    }
    
    public function setFullName( $name )
    {
        if( trim( $name ) == '' )
            throw new e\EmptyValueException(
                S_SPAN . c\M::EMPTY_FULL_NAME . E_SPAN );

        $this->getProperty()->fullName = $name;
        return $this;
    }
    
    public function setPassword( $pw )
    {
        if( trim( $pw ) == '' )
            throw new e\EmptyValueException(
                S_SPAN . c\M::EMPTY_PASSWORD . E_SPAN );

        $this->getProperty()->password = $pw;
        return $this;
    }
}
?>