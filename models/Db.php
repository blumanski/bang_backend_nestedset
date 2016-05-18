<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This class creates and handles tree structures using the nested set model.
 * The trees created can be used in many ways, as example I name menus and directories...
 * The module works without errors but some configuration and little bits can get improved.
 * 
 * @working fine but may have another look at it
 * Moving a branch into another branch
 * At the moment, I translate the nestable json tree coming from the frontend and loop through to update all
 * the items left, right, parent and levels. With a huge tree that takes some time.
 * That is a point which can improve for sure, it is a very sensitive area as it easy to stuff up the tree.
 * Well, this is not ideal but it works very reliable, Categories are not something that changes very often.
 * 
 * Pros: Works right now reliable
 * Cons: Many queries to update each item - 200 items = 200 queries
 * 
 * However, the other option would be to move the item with all children and update all other left and rights,
 * after that update the left and right of the moved item and children to put into the new spot.
 * 
 * Pros: Less queries, may 2 or three
 * Cons: High error source and may cant update parent and levels in database, which makes the front end handling easier
 * 
 * @review at later point of time
 *  
 *
 * NestedSet Model Data
 */

/**
 * @todo Need to finalize this class soon
 * remove experimental classes or finalize the new features such as fast add
 * @status Real work testing
 */

Namespace Bang\Modules\Directory\Models;

Use \Bang\PdoWrapper, 
	PDO, 
	\Bang\Helper, 
	Bang\Modules\Directory\Models\Mail;

class Db extends \Bang\SuperModel
{
    /**
     * PDO
     * @var object
     */
    private $PDO;
    
    /**
     * ErrorLog object
     * @var object
     */
    private $ErrorLog;
    
    /**
     * Session instance
     * @var object
     */
    private $Session;
    
    /**
     * instance of language object
     * @var object
     */
    private $Lang;
    
    /**
     * Mail instance
     * @var object
     */
    private $Mail;
    
    /**
     * Set up the db model
     * @param object $di
     */
    public function __construct(\stdClass $di)
    {
        $this->PdoWrapper	= $di->PdoWrapper;
        $this->ErrorLog		= $di->ErrorLog;
        $this->Session		= $di->Session;
        $this->Lang			= $di->View->Lang;
    }
    
    /**
     * Set mail instance
     * @param Bang\Modules\Account\Models $Mail
     */
    public function setMailInstance(\Bang\Modules\Directory\Models\Mail $Mail)
    {
    	$this->Mail = $Mail;
    }
    
    /**
     * *********** Experimental Fast Route Adding **********
     * Need to get tested after many changes.
     * 
     * Allows to add items like the example below, that would be a textarea
     * /Level1/Level2/sub0
     * /Level1/Level2/Sub1
     * /Level1/Level2/Sub2
     * /Level1/Level2/Sub2/SubSub1
     * /Level1/Level2/Sub2/SubSub2
     */
    
    /**
     * Try to ad a route to the navigation
     * @param array $route
     */
    public function tryToAddFastRoute(array $route, int $rootid)
    {
    	return $this->testRootPartByName($route, $rootid);
    }
    
    
    private function testRootPartByName(array $route, int $treeid)
    {
    	$id = 1;
    	 
    	foreach($route AS $key => $value) {
    
    		if(!empty(trim($value))) {
    
    			$exists = $this->nodeExistsByName(trim($value), $id, $treeid);
    				
    			// set id for the next run
    			if(is_array($exists) && isset($exists['id'])) {
    				$id = $exists['id'];
    			}
    
    			// insert if it does not exists
    			if(!is_array($exists)) {
    				$params = [
    						'template'	=> 'index.php',
    						'groups'	=> array(3),
    						'rootid'	=> $treeid,
    						'name'		=> trim($value),
    						'position'	=> $id
    				];
    
    				$id = $this->nestAddNodeConrtoller($params);
    			}
    		}
    	}
    	 
    	return true;
    }
    
    /**
     * Test if a node already exists in that context, search using the name and parentid
     * as well as treeid
     */
    private function nodeExistsByName(string $name, int $targetid, int $treeid)
    {
    	$query = "SELECT `id` FROM `".$this->addTable('categories')."`
    				WHERE
    					`name` = :name
    				AND
    					`parentid` = :targetid
    				AND
    					`rootid` = :treeid
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', 	$name, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':targetid', $targetid, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':treeid', $treeid, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssoc();
    		 
    		if(is_array($result) && isset($result['id'])) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * *********** END Experimental Fast Route Adding **********
     */
    
/********************* MOVE NODE **************/   
   
    /**
     * Move an node to a new position in the tree
     * @param string $serialized
     * @param int $id
     */
    public function moveNestedSetNode(string $serialized, int $rootid) : bool
    {
    	$serialized = json_decode($serialized, true);
    	
    	$dataTree = $this->addLeftRightToArray($serialized);
    	return $this->updateTreeReasonable($dataTree, $rootid);
    }
    
    /**
     * Loop through tree and trigger item update using the given data
     * @param array $data
     * @param int $treeid
     */
    public function updateTreeReasonable(array $data, int $treeid) : bool
    {
    	if(is_array($data) && count($data)) {
    
    		foreach($data AS $key => $value) {
    			$this->updateLeftRight($value, $treeid);
    		}
    	}
    
    	return true;
    }
    
	/**
	 * Update the left an right values for item after moving it in the tree
	 * @param array $item
	 * @param int $treeid
	 */
    private function updateLeftRight(array $item, int $treeid = '')
    {
    	$query = "UPDATE `".$this->addTable('categories')."` 
    				SET 
    					`lft` 			= :lft, 
    					`rgt` 			= :rgt, 
    					`parentid` 		= :parentid, 
    					`level` 		= :level
    			
    				WHERE `id` = :id
		";
    	
    	try {
    
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':lft',      	$item['lft'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rgt',		$item['rgt'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':id',        	$item['id'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':parentid',  	$item['parent'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':level',     	$item['level'], PDO::PARAM_INT);
    
    		$this->PdoWrapper->execute();
    
    		if ($this->PdoWrapper->rowCount() > 0) {
    			return true;
    		}
    
    	} catch (PDOException $e) {
    
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		return 'Update menu failed... -> ' . $message;
    	}
    
    	return 'update menu failed...';
    }
    
    /**
     * Get the count of children in a multi dimensional - nested - array
     * @param array/string $data - array we want the count of
     * @param bool $reset - reset count for a fresh start
     */
    public function getChildCount(array $data, $reset = false) : int
    {
    	// keep value
    	static $count = 0;
    
    	// if a reset is needed, for a fresh start
    	if($reset === true) {
    		$count  = 0;
    	}
    
    	// validate array
    	if(is_array($data) && count($data)) {
    		// loop through array
    		foreach($data AS $key => $value) {
    			// increase count
    			$count++;
    			// if children are available, the process has to get restarted for deeper levels
    			if(isset($value['children']) && is_array($value['children']) && count($value['children'])) {
    				// resatrt the process
    				$this->getChildCount($value['children']);
    			}
    		}
    	}
    
    	return $count;
    }
    
    /**
     * add nested set left and right to array
     * @param array $data
     */
    private function addLeftRightToArray(array $data) : array
    {
    	$left   = 0;
    	$new    = array();
    	$i      = 0;
    
    	if(is_array($data) && count($data)) {
    		$new = $this->recursiveChildren($data);
    	}
    
    	return $new;
    }
    
    
    private function recursiveChildren(array $data, int $parent = 0, int $level = 2) : array
    {
    	static $array       = array();
    	static $left        = 1;
    
    	if(is_array($data) && count($data)) {
    
    		foreach($data AS $key => $value) {
    
    			$left++;
    
    			$array[$value['id']]['lft']         = $left;
    			$array[$value['id']]['data']        = $value;
    			$array[$value['id']]['id']      	= $value['id'];
    			$array[$value['id']]['level']       = $level;
    
    
    			if($parent > 0) {
    				$array[$value['id']]['parent']          = $parent;
    				$array[$value['id']]['data']['parent']  = $parent;
    			} else {
    				$array[$value['id']]['parent']          = 1;
    				$array[$value['id']]['data']['parent']  = 1;
    			}
    
    			unset($array[$value['id']]['data']['children']);
    
    			if(isset($value['children']) && count($value['children'])) {
    
    				$countChildren = $this->getChildCount($value['children'], true);
    				$countChildren = $countChildren * 2 + 1;
    
    				$array[$value['id']]['rgt'] = $countChildren + $left;
    
    				$this->recursiveChildren($value['children'], $value['id'], ($array[$value['id']]['level'] + 1));
    
    				$left = $array[$value['id']]['rgt'];
    
    			} else {
    
    				// no children, need to increase i and set it as right number
    				$left++;
    				$array[$value['id']]['rgt'] = $left;
    			}
    		}
    	}
    
    	return $array;
    }
    
    /********************* END MOVE NODE **************/
    
    /**
     * Delete branch from database
     * @param int $id
     * @param int $rootid
     */
    private function deleteBranchById(int $id, int $rootid) : bool
    {
    	// delete branch with all children
    	$query = "DELETE FROM `".$this->addTable('categories')."`
    				WHERE
    					`rootid` = :rootid
    				AND
    					(`parentid` = :id || `id` = :id)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':id', $id, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rootid', $rootid, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		
    		if($this->PdoWrapper->rowCount() > 0) {
    			return true;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Update left after removing a branch
     * @param array $branch
     * @param int $rootid
     * @param int $id
     */
    private function updateLeftRightAfterDeleteBranchLeft(array $branch, int $rootid, int $id) : bool
    {
    	$query = "UPDATE `".$this->addTable('categories')."`
    				SET 
    					`lft` = lft - :newleft
    			  WHERE
    				(`rootid` = :rootid OR `id` = :rootid)
    				
    			  AND 
    				`lft` > :rgt 
    	";
    	
    	$newleft = (int)$branch['rgt'] - (int)$branch['lft'] + 1;
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':rootid', 	$rootid, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':newleft', 	$newleft, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rgt', 		$branch['rgt'], PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Update tree after deleteing a branch
     * Update right after removing
     * @param array $branch
     * @param int $rootid
     * @param int $id
     */
    private function updateLeftRightAfterDeleteBranchRight(array $branch, int $rootid, int $id) : bool
    {
    	$query = "UPDATE `".$this->addTable('categories')."`
    				SET
    					`rgt` = rgt - :newright
    			  WHERE
    				(`rootid` = :rootid OR `id` = :rootid)
    	
    			  AND
    				`rgt` > :rgt
    	";
    	
    	$newright = (int)$branch['rgt'] - (int)$branch['lft'] + 1;
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':rootid', 	$rootid, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':newright', 	$newright, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rgt', 		$branch['rgt'], PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Controller for delete branch process
     * @param int $id
     * @param int $rootid
     */
    public function deleteBranchByIdController(int $id, int $rootid) : bool
    {
    	if($this->PdoWrapper->inTransaction() !== true){
    		 
    		$this->PdoWrapper->beginTransaction();
    		
    		$branch = $this->getCategoryInfoById($id);
    		
    		if(is_array($branch) && $this->deleteBranchById((int)$id, (int)$rootid) === true) {
    			
    			if($this->updateLeftRightAfterDeleteBranchLeft((array)$branch, (int)$rootid, (int)$id) === true) {
    				
    				if($this->updateLeftRightAfterDeleteBranchRight((array)$branch, (int)$rootid, (int)$id) === true) {
    				
    					$this->PdoWrapper->commit();
    					return true;
    				} 
    			}
    		}
    		
    		$this->PdoWrapper->rollBack();
    	}
    	
		return false;
    }
    
    
    /**
     * Delete a category with all dependencies
     * @param int $catid
     */
    public function removeCategory(int $catid) : bool
    {
    	$backupFile = $this->backupCategory((int)$catid);
    	
    	if($backupFile !== false) { 
    		
    		$query = "DELETE FROM `".$this->addTable('categories')."`
	    				WHERE
	    					`id` = :catid
	    				OR
	    					`rootid` = :catid
	    	";
	    		 
    		try {
    			 
    			$this->PdoWrapper->prepare($query);
    			$this->PdoWrapper->bindValue(':catid', $catid, PDO::PARAM_INT);
    			$this->PdoWrapper->execute();
    			 
    			if($this->PdoWrapper->rowCount() > 0) {
    				return true;
    			}
    			 
    		} catch (\PDOException $e) {
    			 
    			$message = $e->getMessage();
    			$message .= $e->getTraceAsString();
    			$message .= $e->getCode();
    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		}
    		
    	} else {
    		
    		$this->Session->setError('Backup failed');
    		// backup failed
    	}
    	
    	return false;
    }
    
    /**
     * Backup a category as .sql file and upload the file to AWS S3
     * @param int $catid
     */
    public function backupCategory(int $catid) :bool
    {
    	$tree = $this->getCompleteRootTreeById((int)$catid);
    	
    	if(is_array($tree) && count($tree)) {
    		$backup = $this->genBackupSqlForTree((array)$tree);
    		
    		if($backup !== false) {
    			
    			$S3 		= new \Bang\Tools\Awss3();
    			$filename 	= $S3->putObjectAsString($backup, 'category_'.$catid.'.sql', 'backups/categories/');
    			 
    			if($filename !== false && !empty($filename)) {
    				return $filename;
    			}
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Create sql insert string
     * @param array $data
     */
    private function genBackupSqlForTree(array $data)
    {
    	$insert = "INSERT INTO `".$this->addTable('categories')."` 
    				(
    				`id`, 
			    	`name`, 
			    	`lft`, 
			    	`rgt`, 
			    	`parentid`,
			    	`rootid`, 
			    	`template`, 
			    	`description`, 
			    	`level`
    				)
    			VALUES
    	\n";
    	
    	foreach($data AS $key => $value) {
    		
    		$insert .= "(
	    				'".$value['id']."', 
				    	'".$value['name']."', 
				    	'".$value['lft']."', 
				    	'".$value['rgt']."', 
				    	'".$value['parentid']."',
				    	'".$value['rootid']."', 
				    	'".$value['template']."', 
				    	'".$value['description']."', 
				    	'".$value['level']."'
	    				),";
    		
    	}
    	
    	$insert = mb_substr($insert, 0, -1);
    	
    	return $insert;
    }
    
   /**
    * Get a tree inclusive root and all children
    * @param int $catid
    */
  	private function getCompleteRootTreeById(int $catid)
   	{
   		$query = "SELECT * FROM `".$this->addTable('categories')."`
    				WHERE
    					(`rootid` = :catid OR `id` = :catid)
    				ORDER BY `lft`
    	";
   		 
   		try {
   			 
   			$this->PdoWrapper->prepare($query);
   			$this->PdoWrapper->bindValue(':catid', (int)$catid, PDO::PARAM_INT);
   			$this->PdoWrapper->execute();
   			 
   			$result = $this->PdoWrapper->fetchAssocList();
   			
   			if(is_array($result) && count($result)) {
   				return $result;
   			}
   			 
   		} catch (\PDOException $e) {
   			 
   			$message = $e->getMessage();
   			$message .= $e->getTraceAsString();
   			$message .= $e->getCode();
   			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
   		}
   		 
   		return false;
   	}
    
    /**
     * Get available templates from template index directory
     */
    public function getMainTemplates()
    {
    	$path = PUB.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['backendmaintpl'].DIRECTORY_SEPARATOR;
    	$files = glob($path . "*.php");
    	$new = array();
    	
    	if(is_array($files) && count($files)) {
    		
    		foreach($files AS $key => $value) {
    			
    			if(basename($value) != 'error.php' && basename($value) != 'login.php' && 
    			basename($value) != 'forgot.php' && basename($value) != 'message.php') {
    				
    				$new[] = basename($value);
    			} 
    		}
    	}
    	
    	return $new;
    }
    
    /**
     * Get frontend permission groups
     */
    public function getFrontendPermissionGroups()
    {
    	$query = "SELECT * FROM `".$this->addTable('permission_groups')."`
    				WHERE 
    					`id` > 2
    	";	
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Get all root categories
     */
    public function getRootCategories()
    {
    	$query = "SELECT * FROM `".$this->addTable('categories')."` 
    				WHERE 
    					`rootid` = :rootid
    				AND 
    					`parentid` = :parentid
    	";
    	
    	try {
    	
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':rootid', 	0, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':parentid', 	0, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssocList();
    	
    		if(is_array($result) && count($result)) {
    			
    			foreach($result AS $key => $value) {
    				
    				$lang = $this->getLanguageFromGrid((int)$value['id'], 'directory');
    				$result[$key]['langstrings'] = $lang;
    			}
    			
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Get a root category by id
     * @param int $id
     */
    public function getCategoryInfoById(int $id) 
    {
    	$query = "SELECT * FROM `".$this->addTable('categories')."`
    				WHERE
    					`id` = :id
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':id', 	(int)$id, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssoc();
    		 
    		if(is_array($result) && count($result)) {
    			
    			$lang = $this->getLanguageFromGrid((int)$id, 'directory');
				$result['langstrings'] = $lang;    			
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Delete language entries for hookid
     * @param int $hookid
     */
    private function deleteFromLanguageGrid(int $hookid) : bool
    {
    	$query = "DELETE FROM `".$this->addTable('language_grid')."`
    				WHERE
    					`hookid` = :hookid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':hookid', 	(int)$id, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		
    		if($this->PdoWrapper->rowCount() > 0) {
    			return true;
    		}

    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Get the language strings for this hookid and module
     * @param int $id
     * @param string $module
     */
    private function getLanguageFromGrid(int $id, string $module)
    {
    	$query = "SELECT * FROM `".$this->addTable('language_grid')."`
    				WHERE 
    					`module` = :module
    				AND 
    					`hookid` = :hookid
    				ORDER BY
    					`langcode`
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':hookid', 	(int)$id, PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':module', 	$module, PDO::PARAM_STR);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			
    			$new = array();
    			
    			foreach($result AS $key => $value) {
    				$new[$value['langcode']][$value['namekey']] = $value;
    			}
    			
    			return $new;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Update a categories meta data
     * @param array $params
     */
    public function updateCategoryMetaData(array $params) : bool
    {
    	if($this->validateCreateMainCategoryForm((array)$params) === true) {
    		
    		if($this->PdoWrapper->inTransaction() !== true) {
    		
    			// Data is there
    			$this->PdoWrapper->beginTransaction();
    		
    		
	    		$query = "UPDATE `".$this->addTable('categories')."`
	    					SET 
	    						`name` 			= :name,
	    						`description` 	= :desc,
	    						`template` 		= :template,
	    						`ctype`			= :ctype
	    					WHERE 
	    						`id` = :id
	    		";
	    		
	    		try {
	    		
	    			if(!isset($params['description'])) {
	    				$params['description'] = '';
	    			}
	    			 
	    			$this->PdoWrapper->prepare($query);
	    			$this->PdoWrapper->bindValue(':name', 		filter_var($params['name'], FILTER_SANITIZE_STRING), PDO::PARAM_STR);
	    			$this->PdoWrapper->bindValue(':desc',		filter_var($params['description'], FILTER_SANITIZE_STRING), PDO::PARAM_STR);
	    			$this->PdoWrapper->bindValue(':template', 	$params['template'], PDO::PARAM_STR);
	    			$this->PdoWrapper->bindValue(':ctype', 		$params['ctype'], PDO::PARAM_STR);
	    			$this->PdoWrapper->bindValue(':id', 		(int)$params['rootid'], PDO::PARAM_INT);
	    			 
	    			if($this->PdoWrapper->execute() === true) {
	    				
	    				if($this->addStringToLanguageTable('lang_name', (int)$params['rootid'], $params['name'], $this->Session->getUserLang(), 'directory') === true) {
	    					$this->addStringToLanguageTable('lang_desc', (int)$params['rootid'], $params['description'], $this->Session->getUserLang(), 'directory');
	    					
	    					$this->PdoWrapper->commit();
	    					return true;
	    				}
	    			}
	    		
	    		} catch (\PDOException $e) {
	    		
	    			$message = $e->getMessage();
	    			$message .= $e->getTraceAsString();
	    			$message .= $e->getCode();
	    			$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
	    			 
	    		}
	    		
	    		$this->PdoWrapper->rollBack();
	    	}
    	}
    	
    	return false;
    }
    
    /**
     * Validate create main categoty form
     * @param array $params
     */
    private function validateCreateMainCategoryForm(array $params) : bool 
    {
    	if(isset($params['name']) && !empty($params['name'])) {
    		
    		if(Helper::validate($params['name'], 'raw', 80) === true) {
    			
    			if(isset($params['ctype']) && !empty($params['ctype'])) {
    			
	    			if(isset($params['template']) && !empty($params['template'])) {
	    			
	    				if(file_exists(PUB.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['backendmaintpl'].DIRECTORY_SEPARATOR.basename($params['template'])) === true) {
	    					
	    					return true;
	    					
	    				} else {
	    					
	    					$this->Session->setError($this->Lang->get('directory_new_category_form_validation_nofile'));
	    				}
	    			
	    			} else {
	    			
	    				// please select temlate
	    				$this->Session->setError($this->Lang->get('directory_new_category_form_validation_notemplate'));
	    			}
    			
    			} else {
    				
    				$this->Session->setError($this->Lang->get('directory_new_category_type_select'));
    			}
    			
    			
    		} else {
    			
    			// name is too long
    			$this->Session->setError($this->Lang->get('directory_new_category_form_validation_name_toolong'));
    		}
    		
    	} else {
    		
    		// please enter a name
    		$this->Session->setError($this->Lang->get('directory_new_category_form_validation_noname'));
    	}
    	
    	return false;
    }
    
    /**
     * Add a new category tree/menu/directory
     * @param array $params
     */
    public function newRootCategory(array $params) : bool
    {
    	if($this->validateCreateMainCategoryForm((array)$params) === true) {
    		
    		if($this->PdoWrapper->inTransaction() !== true) {
    		
    			// Data is there
    			$this->PdoWrapper->beginTransaction();
    		
    			$query = "INSERT INTO `".$this->addTable('categories')."`
    					(`name`, `description`, `template`, `lft`, `rgt`, `parentid`, `rootid`, `level`, `ctype`)
    				  VALUES
    					(:name, :description, :template, :lft, :rgt, :parentid, :rootid, :level, :ctype)
    			";
    			
    			try {
    			
    				if(!isset($params['description'])) {
    					$params['description'] = '';
    				}
    				 
    				$this->PdoWrapper->prepare($query);
    				$this->PdoWrapper->bindValue(':name', 		filter_var($params['name'], FILTER_SANITIZE_STRING), PDO::PARAM_STR);
    				$this->PdoWrapper->bindValue(':description',filter_var($params['description'], FILTER_SANITIZE_STRING), PDO::PARAM_STR);
    				$this->PdoWrapper->bindValue(':template', 	$params['template'], PDO::PARAM_STR);
    				$this->PdoWrapper->bindValue(':ctype', 		$params['ctype'], PDO::PARAM_STR);
    				$this->PdoWrapper->bindValue(':lft', 		1, PDO::PARAM_INT);
    				$this->PdoWrapper->bindValue(':rgt', 		2, PDO::PARAM_INT);
    				$this->PdoWrapper->bindValue(':parentid', 	0, PDO::PARAM_INT);
    				$this->PdoWrapper->bindValue(':rootid', 	0, PDO::PARAM_INT);
    				$this->PdoWrapper->bindValue(':level', 		1, PDO::PARAM_INT);
    				 
    				$this->PdoWrapper->execute();
    				$count = $this->PdoWrapper->rowCount();
    				 
    				if($this->PdoWrapper->rowCount() > 0) {
    			
    					$id = $this->PdoWrapper->lastInsertId();

    					if($this->addStringToLanguageTable('lang_name', $id, $params['name'], $this->Session->getUserLang(), 'directory') === true) {
    						$this->addStringToLanguageTable('lang_desc', $id, $params['description'], $this->Session->getUserLang(), 'directory');
    						
    						$this->PdoWrapper->commit();
    						
    						return true;
    					}
    				}
    			
    			} catch (\PDOException $e) {
    			
    				$message = $e->getMessage();
    				$message .= $e->getTraceAsString();
    				$message .= $e->getCode();
    				$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    				 
    			}
    			
    			$this->PdoWrapper->rollBack();
    		}
    	}
    	
    	return false;
    }
    
    /**
     * 
     * @param string $nameKey
     * @param int $id
     * @param string $value
     * @param string $langcode
     * @param string $module
     */
    private function addStringToLanguageTable(string $nameKey, int $id, string $value, string $langcode, string $module) : bool
    {
    	$query = "INSERT INTO `".$this->addTable('language_grid')."`
    				(`hookid`, `namekey`, `textvalue`, `langcode`, `module`)
    			  VALUES
    				(:hookid, :namekey, :textvalue, :langcode, :module)
    			
    			  ON DUPLICATE KEY UPDATE
    				textvalue	 = VALUES(textvalue)
    	";
    	
    	try {
    	
    		if(!isset($params['description'])) {
    			$params['description'] = '';
    		}
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':textvalue', 	filter_var($value, FILTER_SANITIZE_STRING), PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':langcode', 	$langcode, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':namekey', 	$nameKey, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':module', 	$module, PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':hookid', 	(int)$id, PDO::PARAM_INT);
    		 
    		return $this->PdoWrapper->execute();
    	
    	} catch (\PDOException $e) {
    	
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    		 
    	}
    	 
    	return false;
    }
    
    /**
     * Validate the add category form
     * @param array $params
     */
    private function validateAddNodeForm(array $params) : bool
    {
    	if(isset($params['rootid']) && (int)$params['rootid'] > 0) {
    		
    		if(isset($params['name']) && !empty($params['name'])) {
    		
    			if(isset($params['template']) && !empty($params['template'])) {
    			
    				if(isset($params['position']) && !empty($params['position'])) {
    				
    					if(isset($params['groups']) && !empty($params['groups'])) {
    					
    						if(file_exists(PUB.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['backendmaintpl'].DIRECTORY_SEPARATOR.basename($params['template'])) === true) {
    								
    							return true;
    							
    						} else {
    								
    							$this->Session->setError($this->Lang->get('directory_new_category_form_validation_nofile'));
    						}
    					
    					} else {
    					
    						$this->Session->setError($this->Lang->get('directory_new_category_form_validation_nogroup'));
    					}
    				
    				} else {
    					 
    					$this->Session->setError($this->Lang->get('directory_new_category_form_validation_position'));
    				}
    			
    			} else {
    				 
    				$this->Session->setError($this->Lang->get('directory_new_category_form_validation_notemplate'));
    			}
    		
    		} else {
    			
    			$this->Session->setError($this->Lang->get('directory_new_category_form_validation_name'));
    		}
    	}
    	
    	return true;
    }
    
    /**
     * 
     * @param array $params
     */
    private function validateUpdateNodeForm(array $params) :bool
    {
    	if(isset($params['rootid']) && (int)$params['rootid'] > 0) {
    	
    		if(isset($params['name']) && !empty($params['name'])) {
    	
    			if(isset($params['template']) && !empty($params['template'])) {
    				 
					if(isset($params['groups']) && !empty($params['groups'])) {
    							
						if(file_exists(PUB.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['backendmaintpl'].DIRECTORY_SEPARATOR.basename($params['template'])) === true) {
    	
							return true;
    								
						} else {
    	
							$this->Session->setError($this->Lang->get('directory_new_category_form_validation_nofile'));
						}
    							
					} else {
    							
						$this->Session->setError($this->Lang->get('directory_new_category_form_validation_nogroup'));
					}
    				 
    			} else {
    					
    				$this->Session->setError($this->Lang->get('directory_new_category_form_validation_notemplate'));
    			}
    	
    		} else {
    			 
    			$this->Session->setError($this->Lang->get('directory_new_category_form_validation_name'));
    		}
    	}
    	 
    	return true;
    }
    
    
    /**
     *
     * @param int $rootid
     */
    public function getBranchById(int $id)
    {
    	$query = "SELECT
    				node.*,
    				round( (node.`rgt`- node.`lft`- 1) / 2,0) AS Children,
                    IF(node.`rgt` - 1 = node.`lft`, 'close' ,'open' ) AS Control
    
					FROM `".$this->addTable('categories')."` AS node
			
					WHERE
						node.`lft` BETWEEN node.`lft` AND node.`rgt`
					AND
						node.`id` = :id
    
					ORDER BY node.`lft`
    	";
    	 
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':id', $id, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * 
     * @param int $rootid
     */
    public function getTreeById(int $rootid)
    {
    	$query = "SELECT 
    				node.*,
    				round( (node.`rgt`- node.`lft`- 1) / 2,0) AS Children, 
                    IF(node.`rgt` - 1 = node.`lft`, 'close' ,'open' ) AS Control
    			
					FROM `".$this->addTable('categories')."` AS node
					
					WHERE 
						node.`lft` BETWEEN node.`lft` AND node.`rgt`
					AND 
						node.`rootid` = :rootid
					     
					ORDER BY node.`lft`
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':rootid', $rootid, PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		$result = $this->PdoWrapper->fetchAssocList();
    		 
    		if(is_array($result) && count($result)) {
    			
    			foreach($result AS $key => $value) {
    				
    				$lang = $this->getLanguageFromGrid((int)$value['id'], 'directory');
					$result[$key]['langstrings'] = $lang;    			
    			}
    			
    			return $result;
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Update node item
     * @param array $params
     * @return unknown|boolean
     */
    public function nestUpdateNodeConrtoller(array $params) : bool
    {
    	// 1. Validate params
    	if($this->validateUpdateNodeForm($params) === true) {
    		
    		if($this->PdoWrapper->inTransaction() !== true) {

    			// Data is there
    			$this->PdoWrapper->beginTransaction();
    			
    			if($this->updateNodeData($params) === true) {
    					
    				if($this->assignCategoryGroupPermissions((int)$params['nodeid'], (array)$params['groups']) === true) {
    			
    					if($this->addStringToLanguageTable('node_name', (int)$params['nodeid'], $params['name'], $this->Session->getUserLang(), 'directory') === true) {
    						
    						$this->PdoWrapper->commit();
    						return true;
    					}
    				} 
    			}
    			
    			$this->PdoWrapper->rollBack();
    		}
    	}
    	 
    	return false;
    }
    
    /**
     * Update nodeitem
     * @param array $params
     */
    private function updateNodeData(array $params) : bool
    {
    	$query = "UPDATE `".$this->addTable('categories')."`
    				SET 
    					`name` = :name,
    					`template` = :template
    				WHERE
    					`rootid` = :rootid
    				AND
    					`id` = :nodeid
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', 		$params['name'], 			PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':template', 	$params['template'], 		PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':nodeid', 	(int)$params['nodeid'], 	PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rootid', 	(int)$params['rootid'],	 	PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Controller for the add node process
     * 1. Validate form
     * 2. Get target information
     * 3. Update left (optional)
     * 4. Update right
     * 5. Insert new Node
     * 6. Commit/rollBack transaction
     * 7. return bool
     * @param array $params
     */
    public function nestAddNodeConrtoller(array $params)
    {
    	// 1. Validate params
    	if($this->validateAddNodeForm($params) === true) {
    		// 2. Get target information
    		$target = $this->getCategoryInfoById($params['position']);
    		
    		if($this->PdoWrapper->inTransaction() !== true) {
    			 
    			if(is_array($target) && isset($target['lft'])) {
    				// Data is there
    				$this->PdoWrapper->beginTransaction();
    				
    				// Update left if it is not root
    				if ($target['lft'] != 1) {
    					$this->nestUpdateLeft($target, $params['rootid']);
    				}
    				
    				// Update right and insert new node
    				if($this->nestUpdateRight($target, $params['rootid']) === true) {
    					
    					$insertid = $this->nestInsertNode($target, $params);
    					
    					if((int)$insertid > 0) { 
    						
    						if($this->addStringToLanguageTable('node_name', (int)$insertid, $params['name'], $this->Session->getUserLang(), 'directory') === true) {
    						
    							// all true commit
    							$this->PdoWrapper->commit();
    							//
    							return $insertid;
    						}
    					}
    					
    				} else {
    					
    					// something went wrong rollBack
    					$this->PdoWrapper->rollBack();
    				}
    			}
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Insert new node/category
     * @param array $target
     * @param array $params
     */
    private function nestInsertNode(array $target, array $params)
    {
    	$query = "INSERT INTO `".$this->addTable('categories')."`
    				(`name`, `lft`, `rgt`, `parentid`, `rootid`, `level`, `template`)
    			  VALUES
    				(:name, :lft, :rgt, :parentid, :rootid, :level, :template)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':name', 		$params['name'], 			PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':template', 	$params['template'], 		PDO::PARAM_STR);
    		$this->PdoWrapper->bindValue(':lft', 		(int)$target['rgt'], 		PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rgt', 		(int)$target['rgt'] + 1, 	PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':parentid',	(int)$target['id'], 		PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rootid', 	(int)$params['rootid'], 	PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':level', 		(int)$target['level'] + 1, 	PDO::PARAM_INT);
    		$this->PdoWrapper->execute();
    		 
    		if($this->PdoWrapper->rowCount() > 0) {
    			
    			$id = $this->PdoWrapper->lastInsertId();
    			
    			if($this->assignCategoryGroupPermissions((int)$id, (array)$params['groups']) === true) {
					return $id;    				
    			}
    		}
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
    }
    
    /**
     * Assign the groups to db table
     * @param int $category
     * @param array $groups
     */
    private function assignCategoryGroupPermissions(int $category, array $groups) : bool
    {
    	$query = "INSERT IGNORE INTO `".$this->addTable('categories_group_permisions')."`
    				(`categoryid`, `groupid`)
    			  VALUES
    				(:categoryid, :groupid)
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		
    		foreach($groups AS $key => $value) {
				
    			$this->PdoWrapper->bindValue(':categoryid',	(int)$category, PDO::PARAM_INT);
    			$this->PdoWrapper->bindValue(':groupid',	(int)$value, PDO::PARAM_INT);
    			
    			$this->PdoWrapper->execute();
    		}
    		
    		return true;
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    	
    	
    }
    
    /**
     * Update Right
     * @param array $target
     */
    private function nestUpdateRight(array $target, int $treeid) : bool
    {
    	$query = "UPDATE `".$this->addTable('categories')."`
                	SET
                  		`rgt` = `rgt` +2
                	WHERE
                 		`rgt` >= :right
                	AND
                  		(`rootid`  = :rootid || (`rootid` = 0 AND `id` = :rootid))
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':right', 	(int)$target['rgt'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rootid', (int)$treeid, PDO::PARAM_INT);
    
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	 
    	return false;
    }
    
    /**
     * Update left
     * @param array $target
     */
    private function nestUpdateLeft(array $target, int $treeid) : bool
    {
    	$query = "UPDATE `".$this->addTable('categories')."`
                	SET 
                  		`lft` = `lft` +2
                	WHERE
                 		`lft` > :right
                	AND 
                 		`rgt` >= :right
                	AND
                  		(`rootid`  = :rootid || (`rootid` = 0 AND `id` = :rootid))
    	";
    	
    	try {
    		 
    		$this->PdoWrapper->prepare($query);
    		$this->PdoWrapper->bindValue(':right', 	(int)$target['rgt'], PDO::PARAM_INT);
    		$this->PdoWrapper->bindValue(':rootid', (int)$treeid, PDO::PARAM_INT);
    		
    		return $this->PdoWrapper->execute();
    		 
    	} catch (\PDOException $e) {
    		 
    		$message = $e->getMessage();
    		$message .= $e->getTraceAsString();
    		$message .= $e->getCode();
    		$this->ErrorLog->logError('DB', $message, __METHOD__ .' - Line: '. __LINE__);
    	}
    	
    	return false;
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
    			$varArray[$propName] = $this->$propName;
    		}
    	}
    
    	return $varArray;
    }
}