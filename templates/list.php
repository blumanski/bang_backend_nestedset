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
				<?php echo $this->Lang->write('dashboard_module_listing_headline');?>
				</h5>
				
				<h5 class="breadcrumbs-title"><?php echo $this->Lang->write('dashboard_module_headline');?></h5>
				<ol class="breadcrumbs">
					<li>
						<a href="/">
							<?php echo $this->Lang->write('app_breadcrumbs_home');?>
						</a>
						 <i class="mdi-hardware-keyboard-arrow-right" style="line-height: 15px;"></i>
						<?php echo $this->Lang->write('directory_breadcrumbs_main');?>
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
		        <li class="active"><a href="/directory/index/index/"><?php echo $this->Lang->write('directory_nav_listcategories');?></a></li>
		        <li><a href="/directory/index/newcategory/"><?php echo $this->Lang->write('directory_nav_newcategory');?></a></li>
		      </ul>
			</div>
		</nav>

		<div class="action-wrapper">
			<div class="table-datatables">
				
				<?php 
				$lang = $this->Session->getUserLang();
				?>
				
				<div class="row">
	              	  
					<div class="col s12 m12 19">
						
						<form action="/directory/index/index/" method="post">
					
							<table id="directory-group-table" class="responsive-table display" cellspacing="0">
								<thead>
								
			                        <tr>
			                        	<th class="action-td">Id</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                        	<th class="action-td" data-orderable="false">&#160;</th>
			                            <th style="width: 40%;"><?php echo $this->Lang->write('directory_listing_table_head_name');?></th>
			                            <th style="width: 60%;"><?php echo $this->Lang->write('directory_listing_table_head_description');?></th>
			                        </tr>
			                    </thead>
								
								<tbody>
								<?php if(isset($this->tplVar['roots']) && is_array($this->tplVar['roots']) && count($this->tplVar['roots'])) { ?>
									<?php foreach($this->tplVar['roots'] AS $key => $value) { ?>
					
					
									<tr class="">
										<td class="action-td">#<?php echo (int)$value['id'];?></td>
										<td class="action-td"><a href="/directory/index/editcategory/rootid/<?php echo (int)$value['id'];?>/"><i class="mdi-editor-mode-edit"></i></a></td>
										<td class="action-td">
										
										<a class="del-group" href="/directory/index/removecat/catid/<?php echo (int)$value['id'];?>"><i class="mdi-action-delete"></i></a>
										
										</td>
			                            <td>
			                            	<a href="/directory/index/tree/rootid/<?php echo (int)$value['id'];?>/">
			                            		
			                            		<?php 
			                            		if(isset($value['langstrings']) && is_array($value['langstrings'])) {
			                            			if(isset($value['langstrings'][$lang]) && isset($value['langstrings'][$lang]['lang_name']) && isset($value['langstrings'][$lang]['lang_name']['textvalue'])) {
			                            				echo htmlspecialchars($value['langstrings'][$lang]['lang_name']['textvalue'], ENT_QUOTES, 'UTF-8');
			                            			} else {
				                            			echo htmlspecialchars($value['name'], ENT_QUOTES, 'UTF-8');
				                            		}
			                            		} else {
			                            			echo htmlspecialchars($value['name'], ENT_QUOTES, 'UTF-8');
			                            		}
			                            		?>
			                            	
			                            	</a>
			                           	</td>
			                            
			                            <td>
			                            	<?php 
			                            		if(isset($value['langstrings']) && is_array($value['langstrings'])) {
			                            			if(isset($value['langstrings'][$lang]) && isset($value['langstrings'][$lang]['lang_desc']) && isset($value['langstrings'][$lang]['lang_desc']['textvalue'])) {
			                            				echo htmlspecialchars($value['langstrings'][$lang]['lang_desc']['textvalue'], ENT_QUOTES, 'UTF-8');
			                            			} else {
				                            			echo htmlspecialchars($value['description'], ENT_QUOTES, 'UTF-8');
				                            		}
			                            		} else {
			                            			echo htmlspecialchars($value['description'], ENT_QUOTES, 'UTF-8');
			                            		}
			                            		?>
			                            
			                            </td>
			                        </tr>
									<?php } ?>
									
								<?php } else { ?>
								
									<h4>
										<?php echo $this->Lang->write('directory_categories_help_headline');?>
									</h4>
									<p>
										<?php echo $this->Lang->write('directory_categories_help_text', false);?>
									</p>
								
								<?php } ?>
		
		                    </tbody>
		                  </table>
	                  
	                  </form>
	                </div>
	              </div>
			
			</div>
		</div>
	</div>
</div>

<script>

$(document).ready(function() {

	createDataTable($('#directory-group-table'), 50)
	
//     $('#account-data-table').DataTable( {
//         "paging":   true,
//         "ordering": true,
//         "info":     true,
//         "bDestroy":true,
//         "bPaginate": true,
//         "bLengthChange": true,
//         "bFilter": false,
//         "iDisplayLength": max,
//         "bSort": true,
//         "bAutoWidth": false
//     } );
} );


</script>
       
