<script type="text/javascript" src="<?php echo base_url();?>assets/fancybox/jquery.fancybox.js?v=2.1.5"></script>
<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/fancybox/jquery.fancybox.css?v=2.1.5" media="screen" />

<script type="text/javascript">

	$(window).load(function(){
		
		$("img.lazy").each(function () {
          var src = $(this).attr("data-src");
          $(this).attr("src", src).removeAttr("data-src");
       	});
       
		
		$(".fancybox").fancybox();
		
		var menuSortableObj = '#sortable';
    	var isDragable = false;
    	
		$(menuSortableObj).sortable({
			
			cursor:'move',	
			revert: true,
  			placeholder: "sortable-ghost",
  		
			cursorAt: {
		         top: -18
		    },
			sort: function(event, ui) {	
				ui.helper.css({
					'z-index':'9999',
				});
				
				ui.placeholder.css({
	          		visibility: 'visible',
	          		opacity: '.5',  	          		
					border:'2px dashed #000',       			
	          	}).html(ui.item.html());	          	
	          	
	        },
	       receive:function(event,ui){
	        	updateImageSequence(ui.item.parent().children('li'));
	        },
          	stop: function( event, ui ) {
          		isDragable = false;
          		updateImageSequence($(this).find('li'));
		  	}
		});
		$( "#sortable" ).disableSelection();
		
		  	
		$("#add_event_photo").fileupload({
            dataType: 'json',
            done: function (e, data) {
            	$('body').css('cursor', 'auto');
            	$("#uploadError").text('');
                if (data.result.success === 1) {
					var img_url = data.result.url;
					var img_id = data.result.event_photo_id;
					var thumb_url = data.result.thumb_url;					
					var liHTML = '<li id="'+img_id+'"><img src="'+thumb_url+'" alt="img" /><div class="actions"><div class="inner"><a href="'+img_url+'" class="fancybox"><i class="fa fa-arrows"></i></a><a class="remove" href="javascript:;"><i class="fa fa-trash"></i></a></div></div></li>';		
					$("#GalleryPhotos").find('ul.slides').append(liHTML);										
					$(menuSortableObj).sortable( "refresh" );
	             }
	             else
	             {
	             	$("#uploadError").text(data.result.msg);
	             }
            }
		});	
		
		$(".remove").live('click',function(){
			var liId = $(this).parent().parent().parent().attr('id');
			if(liId > 0)
			{
				loading();
				$.ajax({         	
		        	url:'<?php echo base_url($this->admin_url."/deleteEventPhoto/".$event_id);?>',
		        	type:'post',
		        	dataType:'json',
		        	data:{id:liId},
		        	success:function(response){
		        		stop_loading()
		        		if(response.success === 1)
		        		{
		        			$("#"+liId).fadeOut(function(){$(this).remove()});
		        		}
		        	}
		        });	
			}
				        
		});	
	});
	
	function updateImageSequence(currentSelector)
	{
		var imageList = Array();		
		$.map(currentSelector, function(el) {	          	 		          	 		          	 
			
      	 	var tmp = {
      	 		'id':el.id,      	 		
      	 		'view_order':$(el).index()+1
      	 	};	                
            imageList.push(tmp);
            
        });
        if(imageList)
        {
        	loading();
        	$.ajax({         	
	        	url:'<?php echo base_url($this->admin_url."/updateEventPhotoViewOrder/".$event_id);?>',
	        	type:'post',
	        	dataType:'json',
	        	data:{data:imageList},
	        	success:function(response){
	        		stop_loading();
	        	}
	        });	
        }
	}
</script>

<div class="userBox-wrap">
	<div class="Pf-btnM file-upload mar-top2">
		<span class="upload-button">
			<label><?php echo translate_phrase('Add Photos') ?></label>
			<input type="file"  multiple data-url="<?php echo base_url($this->admin_url.'/upload_event_photos/'.$event_id) ?>" id="add_event_photo" name="fileToUpload">						
		</span>
	</div>
	<div class="error-msg" id="uploadError"></div>
	<div id="GalleryPhotos">
		<ul class="slides" id="sortable">
			<?php if($event_photos):?>
			<?php foreach($event_photos as $photos):
				$img_thumb_url = base_url('event_photos/'.$event_id.'/thumbs/'.$photos['photo']); 
				$img_url = base_url('event_photos/'.$event_id.'/'.$photos['photo']); 
			?>
			<li id="<?php echo $photos['event_photo_id']?>">
				<img alt="" class="lazy" src="<?php echo base_url('assets/images/load_event.jpg')?>" data-src="<?php echo $img_thumb_url;?>" />	
				<div class="actions">
					<div class="inner">
						<a href="<?php echo $img_url;?>" class="fancybox"><i class="fa fa-arrows"></i></a>
						<a class="remove" href="javascript:;"><i class="fa fa-trash"></i></a>					
					</div>
				</div>				
			</li>
			<?php endforeach;?>
			<?php endif;?>
		</ul>
	</div>				
</div>