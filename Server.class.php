<?php
/**
 * class Server
 * 
 *    .oooooo..o                                                   
 *  d8P'    `Y8                                                   
 *  Y88bo.       .ooooo.  oooo d8b oooo    ooo  .ooooo.  oooo d8b 
 *   `"Y8888o.  d88' `88b `888""8P  `88.  .8'  d88' `88b `888""8P 
 *       `"Y88b 888ooo888  888       `88..8'   888ooo888  888     
 *  oo     .d8P 888    .o  888        `888'    888    .o  888     
 *  8""88888P'  `Y8bod8P' d888b        `8'     `Y8bod8P' d888b                                                     
 * 
 * Handles client output and long tasks running after the client has been served.
 * 
 *                                     oooo   o8o  oooo                           .   oooo         o8o           
 *                                     `888   `"'  `888                         .o8   `888         `"'           
 * oooo  oooo   .oooo.o  .ooooo.        888  oooo   888  oooo   .ooooo.       .o888oo  888 .oo.   oooo   .oooo.o 
 * `888  `888  d88(  "8 d88' `88b       888  `888   888 .8P'   d88' `88b        888    888P"Y88b  `888  d88(  "8 
 *  888   888  `"Y88b.  888ooo888       888   888   888888.    888ooo888        888    888   888   888  `"Y88b.  
 *  888   888  o.  )88b 888    .o       888   888   888 `88b.  888    .o        888 .  888   888   888  o.  )88b 
 *  `V88V"V8P' 8""888P' `Y8bod8P'      o888o o888o o888o o888o `Y8bod8P'        "888" o888o o888o o888o 8""888P' 
 * 
 * @uses
 * 
 * 	First register output functions:
 * 
 *		e.g. Server::registerOutputFunction($anObject, 'aFunctionThatEchoesTheResponse');
 *
 *
 *	Second register functions which need to be completed before the response is final:
 *
 *		e.g. Server::registerSyncFunction('session_write_close');
 *
 *
 *	Third register "long-running" functions (e.g. > 100ms) which have to be executed but should not affect the server's response time to a client request.
 *
 *		e.g. Server::registerBackgroundFunction($anotherObject, 'aLongRunningFunction');
 *
 *
 *	Lastly, run everything:
 *
 *		Server::end();
 * 
 * 
 * 
 * @category Classes
 * @package Classes
 * @author Robert Biehl <robert.biehl@fashionfreax.net>
 * @copyright Copyright (c) 2011, Empora GmbH
 * 
 * @license http://www.gnu.org/licenses/gpl.html
 * 
 * @link	https://github.com/RobertBiehl/php-background-server
 * 
*/
class Server{
	
	private static $outputFunctions = array();
	private static $syncFunctions = array();
	private static $backgroundFunctions = array();
	
	public static function registerOutputFunction($object, $functionName=NULL){
		if(!$functionName)
			self::$outputFunctions[] = $object;
		else
			self::$outputFunctions[] = array(
				'object' => $object,
				'functionName' => $functionName
			);
	}
	
	public static function registerSyncFunction($object, $functionName=NULL){
		if(!$functionName)
			self::$syncFunctions[] = $object;
		else
			self::$syncFunctions[] = array(
				'object' => $object,
				'functionName' => $functionName
			);
	}
	
	public static function registerBackgroundFunction($object, $functionName=NULL){
		if(!$functionName)
			self::$backgroundFunctions[] = $object;
		else
			self::$backgroundFunctions[] = array(
				'object' => $object,
				'functionName' => $functionName
			);
	}
	
	public static function end(){
		
//...........................................................
															//	       .o   .o
		ob_end_clean();										//	      .8'  .8'
		//header("Content-Encoding: none\r\n");				//	  .888888888888'	OPENS
		ignore_user_abort(true);	// optional				//	    .8'  .8'		RESPONSE
		ob_start();					// OUTER				//	.888888888888'		BUFFER
		if(!ob_start("ob_gzhandler"))						//	  .8'  .8'
			ob_start();				// INNER				//	 .8'  .8'
//...........................................................
//
//		                          .                              .   
//		                        .o8                            .o8   
//		 .ooooo.  oooo  oooo  .o888oo oo.ooooo.  oooo  oooo  .o888oo 
//		d88' `88b `888  `888    888    888' `88b `888  `888    888   
//		888   888  888   888    888    888   888  888   888    888  
//		888   888  888   888    888 .  888   888  888   888    888 . 
//		`Y8bod8P'  `V88V"V8P'   "888"  888bod8P'  `V88V"V8P'   "888" 
//		                               888                           
//		                              o888o     
//
//		Runs tasks providing the actual output to the client.                     
		foreach(self::$outputFunctions as $task){
			is_array($task) ? call_user_func(array($task['object'], $task['functionName'])) : call_user_func($task);
		}
		
//		                                                  .                      oooo                 
//		                                                .o8                      `888                 
//		 .oooo.o oooo    ooo ooo. .oo.    .ooooo.     .o888oo  .oooo.    .oooo.o  888  oooo   .oooo.o 
//		d88(  "8  `88.  .8'  `888P"Y88b  d88' `"Y8      888   `P  )88b  d88(  "8  888 .8P'   d88(  "8 
//		`"Y88b.    `88..8'    888   888  888            888    .oP"888  `"Y88b.   888888.    `"Y88b.  
//		o.  )88b    `888'     888   888  888   .o8      888 . d8(  888  o.  )88b  888 `88b.  o.  )88b 
//		8""888P'     .8'     o888o o888o `Y8bod8P'      "888" `Y888""8o 8""888P' o888o o888o 8""888P' 
//		         .o..P'                                                                               
//		         `Y8P'                                                                                
//
//		Runs tasks which need to be finished before the response is sent (e.g. writing session data)

		foreach(self::$syncFunctions as $task){
			is_array($task) ? call_user_func(array($task['object'], $task['functionName'])) : call_user_func($task);
		}
		
//...........................................................		
															//	       .o   .o 
		ob_end_flush();	$size = ob_get_length();			//	      .8'  .8'   
		header("Content-Length: $size\r\n\r\n");			//	  .888888888888'	CLOSES
		ob_end_flush();	// Strange behaviour, will not		//	    .8'  .8'		THIS
		flush();		// work unless both are called!		//	.888888888888'		RESPONSE
		ob_end_clean();										//	  .8'  .8'
															//	 .8'  .8'
//...........................................................
//
//		 .o8                           oooo                                                                    .o8         .                      oooo                 
//		"888                           `888                                                                   "888       .o8                      `888                 
//		 888oooo.   .oooo.    .ooooo.   888  oooo   .oooooooo oooo d8b  .ooooo.  oooo  oooo  ooo. .oo.    .oooo888     .o888oo  .oooo.    .oooo.o  888  oooo   .oooo.o 
//		 d88' `88b `P  )88b  d88' `"Y8  888 .8P'   888' `88b  `888""8P d88' `88b `888  `888  `888P"Y88b  d88' `888       888   `P  )88b  d88(  "8  888 .8P'   d88(  "8 
//		 888   888  .oP"888  888        888888.    888   888   888     888   888  888   888   888   888  888   888       888    .oP"888  `"Y88b.   888888.    `"Y88b.  
//		 888   888 d8(  888  888   .o8  888 `88b.  `88bod8P'   888     888   888  888   888   888   888  888   888       888 . d8(  888  o.  )88b  888 `88b.  o.  )88b 
//		 `Y8bod8P' `Y888""8o `Y8bod8P' o888o o888o `8oooooo.  d888b    `Y8bod8P'  `V88V"V8P' o888o o888o `Y8bod88P"      "888" `Y888""8o 8""888P' o888o o888o 8""888P' 
//		                                           d"     YD                                                                                                           
//		                                           "Y88888P'            
		
		foreach(self::$backgroundFunctions as $task){
			is_array($task) ? call_user_func(array($task['object'], $task['functionName'])) : call_user_func($task);
		}
	}
}