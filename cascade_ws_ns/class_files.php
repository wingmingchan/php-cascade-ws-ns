<?php
use cascade_ws_utility as utility;
/*
use cascade_ws_constants as constants;
use cascade_ws_asset     as asset;
use cascade_ws_exception as exception;
*/
spl_autoload_register('cascade__autoload');
function cascade__autoload( $classname )
{
	$array =
		utility\StringUtility::getExplodedStringArray( "\\", $classname );
	$size = count( $array );
	
	if( $size > 0 )
		$classname = $array[ $size - 1 ];
    $root_path              = dirname( __FILE__ ) . '/';
    $asset_class_folder     = "asset_classes/";
    $helping_class_folder   = "property_classes/";
    $exception_class_folder = "exception_classes/";
    $utility_class_folder   = "utility_classes/";
    $file                   = "$classname.class.php";
    
    if( file_exists( $root_path . $asset_class_folder . $file ) )
        require_once( $root_path . $asset_class_folder . $file );
    else if( file_exists( $root_path . $exception_class_folder . $file ) )
        require_once( $root_path . $exception_class_folder . $file );
    else if( file_exists( $root_path . $helping_class_folder . $file ) )
        require_once( $root_path . $helping_class_folder . $file );
    else if( file_exists( $root_path . $utility_class_folder . $file ) )
        require_once( $root_path . $utility_class_folder . $file );
}