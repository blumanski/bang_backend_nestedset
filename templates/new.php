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
				
				<h5 class="breadcrumbs-title">
				<?php echo $this->Lang->write('directory_breadcrumbs_editcat');?>
				</h5>
				
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
						<?php echo $this->Lang->write('directory_breadcrumbs_newcat');?>
						
						</li>
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
		        <li class="active"><a href="/directory/index/newcategory/"><?php echo $this->Lang->write('directory_nav_newcategory');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
			<div class="table-datatables">
				
				<div class="row s12">
							
					<div class="col s6">
				
						<?php 
						$post = $this->Session->getFormData();
						?>
				
						<form autocomplete="off" id="profile-form" action="/directory/index/processnewcategory/" method="post">
							
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
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('directory_new_category_name');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
									<div class="input-field col s12">
							            <input 
							            	autocomplete="off"
							            	placeholder="<?php echo $this->Lang->write('directory_new_category_description');?>" 
							            	type="text" 
							            	maxlength="200"
							            	id="description" 
							            	name="description" 
							            	value="<?php if(isset($post['description'])) { echo htmlspecialchars($post['description'], ENT_QUOTES, 'UTF-8'); }?>" 
							            />
							            
							            <label for="desc" class="center-align"><?php echo $this->Lang->write('directory_new_category_description');?></label>
							            <div class="valerror"></div>
									</div>
								</div>
								
								<div class="row margin">
					
									<div id="timezone-wrapper" class="input-field col s6"  style="margin-top: 15px;">
											
										<select class="auth-input" id="template" name="template">
											
												<?php if(isset($this->tplVar['templates']) && count($this->tplVar['templates'])) { ?>
					                      
													<?php foreach($this->tplVar['templates'] AS $key => $value) { ?>
										    			
														<option 
															<?php if(isset($post['template']) && $post['template'] == $value) { echo 'selected="selected"';}?> 
															value="<?php echo $value;?>"
														>
															<?php echo $value; if($value == 'index.php') { echo ' (Default)'; } ?>
														</option>
										    
											<?php } ?>
										<?php } ?>  
										    
										</select>
										
										<label for="desc" class="center-align"><?php echo $this->Lang->write('directory_new_category_template');?></label>
										<div class="valerror"></div>  
							           
									</div>
								</div>
								
								<div class="row margin">
					
									<div id="timezone-wrapper" class="input-field col s6"  style="margin-top: 15px;">
											
										<select class="auth-input" id="ctype" name="ctype">
											
											<option value="1">
												<?php echo ucfirst($this->Lang->write('directory_new_category_type_category'));?>
											</option>
					                      
										    <option value="2">
												<?php echo ucfirst($this->Lang->write('directory_new_category_type_menu'));?>
											</option>
										</select>
										
										<label for="desc" class="center-align"><?php echo $this->Lang->write('directory_new_category_type');?></label>
										<div class="valerror"></div>  
							           
									</div>
								</div>
								
							
							<div class="row" style="margin-top: 25px;">
								<div class="col s12">
								<button 
									type="submit" 
									class="btn waves-effect waves-light left submit">
										<?php echo $this->Lang->write('directory_new_category_save');?>
									</button>
								</div>
							</div>
							
						</form>
				
					</div> <!-- s6 -->
				
				
					<div class="col s4">


						
					</div> <!-- end upload -->
				</div> <!-- s12 -->
			
			</div>
		</div>
	</div>
</div>
       
