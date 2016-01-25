<?php
/**
  * Author: Wing Ming Chan
  * Copyright (c) 2014 Wing Ming Chan <chanw@upstate.edu>
  * MIT Licensed
  * Modification history:
  * 5/28/2015 Added namespaces.
  * 9/23/2014 Fixed a bug in isMultiple.
  * 7/1/2014 Added getStructuredData.
 */
namespace cascade_ws_asset;

use cascade_ws_constants as c;
use cascade_ws_AOHS as aohs;
use cascade_ws_utility as u;
use cascade_ws_exception as e;
use cascade_ws_property as p;

class DataDefinition extends ContainedAsset
{
    const DEBUG     = false;
    const TYPE      = c\T::DATADEFINITION;
    const DELIMITER = ';';

    /**
    * The constructor
    * @param $service the AssetOperationHandlerService object
    * @param $identifier the identifier object
    */
    public function __construct( 
    	aohs\AssetOperationHandlerService $service, \stdClass $identifier )
    {
        parent::__construct( $service, $identifier );
        $this->xml             = $this->getProperty()->xml;
        $this->attributes      = array();
        $this->structured_data = new \stdClass();
        
        // process the xml
        $this->processSimpleXMLElement( new \SimpleXMLElement( $this->xml ) );
        // fully qualified identifiers
        $this->identifiers = array_keys( $this->attributes );
        
        // create the structured data
        $this->createStructuredData( new \SimpleXMLElement( $this->xml ) );
    }
    
    public function display()
    {
        $xml_string = u\XMLUtility::replaceBrackets( $this->xml );
        
        echo S_H2 . "XML" . E_H2 .
             S_PRE . $xml_string . E_PRE . HR;
        echo S_H2 . "Attributes" . E_H2 . S_PRE;
        var_dump( $this->attributes );
        echo E_PRE . HR;
        
        return $this;
    }
    
    public function displayAttributes()
    {
        echo S_H2 . "Attributes" . E_H2 . S_PRE;
        var_dump( $this->attributes );
        echo E_PRE . HR;
        
        return $this;
    }
    
    public function displayXml( $formatted=true )
    {
        if( $formatted )
        {
            $xml_string = u\XMLUtility::replaceBrackets( $this->xml );
            echo S_H2 . "XML" . E_H2 . S_PRE;
        }

        echo $xml_string;
        
        if( $formatted )
             echo E_PRE . HR;
        
        return $this;
    }
    
    public function edit()
    {
        $asset = new \stdClass();
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

    public function getField( $field_name )
    {
        if( !in_array( $field_name, $this->identifiers ) )
            throw new e\NoSuchFieldException(
                S_SPAN . "The field name $field_name does not exist." . E_SPAN );

        return $this->attributes[ $field_name ];
    }
    
    public function getIdentifiers()
    {
        return $this->identifiers;
    }
    
    public function getStructuredData()
    {
    	return $this->structured_data;
    }
    
    public function getXml()
    {
        return $this->xml;
    }

    public function hasField( $field_name )
    {
        return $this->hasIdentifier( $field_name );
    }
    
    public function hasIdentifier( $field_name )
    {
        return ( in_array( $field_name, $this->identifiers ) );
    }
    
    public function isMultiple( $field_name )
    {
        if( !in_array( $field_name, $this->identifiers ) )
        {
            throw new e\NoSuchFieldException( 
                S_SPAN . "The field name $field_name does not exist." . E_SPAN );
        }
        
        if( isset( $this->attributes[ $field_name ][ 'multiple' ] ) ) 
        {
            return true;
        }
        else if( isset( $this->attributes[ $field_name ][ 0 ][ 'multiple' ] ) )
        {
            return true;
        }
        
        return false;
    }
    
    public function setXml( $xml )
    {
        $this->getProperty()->xml = $xml;
        $this->xml = $xml;
        $this->processSimpleXMLElement( new \SimpleXMLElement( $this->xml ) );

        return $this;
    }

    private function processSimpleXMLElement( $xml_element, $group_names='' )
    {
        foreach( $xml_element->children() as $child )
        {
            $type       = trim( $child->attributes()->{ $a = 'type' } );
            $name       = $child->getName();
            $identifier = $child[ 'identifier' ]->__toString();
            $old_group  = $group_names;
            
            if( $name == 'group' )
            {
            	// fully qualified identifier
                // if a field/group belongs to a group,
                // add the group name to the identifier
                $group_names    .= $identifier;
                $group_names    .= self::DELIMITER;
                $attributes      = $child->attributes();
                $attribute_array = array();
                // add the name
                $attribute_array[ 'name' ] = $name;

                // create the attribute array
                foreach( $attributes as $key => $value )
                {
                    $attribute_array[$key] = $value->__toString();
                }
                // store attributes
                $this->attributes[ trim( $group_names, self::DELIMITER ) ] = 
                	$attribute_array;
                // recursively process children
                $this->processSimpleXMLElement( $child, $group_names );
                
                // reset parent name for siblings
                $group_names = $old_group;
            }
            else
            {
                $value_string = '';
                
                // process checkbox, dropdown, radio, selector
                if( $name == 'text' && isset( $type ) && 
                	$type != 'datetime' && $type != 'calendar' )
                {
                    $item_name = '';
                
                    // if type is not defined, then normal, multi-line, wysiwyg
                    switch( $type )
                    {
                        case 'checkbox':
                        case 'dropdown':
                            $item_name = $type;
                            break;
                        case 'radiobutton':
                            $item_name = 'radio';
                            break;
                        case 'multi-selector':
                            $item_name = 'selector';
                            break;
                    }
                
                    $text = array();
                
                    foreach( $child->{$p = "$item_name-item"} as $item )
                    {
                        $text[] = $item->attributes()->{ $a = 'value' };
                    }
                    
                    $value_string = implode( self::DELIMITER, $text );
                }
            
                $attributes      = $child->attributes();
                $attribute_array = array();
                // add the name
                $attribute_array[ 'name' ] = $name;
                
                // attach items for checkbox, dropdown, radio, selector
                if( $value_string != '' )
                {
                    $attribute_array[ 'items' ] = $value_string;
                }
                // create the attribute array
                foreach( $attributes as $key => $value )
                {
                    $attribute_array[$key] = $value->__toString();
                }
                
                // add identifier/attribute array to $this->attributes
                // add the first item
                $this->attributes[ $group_names . $identifier ] = 
                	$attribute_array;
            }
        }
    }
    
    private function getStructuredDataNode( $xml_element, $type, $identifier )
    {
    	if( self::DEBUG ) { u\DebugUtility::out( "$type, $identifier" ); }
    	
    	$obj = AssetTemplate::getStructuredDataNode();
    	
    	if( $type == "group" )
    	{
    		$obj->type       = $type;
    		$obj->identifier = $identifier;
    		$obj->structuredDataNodes = new \stdClass();
    		
    		$child_count = count( $xml_element->children() );
    		$more_than_one = ( $child_count > 1 ? true : false );
    		
    		if( $more_than_one )
    		{
    			$obj->structuredDataNodes->structuredDataNode = array();
    			
				foreach( $xml_element->children() as $child )
				{
					$child_type = $child->getName();
					
    				if( self::DEBUG ) { u\DebugUtility::out( "Child type in group: $child_type" ); }
					
					if( isset( $child[ 'identifier' ] ) )
					{
						$child_identifier = $child[ 'identifier' ]->__toString();
						
						$child_std = $this->createChildStd( $child, $child_type, $child_identifier );
					
						$obj->structuredDataNodes->structuredDataNode[] = $child_std;
					}
				}
			}
			else
			{
				$xml_array  = $xml_element->children();
				
				//var_dump( $xml_array );
				
				$child      = $xml_array[ 0 ];
				$child_type = $child->getName();
				
    			if( self::DEBUG ) { u\DebugUtility::out( "Child type in group: $child_type" ); }
				
				$child_identifier = $child[ 'identifier' ]->__toString();
				$child_std = $this->createChildStd( $child, $child_type, $child_identifier );
				$obj->structuredDataNodes->structuredDataNode = $child_std;
			}
    	}
    	else
    	{
    		$obj->type       = $type;
    		$obj->identifier = $identifier;
    	}
    	
    	return $obj;
    }
    
    private function createStructuredData( $xml_element )
    {
    	$this->structured_data->definitionId   = $this->getId();
    	$this->structured_data->definitionPath = $this->getPath();
    	
    	$count = count( $xml_element->children() );
    	
    	if( $count > 1 )
    	{
			$this->structured_data->structuredDataNodes = new \stdClass();
    		$this->structured_data->structuredDataNodes->structuredDataNode = array();
    		
			foreach( $xml_element->children() as $child )
			{
				$child_type = $child->getName();
				
				if( isset( $child[ 'identifier' ] ) )
				{
					$child_identifier = $child[ 'identifier' ]->__toString();
					$child_std = $this->createChildStd( $child, $child_type, $child_identifier );
					$this->structured_data->structuredDataNodes->structuredDataNode[] = $child_std;
				}
			}
		}
		else
		{
			$child      = $xml_element->children();
			$child_type = $child->getName();
			$attributes = $child->attributes();
			
			if( isset( $attributes[ 'identifier' ] ) )
			{
				$child_identifier = $attributes[ 'identifier' ]->__toString();
                $this->structured_data->structuredDataNodes                     = new \stdClass();
                $this->structured_data->structuredDataNodes->structuredDataNode = new \stdClass();
				$this->structured_data->structuredDataNodes->structuredDataNode = 
					$this->createChildStd( $child, $child_type, $child_identifier );
			}
		}
    }
    
    private function createChildStd( $child, $child_type, $child_identifier )
    {
		$child_std = $this->getStructuredDataNode( $child, $child_type, $child_identifier );
		
		$grandchild = $child->children();
		
		if( isset( $grandchild ) )
		{
			$grandchild_type = $grandchild->getName();
			
			if( $grandchild_type == "checkbox-item" )
			{
				$child_std->text = p\StructuredDataNode::CHECKBOX_PREFIX;
			}
			else if( $grandchild_type == "selector-item" )
			{
				$child_std->text = p\StructuredDataNode::SELECTOR_PREFIX;
			}
		}
		
		if( $child_type == "asset" )
		{
			$child_attributes     = $child->attributes();
			$asset_type           = $child_attributes[ "type" ]->__toString();
			$child_std->assetType = $asset_type;
		}
    	return $child_std;
    }
    
    private $attributes;      // all attributes of each field
    private $identifiers;     // all identifiers of fields
    private $xml;             // the definition xml
    private $structured_data; // the corresponding structured data
}
?>
