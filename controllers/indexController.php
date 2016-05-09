<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Default controller class for module account
 * 
 */

Namespace Bang\Modules\Directory;

Use Bang\Modules\Directory\Models\Db,
	Bang\Modules\Directory\Models\Mail,
    Bang\Helper;

class indexController extends \Bang\SuperController implements \Bang\ControllerInterface
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
    
    public function tweedleAction()
    {
    	$post = Helper::getRequestParams('post'); 
    	
    	if(isset($post['fastadd']) && !empty($post['fastadd'])) {
    		
    		$cats = explode(PHP_EOL, $post['fastadd']);
    		
    		if(is_array($cats) && count($cats)) {
    			
    			foreach($cats AS $key => $value) {
    				
    				$route = explode('/', $value);
    				
    				if(is_array($route) && count($route)) {
    					
    					$this->Data->tryToAddFastRoute($route, $post['rootid']);
    				}
    			}
    		}
    	}
    	
    	$treedata = $this->Data->getTreeById(1);
    	
    	if(is_array($treedata) && count($treedata)) {
    		$list = $this->generateNestableList($treedata);
    		
    		$this->View->setTplVar('menu', $list);
    	}
    	
    	$template = '';
    	 
    	if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tweed.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tweed.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tweed.php');
    	}
    	
    	print $template;
    	
    	die('<br />I am dead right here');
    }
    
    /**
     * Edit a node/sub category
     */
    public function editnodeAction()
    {
    	$get = Helper::getRequestParams('get');
    	
    	if(is_array($get) && isset($get['nodeid']) && (int)$get['nodeid'] > 0 && isset($get['rootid']) && (int)$get['rootid'] > 0) {
    		
    		$this->View->setTplVar('rootid', (int)$get['rootid']);
    		
    		$rootInfo = $this->Data->getCategoryInfoById((int)$get['rootid']);
    		$this->View->setTplVar('rootInfo', $rootInfo);
    		
    		$templates = $this->Data->getMainTemplates();
    		$this->View->setTplVar('templates', $templates);
    		
    		$frontGroups = $this->Data->getFrontendPermissionGroups();
    		$this->View->setTplVar('frontgroups', $frontGroups);
    		
    		
    		$node = $this->Data->getCategoryInfoById((int)$get['nodeid']);
    		
    		if($node !== false) {
    			$this->View->setTplVar('node', $node);
    		}
    		
    		$treedata = $this->Data->getTreeById((int)$get['rootid']);
    		
    		$menu = $treedata;
    		
    		// add root to tree
    		if(is_array($treedata)) {
    			 
    			array_unshift($treedata, array('name' => 'Root', 'level' => 1, 'id' => $get['rootid']));
    			 
    		} else {
    			 
    			$treedata = array(0 => array('name' => 'Root', 'level' => 1, 'id' => $get['rootid']));
    		}
    		
    		$this->View->setTplVar('treedata', $treedata);
    		
    		$template = '';
    		
    		if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editnode.php')) {
    			$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editnode.php');
    		} else {
    			$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'editnode.php');
    		}
    		
    		// main template
    		$this->View->setModuleTpl('editnode', $template);
    		
    	} else {
    		
    		// no data given
    	}
    	
    	
    }
    
    /**
     * Remove nodeid from nested set tree and redirect back to listing
     */
    public function removeNodeAction()
    {
    	$params = Helper::getRequestParams('get');
    	
    	if(is_array($params) && isset($params['rootid']) && (int)$params['rootid'] > 0) {
    		
    		if(isset($params['nodeid']) && (int)$params['nodeid'] > 0) {
    		
    			if($this->Data->deleteBranchByIdController((int)$params['nodeid'], (int)$params['rootid']) === true) {
    			
    				$this->Session->setSuccess($this->Lang->get('directory_remove_node_success'));
    				Helper::redirectTo('/directory/index/tree/rootid/'.(int)$params['rootid'].'/');
    				
    			} else {
    				
    				$this->Session->setError($this->Lang->get('directory_remove_node_failed'));
    				Helper::redirectTo('/directory/index/tree/rootid/'.(int)$params['rootid'].'/');
    			}
    			
    		} else {
    			
    			$this->Session->setError($this->Lang->get('directory_remove_node_noid'));
    			Helper::redirectTo('/directory/index/tree/rootid/'.(int)$params['rootid'].'/');
    		}
    		
    	} else {
    		
    		$this->Session->setError($this->Lang->get('directory_remove_node_nodata'));
    		Helper::redirectTo('/directory/index/index/');
    	}
    }
    
    /**
     * Remove a category and all dependencies
     */
    public function removecatAction()
    {
    	$get = Helper::getRequestParams('get');
    	 
    	if(isset($get['catid']) && (int)$get['catid'] > 0) {
    		
    		if($this->Data->removeCategory((int)$get['catid']) === true) { 
    			
    			$this->Session->setSuccess($this->Lang->get('directory_remove_category_success'));
    			Helper::redirectTo('/directory/index/index/');
    			
    		} else {
    			
    			$this->Session->setSuccess($this->Lang->get('directory_remove_category_fail'));
    			Helper::redirectTo('/directory/index/index/');
    		}
    		
    	} else {
    		
    		$this->Session->setSuccess($this->Lang->get('directory_remove_noid'));
    		Helper::redirectTo('/directory/index/index/');
    	}
    }
    
    /**
     * Index Action - the default entry point to this controller
     * An API may uses more explecit methods to let users access the data
     * @see \Bang\controllerInterface::indexAction()
     */
    public function indexAction()
    {
    	$cats = $this->Data->getRootCategories();
    	
    	if($cats !== false) {
    		$this->View->setTplVar('roots', $cats);
    	}
    	
    	// In this blog, it looks for an overwrite template in the template folder
    	// if found, it will load this template instead of the internal module template.
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'list.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'list.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'list.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('list', $template);
    }
    
    /**
     * Show a directory tree
     */
    public function treeAction()
    {
    	$get = Helper::getRequestParams('get');
    	
    	if(is_array($get) && count($get) && isset($get['rootid']) && (int)$get['rootid'] > 0) {
    		
    		$this->View->setTplVar('rootid', (int)$get['rootid']);
    		
    		$rootInfo = $this->Data->getCategoryInfoById((int)$get['rootid']);
    		$this->View->setTplVar('rootInfo', $rootInfo);
    		
    		$templates = $this->Data->getMainTemplates();
    		$this->View->setTplVar('templates', $templates);
    		
    		$frontGroups = $this->Data->getFrontendPermissionGroups();
    		$this->View->setTplVar('frontgroups', $frontGroups);
    		
    		$treedata = $this->Data->getTreeById((int)$get['rootid']);
    		
    		$menu = $treedata;
    		
    		// add root to tree
    		if(is_array($treedata)) {
    			
    			array_unshift($treedata, array('name' => 'Root', 'level' => 1, 'id' => $get['rootid']));
    			
    		} else {
    			
    			$treedata = array(0 => array('name' => 'Root', 'level' => 1, 'id' => $get['rootid']));
    		}
    		
    		$this->View->setTplVar('treedata', $treedata);
    		
    		if(is_array($menu) && count($menu)) {
    			$list = $this->generateNestableList($menu);
    			$this->View->setTplVar('menu', $list);
    		}
    		
    	} else {
    		
    		$this->Session->setError($this->Lang->get('directory_new_category_form_nodata'));
    		Helper::redirectTo('/directory/index/index/');
    	}
    	
    	// In this blog, it looks for an overwrite template in the template folder
    	// if found, it will load this template instead of the internal module template.
    	$template = '';
    	 
    	if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tree.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tree.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'tree.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('list', $template);
    }
    
    /**
     * Update a category
     */
    public function processeditcategoryAction()
    {
    	$params = Helper::getRequestParams('post');
    	
    	if(is_array($params) && count($params)) {
    		 
    		$this->Session->setPostData($params);
    	
    		if($this->Data->updateCategoryMetaData($params) === true) {
    	
    			$this->Session->clearFormData();
    			 
    			$this->Session->setSuccess($this->Lang->get('directory_new_category_form_success'));
    			Helper::redirectTo('/directory/index/index/');
    	
    		} else {
    			// deflect the redirection depending on availability of rootid
    			if(isset($params['rootid']) && (int)$params['rootid'] > 0) {
    				Helper::redirectTo('/directory/index/editcategory/'.(int)$params['rootid']);
    			} else {
    				Helper::redirectTo('/directory/index/index/');
    			}
    		}
    		 
    	} else {
    	
    		$this->Session->setError($this->Lang->get('directory_new_category_form_nodata'));
    		Helper::redirectTo('/directory/index/editcategory/');
    	}
    	
    	return false;
    }
    
    /**
     * Edit a root category/menu
     */
    public function editcategoryAction()
    {
    	$params = Helper::getRequestParams('get');
    	
    	if(is_array($params) && isset($params['rootid']) && (int)$params['rootid'] > 0) {

    		$templates = $this->Data->getMainTemplates();
    		$this->View->setTplVar('templates', $templates);
    		 
    		$cat = $this->Data->getCategoryInfoById($params['rootid']);
    		
    		$this->View->setTplVar('cat', $cat);
    		
    	} else {
    		
    		$this->Session->setError($this->Lang->get('directory_new_category_form_nodata'));
    		Helper::redirectTo('/directory/index/index/');
    		// no rootid given
    	}
    	 
    	// In this blog, it looks for an overwrite template in the template folder
    	// if found, it will load this template instead of the internal module template.
    	$template = '';
    	
    	if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edit.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edit.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'edit.php');
    	}
    	
    	// main template
    	$this->View->setModuleTpl('directory', $template);
    }
    
    
    /**
     * Process the create category form
     * A category is the root of a new nested set tree
     * Depending on application one need only one or multiple of those
     */
    public function processnewcategoryAction()
    {
    	$params = Helper::getRequestParams('post');
    	 
    	if(is_array($params) && count($params)) {
    			
    		$this->Session->setPostData($params);
    		
    		if($this->Data->newRootCategory($params) === true) {
				
    			$this->Session->clearFormData();
    			
    			$this->Session->setSuccess($this->Lang->get('directory_new_category_form_success'));
    			Helper::redirectTo('/directory/index/index/');
    			 
    		} else {

    				// error should be set already
    			Helper::redirectTo('/directory/index/newcategory/');
    		}
    	
    	} else {

    		$this->Session->setError($this->Lang->get('directory_new_category_form_nodata'));
    		Helper::redirectTo('/directory/index/newcategory/');
    		exit;
    	}
    	 
    	return false;
    }
    
    /**
     * Add new category action
     */
    public function newcategoryAction()
    {
    	// set a variable to use in the template
    	$this->View->setTplVar('test', 'Set to tpl vars and dispatched');
    	 
    	$templates = $this->Data->getMainTemplates();
    	$this->View->setTplVar('templates', $templates);
    	
    	// In this blog, it looks for an overwrite template in the template folder
    	// if found, it will load this template instead of the internal module template.
    	$template = '';
    	 
    	if(file_exists($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'new.php')) {
    		$template = $this->View->loadTemplate($this->Overwrite.'directory'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'new.php');
    	} else {
    		$template  = $this->View->loadTemplate($this->path.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'new.php');
    	}
    	 
    	// main template
    	$this->View->setModuleTpl('directory', $template);
    }
    
    /**
     * Generate a menu list with nestable classes
     * @param array $data
     */
    public function generateNestableList ($data) 
    {
    	if(!isset($data[0]['Control'])) {
    		return false;
    	}
    	
    	$currentLang = $this->Session->getUserLang();
    	
    	$class  = NULL;
    	$listId = NULL;
    	$i      = 0;
    
    	$result     = '';
    	$result     .= "<ul data-rootid=\"".$data[0]['rootid']."\" class=\"uk-nestable treeroot\" data-uk-nestable=\"{handleClass:'uk-nestable-handle'}\">\n";
    	$depth      = $data[0]['level'];
    	$temproot   = '';
    	$finalright = '';
    	$crumbs     = '';
    
    	foreach ($data as $node) {
    		$i ++;
    		
    		// Get name from language grid
    		$node_name = $node['name'];
    		
    		if(isset($node['langstrings']) && is_array($node['langstrings'])) {
    			if(isset($node['langstrings'][$currentLang]) && isset($node['langstrings'][$currentLang]['node_name'])) {
    				if(isset($node['langstrings'][$currentLang]['node_name']['textvalue'])) {
    					$node_name = $node['langstrings'][$currentLang]['node_name']['textvalue'];
    				}
    			}
    		}
    
    		$pageLink = "<a class=\"mytruncate\" href=\"javascript:void(0);\">" . htmlspecialchars(trim($node_name), ENT_QUOTES, 'UTF-8') . "</a>\n";
    
    		if ($depth < $node['level']) {
    			
    			$result .= "\n<ul>\n";
    			
    		} elseif ($depth == $node['level'] && $depth > $data[0]['level']) {
    			
    			$result .= "</li>\n";
    			
    		} elseif ($depth > $node['level']) {
    			
    			for ($i = 0; $i < ($depth - $node['level']); $i ++) {
    				$result .= "</li></ul>\n";
    			}
    		}
    
    		$result .= "<li data-id=\"".$node['id']."\" class=\"uk-nestable-item\">
					<div class=\"uk-nestable-panel\">
						<i class=\"uk-nestable-handle uk-icon mdi-action-reorder uk-margin-small-right\"></i>\n".
    		$pageLink."<span style=\"float: right;\">
    				
    				<a class=\"tree-toolbar edit\" title=\"".$this->Lang->get('directory_tree_toolbar_delete')."\" href=\"/directory/index/editnode/nodeid/".(int)$node['id']."/rootid/".(int)$node['rootid']."/\" >
    						<i class=\"uk-icon mdi-editor-mode-edit uk-margin-small-right\"></i>
    				</a>
    				
    				<a class=\"tree-toolbar delete\" title=\"".$this->Lang->get('directory_tree_toolbar_delete')."\" href=\"/directory/index/removenode/nodeid/".(int)$node['id']."/rootid/".(int)$node['rootid']."/\" >
    						<i class=\"uk-icon mdi-action-delete uk-margin-small-right\"></i>
    				</a>
    						
    						
    				</span></div>\n";
    
    					$depth = $node['level'];
    
    					if ($node['Control'] == 'open') {
    						$temproot = $node['id'];
    						$finalright = $node['rgt'];
    					}
    
    					if ($finalright - 1 == $node['rgt']) {
    						$temproot = '';
    						$finalright = '';
    						$crumbs = '';
    					}
    
    					$class = NULL;
    	}
    
    	$result .= "</li></ul>\n";
    	$result .= "</ul>\n";
    
    	return $result;
    }
    
    /**
     * Generate a clean ul list
     * @param array $data
     * @return string
     */
    public function generateCleanList (array $data) 
    {
    	$currentLang = $this->Session->getUserLang();
    	
    	$class  = NULL;
    	$listId = NULL;
    	$i      = 0;
    
    	$result     = '';
    	$result     .= '<ul>';
    	$depth      = $data[0]['level'];
    	$temproot   = '';
    	$finalright = '';
    	$crumbs     = '';
    
    	foreach ($data as $node) {
    		$i ++;
    		
    		$node_name = $node['name'];
    		
    		if(isset($node['langstrings']) && is_array($node['langstrings'])) {
    			if(isset($node['langstrings'][$currentLang]) && isset($node['langstrings'][$currentLang]['node_name'])) {
    				if(isset($node['langstrings'][$currentLang]['node_name']['textvalue'])) {
    					$node_name = $node['langstrings'][$currentLang]['node_name']['textvalue'];
    				}
    			}
    		}
    
    		$pageLink = '<a href="javascript:void(0);">' . htmlspecialchars(trim($node_name), ENT_QUOTES, 'UTF-8') . '</a>';
    
    		if ($depth < $node['level']) {
    			$result .= "\n<ul>\n";
    		} elseif ($depth == $node['level'] && $depth > $data[0]['level']) {
    			$result .= "</li>\n";
    		} elseif ($depth > $node['level']) {
    			for ($i = 0; $i < ($depth - $node['level']); $i ++) {
    				$result .= "</li></ul>\n";
    			}
    		}
    
    		$result .= '<li>'.$pageLink;
			$depth = $node['level'];
    
			if ($node['Control'] == 'open') {
				$temproot = $node['id'];
				$finalright = $node['rgt'];
			}
    
			if ($finalright - 1 == $node['rgt']) {
				$temproot = '';
				$finalright = '';
				$crumbs = '';
				}
    
			$class = NULL;
    	}
    
    	$result .= "</li></ul>\n";
    	$result .= "</ul>\n";
    
    	return $result;
    }
    
    
    /**
     * Permission test
     * 1. Test if user is logged in
     */
    public function testPermisions()
    {
    	// 1. if user is not logegd in, redirect to login with message
    	if($this->Session->loggedIn() === false) {
    		$this->Session->setError($this->Lang->get('application_notlogged_in'));
    		Helper::redirectTo('/account/index/login/');
    		exit;
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
