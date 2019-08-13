my = {};
my.my = function(){//首页
	var self = this;
	self.styles();
	self.stopTouchendPropagationAfterScroll();
	self.my_operate();
	if(Htmlload){
		self.Html_load();
	}
	self.search_operate();
}
my.my_operate = function(){
	var self = this;
	jQuery('.book-content .book').on('touchend',function(){//创建新文集
		var urls = jQuery(this).data('href');
		if(urls){
			window.location.href = urls;
		}
		return false;
	});
	jQuery('.book-content .book').each(function(){
		if(!jQuery(this).hasClass('add')){
			var w = jQuery(this).find('.book-arrange').width();
			var h = jQuery(this).find('.book-arrange').height();
			self.setImage(jQuery(this).find('img').get(0),w,h);
		}
	})
}
my.search_operate = function(){
	if(jQuery.trim(Keyword) != ''){
		jQuery('#searchBar').addClass('weui-search-bar_focusing');
		jQuery('#searchInput').val(Keyword);
		jQuery(document).on('touchend','#searchCancel',function(){
			var i = jQuery(this).data('index');
			if(i == 'index'){
				window.location.href = MOD_URL;
			}else if(i == 'archive'){
				window.location.href = MOD_URL+'&op=archive';
			}else if(i == 'delete'){
				window.location.href = MOD_URL+'&op=delete';
			}
			
		})
	}
}
my.my_search = function(form,index){
	var txt = jQuery(form).find('#searchInput').val();
	if(jQuery.trim(txt) == ''){
		jQuery.toptip(__lang.search_content_cannot_be_empty, 'error');
		return false;
	}
	if(index == 'index'){
		window.location.href = MOD_URL+'&keyword='+txt;
	}else if(index == 'archive'){
		window.location.href = MOD_URL+'&op=archive'+'&keyword='+txt;
	}else if(index == 'delete'){
		window.location.href = MOD_URL+'&op=delete'+'&keyword='+txt;
	}
	
}
my.my_cleate = function(){
	var self = this;
	//	点击样式处理
	jQuery('.background-choose').on('touchend', function() {
		jQuery('#newcorpus_color').val(jQuery(this).data('color'));
		if(jQuery(this).find('i').length){
			jQuery(this).html('');	
			jQuery('#newcorpus_color').val('');
		}else{
			jQuery(this).html('<i class="icon-white dzz dzz-done"></i>');	
			jQuery(this).siblings().html('');
		}
		return false;
	});
	jQuery(document).on('touchend','.add-img-del',function(){
		jQuery(this).closest('li').remove();
		return false;
	})
	jQuery(document).on('touchend','.add-img-del',function(){
		jQuery(this).closest('li').remove();
		return false;
	})
	jQuery('#switchCP').change(function(){
		if(jQuery(this).prop("checked")){
			jQuery(this).val(1);
		}else{
			jQuery(this).val(0);
		}
	})
	self.my_cleate_fileupload();
}
my.my_cleate_fileupload = function(){
	var self = this;
	jQuery('#uploaderInput').fileupload({
		url: MOD_URL+'&op=ajax&do=imageupload',
		dataType: 'json',
	    autoUpload: true,
	    maxFileSize: 20000000, // 20MB
	    maxChunkSize: 2000000, //2M
	    acceptFileTypes: new RegExp("(\.|\/)([jpeg|jpg|gif|png|bmp])$", 'i'),
	    sequentialUploads: true,
	    add: function(e,data){
	    	data.content = jQuery(this).closest('.weui-uploader__input-box').siblings('ul');
	        jQuery.each(data.files, function(index, file) {				
				var ext = file.name.split('.').pop().toLowerCase();
				if(jQuery.inArray(ext, ['jpg', 'jpeg', 'gif', 'png', 'bmp']) > -1) {
					var img = 'dzz/images/default/thumb.png';
				} else {
					var img = 'dzz/images/extimg/' + ext + '.png';
				}
				data.list = jQuery('<li class="weui-uploader__file weui-uploader__file_status"  style="background-image:url('+img+')"><div class="weui-uploader__file-content">0%</div></li>');
				data.content.html(data.list)
			});
			data.process().done(function() {
				data.submit();
			});
	    },
	    progress : function(e, data) {
			var index = 0;
			var progress = parseInt(data.loaded / data.total * 100, 10);
			data.list.find('.weui-uploader__file-content').text(progress+'%')
	    },
	    done: function(e, data) {
			jQuery.each(data.result.files, function(index, file) {
				if(file.error) {
					data.list.remove();
					jQuery.toptip(file.error, 'error');
				} else {
				  	var html = '<li class="weui-uploader__file"><img class="thumbnail" style="width:100%;height:100%" src="'+file.data.img+'" /><input name="cover" type="hidden" value="'+file.data.aid+'" /><div class="dzz dzz-close add-img-del"></div></li>';
					data.list.replaceWith(html);
				}
			});
	    }
	}); 
}
my.my_cleate_formsubmit = function(form){
	var input = jQuery(form).find('textarea[name="name"]');
	if(jQuery.trim(input.val()) == '') {
		jQuery.toptip(__lang.collection_name_cannot_be_empty, 'error');
		return false;
	}
	jQuery.post(form.action, jQuery(form).serialize(), function(json) {
		if(json.code == 200){
			window.location.href = json.referer;
		}else{
			jQuery.toptip(__lang.creation_unsuccess, 'error');
		}
		
	}, 'json');
}

my.my_archive = function(){//归档
	var self = this;
	self.styles();
	self.my_archive_operate();
	self.search_operate();
}
my.my_archive_operate = function(){//归档操作
	var self = this;
	self.stopTouchendPropagationAfterScroll();
	jQuery(document).on('touchend','.activate-book span',function(){
		var t = jQuery(this);
		var cid = t.data('cid');
		jQuery.post(MOD_URL+'&op=list&cid='+cid+'&do=ajax&operation=restore',function(json){
			if(json.msg == __lang.recovery_was_successful){
				t.closest('.book').fadeOut(function(){
					t.closest('.book').remove();
				})
				jQuery.toptip(__lang.successful_activation, 'success');
			}else{
				jQuery.toptip(__lang.activation_failed, 'error');
				return false;
			}
		},'json');
	})
	self.Html_load();
}
my.Html_load = function(){
	var loads = jQuery('.header-content');
	var loading = false;
    loads.infinite().on("infinite", function() {//滚动加载
      	if(loading) return;
      	loading = true;
      	if (jQuery(".weui-loadmore").length > 0) {
        	var url = jQuery(".weui-loadmore").data("url");
        	jQuery.ajax({
            	url: url,
            	type: 'get',
            	success: function(res) {
            		var html = jQuery(res);
                	jQuery(".weui-loadmore").replaceWith(html);
                	loading = false;
            	}
        	})
      	} else {
        	loads.destroyInfinite();
      	}
    });
}
my.creat_group = function(){//创建小组
	var self = this;
	jQuery('.js_item').on('touchend', function() {//页面切换
		var id = jQuery(this).data('id');
		window.pageManager.go(id);
	});
	jQuery(document).on('touchend','.choose1', function() {//小组类型
		var val = jQuery('input:radio[name="radio1"]:checked').val();
		if(val == 1) {
			jQuery('.panel-type').find('span').text(__lang.privately_owned);
			jQuery('.panel-type').find('input').val(1);
		} else {
			jQuery('.panel-type').find('span').text(__lang.open);
			jQuery('.panel-type').find('input').val(0);
		}
		home();
	});
	jQuery(document).on('touchend', '.choose2', function() {//成员允许创建
		var closest = jQuery(this).closest('.page');
		var val = '';
		var sum = 0;
		closest.find('.weui-check').each(function() {
			if(jQuery(this).is(':checked')) {
				if(jQuery(this).val() == 1) {
					var txt = __lang.private_collection;
				} else if(jQuery(this).val() == 4) {
					var txt = __lang.public_corpus;
				} else if(jQuery(this).val() == 2) {
					var txt = __lang.group_of_public_works;
				}
				val += ' ' + txt;
				sum += parseInt(jQuery(this).val());
			}
		})
		jQuery('.user-cleat').find('input').val(sum);
		jQuery('.user-cleat').find('span').text(val);
		home();
	})
	jQuery(document).on('touchend', '.choose3', function() {//成员要求
		var val = jQuery('input:radio[name="user1"]:checked').val();
		if(val == 0) {
			jQuery('.user').find('span').text(__lang.anyone);
		} else {
			jQuery('.user').find('span').text(__lang.in_group);
		}
		jQuery('.user').find('input').val(val);
		home();
	})
	jQuery(document).on('touchend', '.choose4', function() {//成员文集移除
		var val = jQuery('input:radio[name="user_book1"]:checked').val();
		if(val == 0) {
			jQuery('.user-del').find('span').text(__lang.no_remove);
		} else {
			jQuery('.user-del').find('span').text(__lang.allow_to_remove);
		}
		jQuery('.user-del').find('input').val(val);
		home();
	})
}
my.addorg_submit = function(form){
	var input = jQuery(form).find('input:text[name="name"]');
	if(input.val() == '') {
		input.focus();
		return false;
	}

	jQuery.post(form.action, jQuery(form).serialize(), function(json) {
		if(json.code == 200){
			window.location.href = json.referer;
		}else{
			jQuery.toptip(__lang.do_failed, 'error');
		}
		
	}, 'json');
	return false;
}
my.styles = function(){
	var h = jQuery(document).outerHeight(true);
	if(jQuery('.footer-simple').length){
		var h1 = jQuery('.footer-simple-find').height();
		jQuery('.header-content').css('height',h-h1);
		jQuery('.footer-simple').css('height',h1);
	}else{
		jQuery('.header-content').css('height',h);
	}
	
}
my.setImage = function(img,clientWidth,clientHeight,scale){
	if(clientWidth=='100%') clientWidth=jQuery(img).parent().width();
	if(clientHeight=='100%') clientHeight=jQuery(img).parent().height();
	if(jQuery(img).length){
		imgReady(img.src, function () {
			width=this.width; 
			height=this.height;
			
			var r0=clientWidth/clientHeight;
			var r1=width/height;
			if(r0>r1){//width充满
				if(!scale){
					w=width>clientWidth?clientWidth:width;
				}else{
					w=clientWidth;
				}
				h=w*(height/width);
			}else{
				if(!scale){
				  h=height>clientHeight?clientHeight:height;
				}else{
					h=clientHeight;
				}
				w=h*(width/height);
			}
			
				if(width<=clientWidth && height<=clientHeight ){
					if(!scale){
						w=width;
						h=height;
					}
					jQuery(img).css('margin-top',(clientHeight-h)/2)
					.css('margin-left',(clientWidth-w)/2)
					.css('width',w)
					.css('height',h);
				}else if(height<clientHeight && width>clientWidth){
					if(!scale){
						w=clientWidth;
						h=w*(height/width);
					}
					
					jQuery(img).css('margin-top',(clientHeight-h)/2)
					.css('margin-left',(clientWidth-w)/2)
					.css('width',w)
					.css('height',h);	
				}else if(height>clientHeight && width<clientWidth){
					if(!scale){
						h=clientHidth;
						w=h*(width/height);
					}
					jQuery(img).css('margin-top',(clientHeight-h)/2)
					.css('margin-left',(clientWidth-w)/2)
					.css('width',w)
					.css('height',h);	
				}else{
					jQuery(img).css('margin-top',(clientHeight-h)/2)
					.css('margin-left',(clientWidth-w)/2)
					.css('width',w)
					.css('height',h);
				}
			
		});
	}
}
my.stopTouchendPropagationAfterScroll = function(){
    var locked = false;
    window.addEventListener('touchmove', function(ev){
        locked || (locked = true, window.addEventListener('touchend', stopTouchendPropagation, true));
    }, true);
    function stopTouchendPropagation(ev){
        ev.stopPropagation();
        window.removeEventListener('touchend', stopTouchendPropagation, true);
        locked = false;
    }
}
