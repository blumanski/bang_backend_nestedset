<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Ajax controller for the javascript interface
 * 
 */

Namespace Bang\Modules\Directory;

Use Bang\Modules\Directory\Models\Db,
	Bang\Modules\Directory\Models\Mail,
	Bang\Tools\PusherWrapper,
    Bang\Helper;

class ajaxController extends \Bang\SuperController implements \Bang\ControllerInterface
{
	/**
	 * Modules DB Model
	 * @var object
	 */
	private $Data;
	
	/**
	 * View object
	 * @var object
	 */
	private $View;
	
	/**
	 * ErrorLog object
	 * @var object
	 */
	private $ErrorLog;
	
	/**
	 * Instance of Language object
	 * @var object
	 */
	private $Lang;
	
	/**
	 * Keeps the template overwrite folder path
	 * If a template is available in the template folder, it allows
	 * to load that template instead of the internal one.
	 * So, one can easily change templates from the template without touching
	 * the module code.
	 * @var string
	 */
	private $Overwrite;
	
	/**
	 * Instance of Mail object
	 * @var object
	 */
	private $Mail;
	
	/*
	 * Set up the class environment
	 * @param object $di
	 */
    public function __construct(\stdClass $di)
    {
        $this->path     		= dirname(dirname(__FILE__));
    	// assign class variables
    	$this->ErrorLog 		= $di->ErrorLog;
    	$this->View				= $di->View;
    	$this->Session          = $di->Session;
    	$this->Lang				= $di->View->Lang;
    	
    	$this->Pusher			= new PusherWrapper();
    	
    	// Get the current language loaded
    	$currentLang = $this->View->Lang->LangLoaded;
    	
    	$this->Overwrite = $this->getBackTplOverwrite();
    	
    	// Add module language files to language array
    	$this->View->Lang->addLanguageFile($this->path.'/lang/'.$currentLang);
    	$this->View->addStyle($this->View->TemplatePath.'min/css/directory/assets/scss/directory.min.css', 0);

    	$this->testPermisions();
    	
    	// create module data model instance
    	$this->Data	= new Db($di);
    	$this->Mail	= new Mail($di);
    	$this->Data->setMailInstance($this->Mail);
    }
    
    /**
     * Index Action - the default entry point to this controller
     * An API may uses more explecit methods to let users access the data
     * @see \Bang\controllerInterface::indexAction()
     */
    public function indexAction()
    {
		die(json_encode(array('message' => 'No endpoint.')));
    }
    
    /**
     * Move a node to a new position
     */
    public function movenodeAction()
    {
    	$post = Helper::getRequestParams('post');
    	$post = Helper::prepareAjaxValues($post);
    		
		if(isset($post['id']) && (int)$post['id'] > 0 && isset($post['rootid']) && (int)$post['rootid'] > 0) {
    			 
			if(isset($post['serialized']) && !empty($post['id'])) {
    				 
				$target = $this->Data->moveNestedSetNode($post['serialized'], $post['rootid']);
				if($target === true) {

					// trigger pusher notification, only if pusher config data is set
					if(isset(CONFIG['pusher']) && isset(CONFIG['pusher']['appid']) && !empty(CONFIG['pusher']['appid'])) {
						
						$identifier = substr(md5($this->Session->getUserEmail()), 0, 12);
						
						$data['message'] = $this->Lang->get('directory_pusher_refresh_tree');
						$data['identifier'] = $identifier;
						$this->Pusher->triggerEvent(CONFIG['pusher']['mainchannel'], 'category_change', $data);
						
					}
					// pusher end
					
					die(json_encode(array('outcome' => 'success', 'message' => 'in move node methos')));
    					
				} else {
					
					die(json_encode(array('outcome' => 'false', 'message' => 'in move node methos')));
				}
			}
		}
    }
    
    /**
     * Update a nodes data
     */
    public function updatenodeAction()
    {
    	$params = Helper::getRequestParams('post');
    	$params = Helper::prepareAjaxValues($params);
    	
    	if(is_array($params) && count($params) && isset($params['rootid']) && (int)$params['rootid'] > 0) {
    		
    		if(isset($params['nodeid']) && (int)$params['nodeid'] > 0) {
    		
    	
	    		if(isset($params['groups'])) {
	    			if(Helper::isJson($params['groups']) === true) {
	    				$params['groups'] = json_decode($params['groups'], true);
	    			}
	    		}
	    	
	    		if($this->Data->nestUpdateNodeConrtoller($params) === true) {
	    			die(json_encode(array('outcome' => 'success', 'message' => 'success')));
	    			 
	    		} else {
	    			 
	    			$error = $this->Session->getError();
	    			die(json_encode(array('outcome' => 'error','message' => $error)));
	    		}
    		
    		} else {
    			 
    			// no nodeid
    		}
    	
    	} else {
    	
    		die(json_encode(array('outcome' => 'error','message' => 'No data...')));
    	}
    }
    
    /**
     * Add a new node to a tree
     */
    public function addNodeAction()
    {
    	$params = Helper::getRequestParams('post');
    	$params = Helper::prepareAjaxValues($params);
		
    	if(is_array($params) && count($params) && isset($params['rootid']) && (int)$params['rootid'] > 0) {
    		
    		if(isset($params['groups'])) {
    			if(Helper::isJson($params['groups']) === true) {
    				$params['groups'] = json_decode($params['groups'], true);
    			}
    		}
    		
    		$response = $this->Data->nestAddNodeConrtoller($params);
    		
    		if((int)$response > 0) {
    			
    			die(json_encode(array('outcome' => 'success', 'message' => 'success')));
    			
    		} else {
    			
    			$error = $this->Session->getError();
    			die(json_encode(array('outcome' => 'error','message' => $error)));
    		}
    		
    	} else {
    		
    		die(json_encode(array('outcome' => 'error','message' => 'No data...')));
    	}
    	
    }
  
    /**
     * Permission test
     * 1. Test if user is logged in
     */
    public function testPermisions()
    {
    	// 1. if user is not logegd in, redirect to login with message
    	if($this->Session->loggedIn() === false || $this->Session->hasPermission(1) !== true) {
    		die(json_encode(array('outcome' => 'error', 'message' => 'No permisions')));
    	}
    	
    	if(Helper::isAjax() === false) {
    		die(json_encode(array('outcome' => 'error', 'message' => 'Ajax request only.')));
    	}
    }
  
    /**
     * Must be in all classes
     * @return array
     */
    public function __debugInfo() {
    
    	$reflect	= new \ReflectionObject($this);
    	$varArray	= array();
    
    	foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
    		$propName = $prop->getName();
    		 
    		if($propName !== 'DI') {
    			//print '--> '.$propName.'<br />';
    			$varArray[$propName] = $this->$propName;
    		}
    	}
    
    	return $varArray;
    }
}
