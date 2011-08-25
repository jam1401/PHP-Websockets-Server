<?php

require_once "wsexceptions.class.php";

   class AppFactory { 
    public static function create($appName, $params = NULL) { 
        if(class_exists($appName)) { 
            if($params == NULL)     
                return new $appName(); 
            else { 
                $obj = new ReflectionClass($appName); 
                return $obj->newInstanceArgs($params); 
            }     
        } 
         
        throw new WSAppNotInstalled("Class [ $appName ] not found..."); 
    } 

    /* 
     * This is the autoload, so no need to require all classes 
     * And it throws exception if there's no such file 
     * @param string $className 
     * @author Roy 
     */     
    public static function autoload($appName) { 
    if(file_exists($file = "$appName.class.php")) 
        require_once $file; 
    elseif(file_exists($file2 = "./$appName/$file")) 
        require_once $file2;     
    else 
        throw new WSAppNotInstalled("File [ $appName.class.php ] not found...");         
    } 
} 
?>