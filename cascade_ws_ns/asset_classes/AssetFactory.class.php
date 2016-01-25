<?php 
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 9/25/2014 Added setWorkflowMode.
  * 7/29/2014 Added getPluginStd, setPlugins.
  * 7/1/2014 Removed copy.
  * 5/23/2014 Fixed a bug in setBaseAsset.
  * 5/22/2014 Added setAllowSubfolderPlacement, 
  *   setFolderPlacementPosition, setOverwrite, and setBaseAsset.
  * 5/21/2014 Fixed some bugs related to foreach.
 */
/**
 * An AssetFactory object represents an asset factory asset
 *
 * @link http://www.upstate.edu/cascade-admin/projects/web-services/oop/classes/asset-classes/asset-factory.php
 */
namespace cascade_ws_asset;

use cascade_ws_constants as c;
use cascade_ws_AOHS as aohs;
use cascade_ws_utility as u;
use cascade_ws_exception as e;
use cascade_ws_property as p;

class AssetFactory extends ContainedAsset
{
    const DEBUG = false;
    const TYPE  = c\T::ASSETFACTORY;
    
    const WORKFLOW_MODE_FACTORY = c\T::FACTORY_CONTROLLED;
    const WORKFLOW_MODE_FOLDER  = c\T::FOLDER_CONTROLLED;
    const WORKFLOW_MODE_NONE    = c\T::NONE;
    
    public function __construct( aohs\AssetOperationHandlerService $service, \stdClass $identifier )
    {
        parent::__construct( $service, $identifier );
        
        if( isset( $this->getProperty()->plugins ) && 
            isset( $this->getProperty()->plugins->plugin ) )
        {
            $this->processPlugins();
        }
    }
    
    public function addGroup( Group $g )
    {
        if( $g == NULL )
        {
            throw new e\NullAssetException( S_SPAN . c\M::NULL_GROUP . E_SPAN );
        }
    
        $group_name   = $g->getName();
        $group_string = $this->getProperty()->applicableGroups;
        $group_array  = explode( ';', $group_string );
        
        if( !in_array( $group_name, $group_array ) )
        {
            $group_array[] = $group_name;
        }
        
        $group_string = implode( ';', $group_array );
        $this->getProperty()->applicableGroups = $group_string;
        return $this;
    }

    public function edit()
    {
        $asset = new \stdClass();
        $this->getProperty()->plugins->plugin = array();
        
        if( count( $this->plugins ) > 0 )
        {
            foreach( $this->plugins as $plugin )
            {
                $this->getProperty()->plugins->plugin[] = $plugin->toStdClass();
            }
        }

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
    
    public function getAllowSubfolderPlacement()
    {
        return $this->getProperty()->allowSubfolderPlacement;
    }

    public function getApplicableGroups()
    {
        return $this->getProperty()->applicableGroups;
    }
    
    public function getAssetType()
    {
        return $this->getProperty()->assetType;
    }
    
    public function getBaseAssetId()
    {
        return $this->getProperty()->baseAssetId;
    }
    
    public function getBaseAssetPath()
    {
        return $this->getProperty()->baseAssetPath;
    }
    
    public function getBaseAssetRecycled()
    {
        return $this->getProperty()->baseAssetRecycled;
    }
    
    public function getFolderPlacementPosition()
    {
        return $this->getProperty()->folderPlacementPosition;
    }
    
    public function getOverwrite()
    {
        return $this->getProperty()->overwrite;
    }
    
    public function getPlacementFolderId()
    {
        return $this->getProperty()->placementFolderId;
    }
    
    public function getPlacementFolderPath()
    {
        return $this->getProperty()->placementFolderPath;
    }
    
    public function getPlacementFolderRecycled()
    {
        return $this->getProperty()->placementFolderRecycled;
    }

    public function getPlugin( $name )
    {
        if( $this->hasPlugin( $name ) )
        {
            foreach( $this->plugins as $plugin )
            {
                if( $plugin->getName() == $name )
                {
                    return $plugin;
                }
            }
        }
        throw new e\NoSuchPluginException( 
        	S_SPAN . "The plugin $name does not exist." . E_SPAN );    
    }
    
    public function getPluginNames()
    {
        $names = array();
        
        if( count( $this->plugins ) > 0 )
        {
            foreach( $this->plugins as $plugin )
            {
                $names[] = $plugin->getName();
            }
        }
        return $names;
    }
    
    public function getPluginStd()
    {
    	return $this->getProperty()->plugins;
    }
    
    public function getWorkflowDefinitionId()
    {
        return $this->getProperty()->workflowDefinitionId;
    }
    
    public function getWorkflowDefinitionPath()
    {
        return $this->getProperty()->workflowDefinitionPath;
    }
    
    public function getWorkflowMode()
    {
        return $this->getProperty()->workflowMode;
    }
    
    public function hasPlugin( $name )
    {
        if( count( $this->plugins ) > 0 )
        {
            foreach( $this->plugins as $plugin )
            {
                if( $plugin->getName() == $name )
                {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function isApplicableToGroup( Group $g )
    {
        if( $g == NULL )
        {
            throw new e\NullAssetException( S_SPAN . c\M::NULL_GROUP . E_SPAN );
        }

        $group_name = $g->getName();
        $group_string = $this->getProperty()->applicableGroups;
        $group_array  = explode( ';', $group_string );
        return in_array( $group_name, $group_array );
    }
    
    public function removeGroup( Group $g )
    {
        if( $g == NULL )
        {
            throw new e\NullAssetException( S_SPAN . c\M::NULL_GROUP . E_SPAN );
        }
        
        $group_name   = $g->getName();
        $group_string = $this->getProperty()->applicableGroups;
        $group_array  = explode( ';', $group_string );
            
        if( in_array( $group_name, $group_array ) )
        {
            $temp = array();
            
            foreach( $group_array as $group )
            {
                if( $group != $group_name )
                {
                    $temp[] = $group;
                }
            }
            $group_array = $temp;
        }
        
        $group_string = implode( ';', $group_array );
        $this->getProperty()->applicableGroups = $group_string;
        
        return $this;
    }
    
    public function setAllowSubfolderPlacement( $bool )
    {
        if( !c\BooleanValues::isBoolean( $bool ) )
            throw new e\UnacceptableValueException( 
            	S_SPAN . "The value $bool must be a boolean." . E_SPAN );
            
        $this->getProperty()->allowSubfolderPlacement = $bool;
        
        return $this;
    }
    
    public function setBaseAsset( Asset $a=NULL )
    {
        if( isset( $a ) )
        {
            $type = $a->getType();
            
            if( u\StringUtility::startsWith( strtolower( $type ), 'block' ) )
            {
                $type = 'block';
            }
            else if( u\StringUtility::startsWith( strtolower( $type ), 'format' ) )
            {
                $type = 'format';
            }
            
            $this->getProperty()->assetType     = $type;
            $this->getProperty()->baseAssetId   = $a->getId();
            $this->getProperty()->baseAssetPath = $a->getPath();
        }
        else
        {
            $this->getProperty()->assetType     = File::TYPE; // dummpy type
            $this->getProperty()->baseAssetId   = NULL;
            $this->getProperty()->baseAssetPath = NULL;
        }
        return $this;
    }
    
    public function setFolderPlacementPosition( $value )
    {
        if( is_nan( $value ) )
        {
            throw new e\UnacceptableValueException( 
            	S_SPAN . "$value is not a number" . E_SPAN );
        }
        
        $this->getProperty()->folderPlacementPosition = intval( $value );
        
        return $this;
    }
    
    public function setOverwrite( $bool )
    {
        if( !c\BooleanValues::isBoolean( $bool ) )
            throw new e\UnacceptableValueException( 
            	S_SPAN . "The value $bool must be a boolean." . E_SPAN );
            
        $this->getProperty()->overwrite = $bool;
        
        return $this;
    }
    
    public function setPlacementFolder( Folder $folder )
    {
        if( $folder == NULL )
            throw new e\NullAssetException( 
            	S_SPAN . c\M::NULL_FOLDER . E_SPAN );
            
        $this->getProperty()->placementFolderId   = $folder->getId();
        $this->getProperty()->placementFolderPath = $folder->getPath();
        
        return $this;
    }
    
    public function setPluginParameterValue( $plugin_name, $param_name, $param_value )
    {
        $plugin = $this->getPlugin( $plugin_name );
        $parameter = $plugin->getParameter( $param_name );
        
        if( isset( $parameter ) )
            $parameter->setValue( $param_value );
        
        return $this;
    }
    
    public function setPlugins( \stdClass $plugins )
    {
    	$property = $this->getProperty();
    	$property->plugins = $plugins;
    	$asset = new \stdClass();
        $asset->{ $p = $this->getPropertyName() } = $property;
        
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
    
    public function setWorkflowMode( $mode=c\T::NONE, WorkflowDefinition $wd=NULL )
    {
    	if( !c\WorkflowModeValues::isWorkflowMode( $mode ) )
    		throw new e\UnacceptableWorkflowModeException( 
    			S_SPAN . "The workflow mode $mode is unacceptable." . E_SPAN );
    		
    	if( $mode == self::WORKFLOW_MODE_FACTORY )
    	{
    		if( $wd == NULL )
    			throw new e\NullAssetException( 
    				S_SPAN . c\M::NULL_WORKFLOW_DEFINITION . E_SPAN );
    		else
    		{
    			$this->getProperty()->workflowDefinitionId   = $wd->getId();
    			$this->getProperty()->workflowDefinitionPath = $wd->getPath();
    		}
    	}
    	else
    	{
			$this->getProperty()->workflowDefinitionId   = NULL;
			$this->getProperty()->workflowDefinitionPath = NULL;
    	}
    	
    	$this->getProperty()->workflowMode = $mode;
    	return $this;
    }
    
    private function processPlugins()
    {
        $this->plugins = array();

        $plugins = $this->getProperty()->plugins->plugin;
            
        if( !is_array( $plugins ) )
        {
            $plugins = array( $plugins );
        }
        
        $count = count( $plugins );
        
        for( $i = 0; $i < $count; $i++ )
        {
            $this->plugins[] = 
                new p\Plugin( $plugins[ $i ] );
        }
    }
    
    private $plugins;
}
?>
