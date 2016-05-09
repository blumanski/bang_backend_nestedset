/**
 * @author Blumanski <blumanski@gmail.com>
 */
(function($) {

    $.Directory = function(element, options) {

        var defaults = {
            //foo: 'bar',
           // onFoo: function() {}
        };

        var plugin = this;

        plugin.settings = {}; 

        var $element = $(element),
             element = element;

        // constructor method
        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
            // code goes here
        };
        
        /**
         * @desc Move node in the tree
         * @param int id
         * @param string serialized
         * @param function callback
         */
        plugin.moveBranch = function(id, serialized, callback)
        {
        	var rootid = $('ul.treeroot').data('rootid');
        	
        	var request = $.ajax({

        		dataType: 'json',
        		type: 'post',
        		url: '/directory/ajax/movenode/',
        		data: {
        			id			: parseInt(id),
        			serialized	: serialized,
        			rootid		: parseInt(rootid)
        		},
    		  
    		}).done(function(response){
    			
    			if(response.outcome && response.outcome == 'success') {
    				//location.reload();
    				callback('sucess');
    			}
    			
    		}).fail(function(response){
    			callback('failed');
    			// console.log(response);
    		});
        }
        
        /**
         * Save a new Node to category
         * Ajax request to save new node into nested set structure
         * @param object formElement
         */
        plugin.updateNode = function(formElement, callback)
        {
        	var name		= formElement.find('#name').val();
        	var template	= formElement.find('#template').val();
        	var groups		= formElement.find('#permgroups').val();
        	var rootid		= formElement.find('#rootid').val();
        	var nodeid		= formElement.find('#nodeid').val();
        	
        	if(typeof(nodeid) == 'undefined') {
        		nodeid = '';
        	}
        	
        	var request = $.ajax({

        		dataType: 'json',
        		type: 'post',
        		url: '/directory/ajax/updatenode/',
        		data: {
        			name		: encodeURIComponent(name),
        			template	: encodeURIComponent(template),
        			groups		: JSON.stringify(groups),
        			rootid		: parseInt(rootid),
        			nodeid		: parseInt(nodeid)
        		},
    		  
    		}).done(function(response){
    			
    			callback('done');
    			if(response.outcome && response.outcome == 'success') {
    				
    				window.location.href = "/directory/index/tree/rootid/"+parseInt(rootid)+"/";
    			}
    			
    		}).fail(function(response){
    			// console.log(response);
    		});
        }
 
        /**
         * Save a new Node to category
         * Ajax request to save new node into nested set structure
         * @param object formElement
         */
        plugin.saveNewNode = function(formElement)
        {
        	var name		= formElement.find('#name').val();
        	var position	= formElement.find('#position').val();
        	var template	= formElement.find('#template').val();
        	var groups		= formElement.find('#permgroups').val();
        	var rootid		= formElement.find('#rootid').val();
        	var ctype		= formElement.find('#ctype').val();
        	
        	if(typeof(nodeid) == 'undefined') {
        		nodeid = '';
        	}
        	
        	var request = $.ajax({

        		dataType: 'json',
        		type: 'post',
        		url: '/directory/ajax/addnode/',
        		data: {
        			name		: encodeURIComponent(name),
        			position	: parseInt(position),
        			template	: encodeURIComponent(template),
        			groups		: JSON.stringify(groups),
        			rootid		: parseInt(rootid),
        			ctype		: encodeURIComponent(ctype)
        		},
    		  
    		}).done(function(response){
    			
    			if(response.outcome && response.outcome == 'success') {
    				location.reload();
    			}
    			
    		}).fail(function(response){
    			// console.log(response);
    		});
        }
 
        
        plugin.init();
    };
    

    $.fn.Directory = function(options) 
    {
        return this.each(function() {
            if (undefined == $(this).data('Directory')) {
                var plugin = new $.Directory(this, options);
                $(this).data('Directory', plugin);
            }
        });
    };

})(jQuery);


$(function() {
	
	var Directory = $(this).Directory({});
	
	/** ----------- Nestable ----------------------- */
	var nestable = UIkit.nestable($('.uk-nestable'), { 
		maxDepth: 10,
	});

	nestable.on('change.uk.nestable', function(e, a, c) {

		
		$('#blanko-overlay').show(); 
		
		
		var els = $(this).data('nestable').serialize();

		// move branch to new position
		Directory.data('Directory').moveBranch(c.data('id'), JSON.stringify(els), function(response){
			console.log(response);
			$('#blanko-overlay').hide();
		});
		
	});
	
	$('a.tree-toolbar.delete').on('click', function(event){
		
		var href = $(this).attr('href');
		event.preventDefault();
		
		$('#confirmation-modal').find('.modal-content h4').html(defaultmsg.confirm_headline.msg);
		$('#confirmation-modal').find('.modal-content p').html(defaultmsg.confirm_paragraph.msg);
		
		$('#confirmation-modal').openModal({
			dismissible: true, // Modal can be dismissed by clicking outside of the modal
			opacity: .5, // Opacity of modal background
			in_duration: 300, // Transition in duration
			out_duration: 200, // Transition out duration
			ready: function() { 
			}, // Callback for Modal open
			complete: function(response) { } // Callback for Modal close
		});
		
		$('#confirmation-modal .modal-footer .agree').on('click', function(){
			if(href != '') {
				window.location.href = href;
			}
		});
		
	});
	
	/**
	 * update node data
	 */
	$('form#edit-node-form').on('submit', function(event){
		event.preventDefault();
		$('#blanko-overlay').show(); 
		Directory.data('Directory').updateNode($(this), function(response) {
			$('#blanko-overlay').hide();
		});
	});
	
	/** ----------- Page Events ----------------------- */
	$('#newnode').on('click', function(event){
		$('#nodeform-wrapper, .content-level').toggle();
	})
	
	$('#reset-new-node-form').on('click', function(){
		$('#nodeform-wrapper, .content-level').toggle();
	});
	
	$('#new-node-form').on('submit', function(event){
		event.preventDefault();
		Directory.data('Directory').saveNewNode($(this));
	});
	
	
	/**
	 * Pusher
	 */
	if(typeof(pappkey) != 'undefined') {
		
		var pusher = new Pusher(pappkey, {
		      encrypted: true,
		      cluster: cluster
		    });
		
		  var channel = pusher.subscribe(chanchan);
		  channel.bind('category_change', function(data) {
			  
			  if(data.message && data.identifier) {
				 // send message not to self
				  if(hash && hash != data.identifier) {
					  
					  var urlvars = getUrlVars('directory', function(response){
						
						  if(response == 1) {
							  
							  var urlvars = getUrlVars('tree', function(response){
								  
								  if(response == 1) {
									  
									  var urlvars = getUrlVars('rootid', function(response){
											
										  if(typeof(rooter) != 'undefined' && response == 1) {
											
											  var urlvars = getUrlVars(rooter, function(response){
												  
												  if(response == 1) {
														
													  $('#confirmation-modal').find('.modal-content h4').html('Puster');
														$('#confirmation-modal').find('.modal-content p').html(data.message);
														
														$('#confirmation-modal').openModal({
															dismissible: true, // Modal can be dismissed by clicking outside of the modal
															opacity: .5, // Opacity of modal background
															in_duration: 300, // Transition in duration
															out_duration: 200, // Transition out duration
															ready: function() { 
															}, // Callback for Modal open
															complete: function(response) { } // Callback for Modal close
														});
														
														$('#confirmation-modal .modal-footer .agree').on('click', function(){
																location.reload();
														});
												  }
											  });
										  }
									  });
								  }
							  });
						  }
					  });
					  
				  } else {
					 // location.reload(); 
				  }
			  }
		  });
		
	}
	
	
	
});