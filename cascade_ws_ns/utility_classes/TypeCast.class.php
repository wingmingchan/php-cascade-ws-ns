<?php 
class TypeCast
{
	public static function cast( $old_object, $new_classname )
	{
		if( class_exists( $new_classname ) ) 
		{
			// Example serialized object segment
			// O:5:"field":9:{s:5:...   <--- Class: Field
			$old_serialized_prefix  = "O:" . strlen( get_class( $old_object ) );
			$old_serialized_prefix .= ":\"" . get_class( $old_object ) . "\":";

			$old_serialized_object = serialize( $old_object );
			$new_serialized_object = 'O:' . strlen( $new_classname ) . ':"' . $new_classname . '":';
			$new_serialized_object .= substr( $old_serialized_object, strlen( $old_serialized_prefix ) );
	   		return unserialize( $new_serialized_object );
	  	}
	  	else
	  	{
	   		return false;
		}
	}
}
?>
