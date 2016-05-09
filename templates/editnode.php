<?php 
$currentLang 	= $this->Session->getUserLang();
$post 			= $this->tplVar['node'];

if(is_array($post['langstrings']) && isset($post['langstrings'][$currentLang])) {
		
	$post['name'] = $post['langstrings'][$currentLang]['node_name']['textvalue'];
		
} else {
	$post['name'] = '';
}
?>

<!--breadcrumbs start-->
<div id="breadcrumbs-wrapper">
	<!-- Search for small screen -->
	<div class="header-search-wrapper grey hide-on-large-only">
		<i class="mdi-action-search active"></i>
			<input type="text" name="Search" class="header-search-input z-depth-2" placeholder="Explore Materialize">
	</div>
	
	<div class="container">
		<div class="row">
			<div class="col s12 m12 l12">
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('dashboard_module_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						 <i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						 <a href="/directory/index/index/">
							<?php echo $this->Lang->write('directory_breadcrumbs_main');?>
						</a>
						
						<i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<a href="/directory/index/tree/rootid/<?php if(isset($this->tplVar['rootid']) && (int)$this->tplVar['rootid'] > 0) { echo $this->tplVar['rootid']; }?>/">
							<?php echo $this->tplVar['rootInfo']['name'];?>
						</a>
						
						<i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<?php if(isset($post['name'])) { echo htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8'); }?>
							
							
				</ol>
			</div>
		</div>
	</div>
</div>
<!--breadcrumbs end-->
        
<!--start container-->
<div class="container content-wrapper">
	<div class="section">
	
	 	<nav>
			<div class="nav-wrapper">
				<ul id="nav-mobile" class="left hide-on-med-and-down">
		        <li><a href="/directory/index/index/"><?php echo $this->Lang->write('directory_nav_listcategories');?></a></li>
		        <li><a href="/directory/index/editcategory/"><?php echo $this->Lang->write('directory_nav_newcategory');?></a></li>
		        <li class="active"><a href="/directory/index/editcategory/"><?php echo $this->Lang->write('directory_breadcrumbs_editcat');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
			<div class="table-datatables">
				
				<div class="row s12">
							
					<div class="col s6">
				
						<form autocomplete="off" id="edit-node-form" method="post">
						
							<div class="row margin">
								<div class="input-field col s12">
						            <input 
						            	autocomplete="off"
						            	placeholder="<?php echo $this->Lang->write('directory_new_category_name');?>" 
						            	type="text" 
						            	maxlength="100"
						            	id="name" 
						            	name="name" 
						            	value="<?php if(isset($post['name'])) { echo htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8'); }?>" 
						            />
						            
						            <input type="hidden" id="rootid" name="rootid" value="<?php if(isset($this->tplVar['rootid']) && (int)$this->tplVar['rootid'] > 0) { echo $this->tplVar['rootid']; }?>" />
						            <input type="hidden" id="nodeid" name="nodeid" value="<?php if(isset($post['id']) && (int)$post['id'] > 0) { echo (int)$post['id']; }?>" />
						            
						            <label for="name" class="center-align">Name</label>
						            <div class="valerror"></div>
								</div>
							</div>
							
							<div class="row margin">
				
								<div id="timezone-wrapper" class="input-field col s6"  style="margin-top: 15px;">
										
									<select class="auth-input" id="template" name="template">
										
										<option value=""><?php echo $this->Lang->write('directory_tree_new_node_form_select_template');?></option>
				                      
											<?php if(isset($this->tplVar['templates']) && count($this->tplVar['templates'])) { ?>
				                      
												<?php foreach($this->tplVar['templates'] AS $key => $value) { ?>
									    			
													<option 
														<?php 
														if(isset($post['template']) && $post['template'] == $value) { 
															
															echo 'selected="selected"';
															
														} else {
															
															if(isset($this->tplVar['rootInfo']) && isset($this->tplVar['rootInfo']['template']) && $this->tplVar['rootInfo']['template'] == $value) {
																
																echo 'selected="selected"';
															}
														}
														
														?> 
														value="<?php echo htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');?>"
													>
														<?php echo $value; if($value == 'index.php') { echo ' (Default)'; } ?>
													</option>
									    
										<?php } ?>
									<?php } ?>  
									    
									</select>
									
									<label for="template" class="center-align"><?php echo $this->Lang->write('directory_tree_new_node_form_template');?></label>
									<div class="valerror"></div>  
						           
								</div>
							</div>
							
							
							<div class="row margin">
				
								<div id="timezone-wrapper" class="input-field col s6"  style="margin-top: 15px;">
										
									<select class="auth-input" id="permgroups" name="permgroups[]" multiple="multiple" size="5">
										
										<option value=""><?php echo $this->Lang->write('directory_tree_new_node_form_select_permission_group');?></option>
				                      
											<?php if(isset($this->tplVar['frontgroups']) && count($this->tplVar['frontgroups'])) { ?>
				                      
												<?php foreach($this->tplVar['frontgroups'] AS $key => $value) { ?>
									    			
													<option 
														<?php if((int)$value['id'] == 3) { echo 'selected="selected"'; }?>
														value="<?php echo (int)$value['id'];?>"
													>
													<?php echo htmlspecialchars(trim($value['name']), ENT_QUOTES, 'UTF-8');?>
													</option>
									    
										<?php } ?>
									<?php } ?>  
									    
									</select>
									
									<label for="permgroups" class="center-align"><?php echo $this->Lang->write('directory_tree_new_node_form_permissions');?></label>
									<div class="valerror"></div>  
						           
								</div>
							</div>
						
						<div class="row" style="margin-top: 25px;">
							<div class="col s12">
							<button 
								type="submit" 
								class="btn waves-effect waves-light left submit">
									<?php echo $this->Lang->write('directory_tree_new_node_form_save');?>
								</button>
								
								<a href="/directory/index/tree/rootid/<?php if(isset($this->tplVar['rootid']) && (int)$this->tplVar['rootid'] > 0) { echo $this->tplVar['rootid']; }?>/"
								type="reset" id="reset-node"
								class="leftmargin btn waves-effect waves-light grey darken left button">
									<?php echo $this->Lang->write('directory_tree_new_node_form_cancel');?>
								</a>
							</div>
						</div>
						
						</form>
				
					</div> <!-- s6 -->
				
				
					<div class="col s6">
						<h4>Language Strings</h4>
							
						<?php if(isset($post['langstrings']) && is_array($post['langstrings']) && count($post['langstrings'])) { ?>
							<ul>
								<?php foreach($post['langstrings'] AS $key => $value) { ?>
								
								<li>
									<p><strong><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8');?></strong></p>
									<p>
									<a href="/account/index/changelang/lang/<?php echo substr($key, 0, 2);?>/">
									<?php 
									
									echo $value['node_name']['namekey'].': ';
									echo $value['node_name']['textvalue'].'<br />';
									?>
									</a>
									</p>
								</li>
								
								<?php } ?>
							
							</ul>
						<?php } ?>
						
					</div> <!-- end upload -->
				</div> <!-- s12 -->
			
			</div>
		</div>
	</div>
</div>
       
