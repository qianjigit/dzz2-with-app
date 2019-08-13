my_view = {};
my_view.ctree = function(){//目录
	var self = this;
	self.children = true;
	self.children_id = [];
	self.children_j = 0;
	self.styles();
	jQuery.showLoading();
	self.ctree_load(0);//加载内容
	self.ctree_operate();
	self.stopTouchendPropagationAfterScroll();
}
my_view.ctree_load1 = function(){//加载内容
	var self = this;
	jQuery.getJSON(MOD_URL+'&op=list&do=ctree&operation=getParentFid&fid='+Fid+'&cid='+Cid,function(json){
		self.children = false;
		if (json[Fid].length > 1) {
			json[Fid].splice(json[Fid].length-1,1);
			self.children_id = json[Fid];
			self.ctree_icon_arrows(jQuery('#ctree_'+json[Fid][0]));
		}
	},'json');
}
my_view.ctree_load = function(id){//加载内容
	var self = this;
	if(id > 0){
		var obj = jQuery('#ctree_'+id)
	}else{
		var obj = jQuery('.tree-content');
	}
	var html1 = '';
	if(id > 0){
		var html1 =  jQuery('<ul><li><i class="weui-loading"></i><span class="weui-loadmore__tips">正在加载</span></li></ul>');
		obj.append(html1);
	}
	jQuery.get(MOD_URL +'&op=list&do=ctree&operation=getchildren&id='+id+'&t='+new Date().getTime()+'&cid='+Cid,function(json){
		if(json.length){
			var html =  '<ul>';
			for(var i = 0; i<json.length;i++){				
				if(json[i].children){
					html +=  '<li id="ctree_'+json[i].id+'" data-id="'+json[i].id+'" data-state="'+json[i].children+'" data-type="'+json[i].type+'"><i class="jstree-icon-folder icon-book"></i>';
					html += '<i class="dzz dzz-arrow-dropright icon-arrows-close icon-arrows"></i><span>'+json[i].text+'</span>';
				}else{
					html +=  '<li id="ctree_'+json[i].id+'" data-id="'+json[i].id+'" data-state="'+json[i].children+'" data-type="'+json[i].type+'"><i class="jstree-icon-file icon-book"></i>';
					html += '<span>'+json[i].text+'</span>';
				}	
				if(Perm > 2){
					html +=  '<i class="dzz dzz-more open-popup" data-target="#showactionSheet"></i></li>';
				}
			}
			html +=  '</ul>';
			html = jQuery(html);
		}
		if(id > 0){
			html1.replaceWith(html);
		}else{
			obj.append(html);
		}
		if(Fid > 0 && self.children){//是否有子级		
			self.ctree_load1();
		}
		if(self.children_id.length && self.children_id[self.children_j]){//有子级时，需要把子级加载出来
			self.ctree_icon_arrows(jQuery('#ctree_'+self.children_id[self.children_j]));
		}
		if(self.children_id.length){//滚动到指定位置
			jQuery('.header-content').animate({
				'scrollTop': jQuery('#ctree_'+self.children_id[0])['position']().top
			}, 350);
		}else{
			if(jQuery('#ctree_'+Fid).length){
				jQuery('.header-content').animate({
					'scrollTop': jQuery('#ctree_'+Fid)['position']().top
				}, 350);
			}
		}
		var h = jQuery('.header-content').scrollTop(); 
		var h1 = jQuery(document).height(); 
		if(h > h1){
			jQuery('.back-top').show();
		}
		jQuery.hideLoading();
		self.children_j++;
		return false;
	},'json');
	
}

my_view.ctree_operate = function(){//目录
	var self = this;
	self.back_top();
	jQuery(document).on('touchend','.tree span',function(){//文字点击
		var t = jQuery(this).parent('li');
		if(!t.hasClass('tree-content')){
			var fid = t.data('id');
			window.location.href = MOD_URL+'&op=list&cid='+Cid+'&do=view&fid='+fid+'&ver=&t='+new Date().getTime();
		}
	})
	jQuery(document).on('touchend','.tree .icon-arrows',function(){//箭头点击
		var parent = jQuery(this).parent('li');
		self.ctree_icon_arrows(parent);
		return false;
	})

	if(Perm > 2){
		jQuery(document).on('touchend','.tree .open-popup',function(){//操作点击
			var t = jQuery(this);
			var parent = jQuery(this).parent('li');
			var type = parent.data('type');
				var html = jQuery(
					'<a href="javascript:;" class="weui-grid js_grid more-item" data-set="1">'+
						'<div class="weui-grid__icon item-icon">'+
							'<i class="dzz dzz-menu2"></i>'+
						'</div>'+
						'<p class="weui-grid__label">'+__lang.new_level+'</p>'+
					'</a>'+
					'<a href="javascript:;" class="weui-grid js_grid more-item" data-set="2">'+
						'<div class="weui-grid__icon item-icon">'+
							'<i class="dzz dzz-netdisk-edit"></i>'+
						'</div>'+
						'<p class="weui-grid__label">'+__lang.new_subordinate+'</p>'+
					'</a>'+
					'<a href="javascript:;" class="weui-grid js_grid more-item" data-set="3">'+
						'<div class="weui-grid__icon item-icon">'+
							'<i class="dzz dzz-netdisk-edit"></i>'+
						'</div>'+
						'<p class="weui-grid__label">'+__lang.rechristen+'</p>'+
					'</a>'+
					'<a href="javascript:;" class="weui-grid js_grid more-item" data-set="4">'+
						'<div class="weui-grid__icon item-icon">'+
							'<i class="dzz dzz-delete"></i>'+
						'</div>'+
						'<p class="weui-grid__label">__lang.delete</p>'+
					'</a>'
				);
			jQuery('#showactionSheet .modal-content').html(html);
			self.ctree_operate_bottom(html,parent,t);
		});
		jQuery(document).on('touchend','.ctree-bottom-operate',function(){//底部操作

			var t = jQuery(this);
			var set = t.data('set');
			switch(set){
				case 1://标星
					jQuery.post(MOD_URL+'&op=ajax&do=setStar',{'cids':Cid},function(json){
						if(json.msg == 'success'){
							if(json.state){//标星
								t.addClass('active');
							}else{//取消标星
								t.removeClass('active');
							}
						}
					},'json');
				break;
				case 2://封面设置
					window.location.href = MOD_URL+'&op=menu&do=topic&operation=cover&cid='+Cid;
				break;
				case 3://归档
					jQuery.confirm(__lang.make_sure_to_archive_this_corpus,__lang.archive, function() {
					  	//点击确认后的回调函数
					  	jQuery.post(MOD_URL+'&op=list&do=ajax&operation=archive',{'cid':Cid},function(json){
							if(json.msg == '归档成功'){
								window.location.href = MOD_URL+'&op=archive';
							}else{
								jQuery.toptip(json.msg, 'error');
								return false;
							}
						},'json');
					});
				break;
				case 4://删除
					jQuery.confirm(__lang.delete_this_collection_temporarily,__lang.delete, function() {
					  	//点击确认后的回调函数
						jQuery.post(MOD_URL+'&op=list&do=ajax&operation=delete' ,{'cid':Cid,'t': new Date().getTime()}, function(json) {
							if(json.error) {
								jQuery.toptip(json.error, 'error');
								return false;
							}else {
								window.location.href = MOD_URL+'&op=delete';				
							}
						}, 'json');
					});
				break;
				case 5://更多
					if(t.find('.weui-dropup').hasClass('hide')){
						t.find('.weui-dropup').removeClass('hide');
					}else{
						t.find('.weui-dropup').addClass('hide');
					}
				break;
				case 6://恢复
					jQuery.confirm(__lang.determine_to_restore_this_collection,__lang.recovery, function() {
					  	//点击确认后的回调函数
						jQuery.post(MOD_URL+'&op=list&do=ajax&operation=restore', {'cid':Cid,'t': new Date().getTime()
						}, function(json) {
							if(json.error) {
								jQuery.toptip(json.error, 'error');
								return false;
							}else {
								window.location.href = MOD_URL;				
							}
						}, 'json');
					});
				break;
				case 7://彻底删除
					jQuery.confirm(__lang.sure_delete_this_corpus_completely,__lang.completely_delete, function() {
					  	//点击确认后的回调函数
						jQuery.get(MOD_URL+'&op=list&do=ajax&operation=delete', {
							    'cid':Cid,
								't': new Date().getTime()
						}, function(json) {
							if(json.error) {
								jQuery.toptip(json.error, 'error');
								return false;
							}else {
								window.location.href = MOD_URL+'&op=delete';				
							}
						}, 'json');
					});
				break;
				case 8://成员
					window.location.href = MOD_URL+'&op=list&do=user&cid='+Cid;
				break;
				case 9://分享
					jQuery.showLoading();
			        var t = jQuery.modal({
			            title: "",
			            text: '<div class="shareClose"><i class="dzz dzz-close"></i></div><img class="img-thumbnail qrcodeImg" src="" alt="">',
			            buttons:[{ text: __lang.copy_address, className:"shareBtn1", onClick: function(){				 									
			                    var client = new Clipboard('.shareBtn1');
			                    client.on('success',function (e) {
			                        jQuery.toptip(__lang.copy_success, 1000, 'success');
			                    })
			                    client.on('error',function (e) {
			                        jQuery.toptip(__lang.copy_failed, 1000, 'error');
			                    })
								jQuery.closeModal();
			                }},
			            ]
			        });
					t.find('.shareClose').on('touchend',function(){
						jQuery.closeModal();
						return false;
					})
			        
			        jQuery.ajax({
			            url:MOD_URL+'&op=menu&do=topic&operation=shareurl',
			            type:'post',
						data:{'cid':Cid},
			            dataType:'json',
			            success:function (res) {
			                if(res.code==200) {
								jQuery('.qrcodeImg').attr('src',res.qrcode);
		            			jQuery('.shareBtn1').attr('data-clipboard-text',res.url);
						        
			                }else{
								jQuery.toptip(__lang.data_request_failed, 1000, 'error');
			                }
			                jQuery('.weui-dropup').addClass('hide');
			                jQuery.hideLoading();
			            }
			        })
				break;
			}
			return false;
		})
	    jQuery(document).click(function(e){
	    	if(!jQuery(e.target).closest('.weui-dropup').lenght && !jQuery(e.target).hasClass('weui-dropup')){
	    		jQuery('.weui-dropup').addClass('hide');
	    	}
	    })
	}
}
my_view.ctree_icon_arrows = function(parent){
	var self = this;
	var state = parent.data('state');
	var fid = parent.data('id');
	var t = parent.children('.icon-arrows');


	
	if(state){
		parent.data('state',false);

		
		self.ctree_load(fid)
	}
	if(t.hasClass('icon-arrows-close')){
		t.removeClass('icon-arrows-close').addClass('icon-arrows-open');
		t.siblings('.icon-book').removeClass('jstree-icon-folder').addClass('jstree-icon-openfolder');
		t.siblings('ul').show();
	}else{
		t.addClass('icon-arrows-close').removeClass('icon-arrows-open');
		t.siblings('.icon-book').addClass('jstree-icon-folder').removeClass('jstree-icon-openfolder');
		t.siblings('ul').hide();
	}
}
my_view.ctree_operate_bottom = function(obj,parent,t){//树型底部操作
	var self = this;
	obj.on('touchend',function(){
		var set = jQuery(this).data('set');
		var fid = parent.data('id');
		jQuery.closePopup();
		switch(set){
			case 1://创建同级
				jQuery.prompt({
				  	title: __lang.create_the_same_level,
				  	text: '',
				  	input: __lang.new_classification,
				  	empty: true, // 是否允许为空
				  	onOK: function (input) {//点击确认
				  		if(jQuery.trim(input) == ''){
				  			input = __lang.new_classification;
				  		}
  						if(parent.parent('ul').parent('li').hasClass('tree-content')){
							var pfid = '#';
						}else{
							var pfid = parent.parent('ul').parent('li').data('id');
						}
						var pos = -1;
				  		var arr = [];
				  		parent.parent('ul').children().each(function(){
				  			arr.push(jQuery(this).data('id'));
				  		})
				  		var pos=jQuery.inArray(parent.data('id'),arr)+1;
				  		jQuery.post(MOD_URL+'&op=list&do=ctree&operation=create&cid='+Cid,{'pfid':pfid,'disp':pos,'type':'folder','t':new Date().getTime()},function(json){
				    		if(!json || json.error){
								jQuery.toptip(__lang.creation_unsuccess, 'error');
								return false;
							}else if(json.id>0){						
								jQuery.post(MOD_URL+'&op=list&do=ctree&operation=rename&cid='+Cid,{'fid':json.id,text:input,'t':new Date().getTime()},function(obj){
									if(obj.msg == 'success'){
										var html =  jQuery('<li data-id="'+json.id+'" data-state="false" data-type="folder"><span><i class="jstree-icon-file icon-book"></i>'+obj.fname+'</span><i class="dzz dzz-more open-popup" data-target="#showactionSheet"></i></li>');
										parent.after(html);
									}else{
										jQuery.toptip(__lang.creation_unsuccess, 'error');
										return false;
									}
								},'json');
							}
							
						},'json');
				  	}
				});
			break;
			case 2://创建下级
				jQuery.prompt({
				  	title: __lang.create_subordinate,
				  	text: '',
				  	input: __lang.new_classification,
				  	empty: true, // 是否允许为空
				  	onOK: function (input) {//点击确认	
				  		if(jQuery.trim(input) == ''){
				  			input = __lang.new_classification;
				  		}
				  		jQuery.post(MOD_URL+'&op=list&do=ctree&operation=create&cid='+Cid,{'pfid':fid,'disp':-1,'type':'folder','t':new Date().getTime()},function(json){
							if(!json || json.error){
								jQuery.toptip(__lang.creation_unsuccess, 'error');
								return false;
							}else if(json.id>0){
								jQuery.post(MOD_URL+'&op=list&do=ctree&operation=rename&cid='+Cid,{'fid':json.id,text:input,'t':new Date().getTime()},function(obj){
									if(obj.msg == 'success'){
										if(parent.data('state')){
											self.ctree_icon_arrows(parent);
										}else{
											if(t.siblings('ul').length){
												var html =  jQuery('<li data-id="'+json.id+'" data-state="false" data-type="folder"><span><i class="jstree-icon-file icon-book"></i>'+obj.fname+'</span><i class="dzz dzz-more open-popup" data-target="#showactionSheet"></i></li>');
												t.siblings('ul').append(html);
											}else{
												var html =  jQuery('<i class="dzz dzz-arrow-dropright icon-arrows icon-arrows-open"></i><ul><li data-id="'+json.id+'" data-state="false" data-type="folder"><span><i class="jstree-icon-file icon-book"></i>'+obj.fname+'</span><i class="dzz dzz-more open-popup" data-target="#showactionSheet"></i></li></ul>');
												parent.children('.icon-book').removeClass('jstree-icon-file').addClass('jstree-icon-openfolder');
												parent.append(html);
											}
										}
									}else{
										jQuery.toptip(__lang.creation_unsuccess, 'error');
										return false;
									}
								},'json');
							}
						},'json');						
				  	}
				});
			break;
			case 3://重新命名
				var txt = parent.children('span').text();
				jQuery.prompt({
				  	title: __lang.rename,
				  	text: '',
				  	input: txt,
				  	empty: true, // 是否允许为空
				  	onOK: function (input) {//点击确认	
				  		if(jQuery.trim(input) == ''){
				  			input = txt;
				  		}
						jQuery.post(MOD_URL+'&op=list&do=ctree&operation=rename&cid='+Cid,{'fid':fid,text:input,'t':new Date().getTime()},function(obj){
							if(obj.msg == 'success'){
								parent.children('span').text(obj.fname);
							}else{
								jQuery.toptip(__lang.creation_unsuccess, 'error');
								return false;
							}
						},'json');
				  	}
				});
			break;
			case 4://删除分类
				var txt = parent.find('span').text();
				jQuery.confirm(txt,__lang.confirm_deletion, function() {
				  	jQuery.get(MOD_URL+'&op=list&do=ctree&operation=delete&cid='+Cid,{'fid':fid,'t':new Date().getTime()},function(json){
						if(json == 'success'){
							parent.fadeIn(function(){
								parent.remove();
							});
						}else{
							jQuery.toptip(__lang.delete_unsuccess, 'error');
						}
					});
			  	});
			break;
		}
		return false;
	})
}

my_view.view = function(){//显示文章
	var self = this;
	self.styles();
	self.back_top();
	self.stopTouchendPropagationAfterScroll();
	jQuery('.view-content-text').click(function(e){
		var w = jQuery(this).width();
		w = w/2;
       	var xx = e.originalEvent.x || e.originalEvent.layerX || 0; 
       	if(xx < w){
       		var fid = jQuery('#nav_prev').val();
       		if(fid == 'cover'){
       			jQuery.toast(__lang.this_is_the_firs_chapter,'text');
       			return false;
       		}
       	}else{
       		var fid = jQuery('#nav_next').val();
       		if(fid == 0){
       			jQuery.toast(__lang.this_is_the_last_chapter, 'text');
       			return false;
       		}	
       	}
       	window.location.href = MOD_URL+'&op=list&cid='+Cid+'&do=view&fid='+fid+'&ver=&t='+new Date().getTime();
    });
    jQuery(document).on('touchend','.more-item',function(){
    	var set = jQuery(this).data('set');
    	switch(set){
    		case 1://目录
    			window.location.href = MOD_URL+'&op=list&cid='+Cid+'&fid='+Fid;
    		break;
    		case 2://编辑
				window.location.href = MOD_URL+'&op=list&do=newdoc&cid='+Cid+'&fid='+Fid+'&t='+new Date().getTime();
    		break;
    		case 3://分享
    			jQuery.closePopup();
		        jQuery.showLoading();
		        var t = jQuery.modal({
		            title: "",
		            text: '<div class="shareClose"><i class="dzz dzz-close"></i></div><img class="img-thumbnail qrcodeImg" src="" alt="">',
		            buttons:[{ text: __lang.copy_address, className:"shareBtn1", onClick: function(){				 									
		                    var client = new Clipboard('.shareBtn1');
		                    client.on('success',function (e) {
		                        jQuery.toptip(__lang.copy_success, 1000, 'success');
		                    })
		                    client.on('error',function (e) {
		                        jQuery.toptip(__lang.copy_failed, 1000, 'error');
		                    })
							jQuery.closeModal();
		                }},
		            ]
		        });
				t.find('.shareClose').on('touchend',function(){
					jQuery.closeModal();
					return false;
				})
		        
		        jQuery.ajax({
		            url:MOD_URL+'&op=menu&do=topic&operation=shareurl',
		            type:'post',
					data:{'cid':Cid,'fid':Fid},
		            dataType:'json',
		            success:function (res) {
		                if(res.code==200) {
							jQuery('.qrcodeImg').attr('src',res.qrcode);
	            			jQuery('.shareBtn1').attr('data-clipboard-text',res.url);
					        
		                }else{
							jQuery.toptip(__lang.data_request_failed, 1000, 'error');
		                }
		                jQuery.hideLoading();
		            }
		        })
    		break;
    		case 4://删除
    			var txt = jQuery('.view-content-title span').text();
    			jQuery.closePopup()
    			jQuery.confirm(txt,__lang.confirm_deletion, function() {
				  	jQuery.get(MOD_URL+'&op=list&do=ctree&operation=delete&cid='+Cid,{'fid':Fid,'t':new Date().getTime()},function(json){
						if(json == 'success'){
					       	var fid = jQuery('#nav_next').val();
				       		if(fid == 0){
				       			fid = jQuery('#nav_prev').val();
								if(fid == 'cover'){
					       			window.location.href = MOD_URL+'&op=list&cid='+Cid+'&fid='+Fid;
					       			return false;
					       		}
				       		}					
					       	window.location.href = MOD_URL+'&op=list&cid='+Cid+'&do=view&fid='+fid+'&ver=&t='+new Date().getTime();					   				       	
						}else{
							jQuery.toptip(__lang.delete_unsuccess, 'error');
						}
					});
			  	});
    		break;		
    	}
    	return false;
    });
}
my_view.back_top = function(){
	jQuery(document).on('touchend','.back-top',function(){//回到顶部
		jQuery('.header-content').animate({
			'scrollTop': 0
		}, 1000);
		jQuery(this).hide();
		return false;
	})
}
my_view.styles = function(){
	var h = jQuery(document).outerHeight(true);
	if(jQuery('.footer-simple').length){
		var h1 = jQuery('.footer-simple-find').height();
		jQuery('.header-content').css('height',h-h1);
		jQuery('.footer-simple').css('height',h1);
	}else{
		jQuery('.header-content').css('height',h);
	}
	
}
my_view.stopTouchendPropagationAfterScroll = function(){
    var locked = false;
    window.addEventListener('touchmove', function(ev){
    	jQuery('.weui-dropup').addClass('hide');
    	if(jQuery('.header-content').scrollTop()>jQuery(document).height()){
    		jQuery('.back-top').show();
    	}else{
    		jQuery('.back-top').hide();
    	} 	
        locked || (locked = true, window.addEventListener('touchend', stopTouchendPropagation, true));
    }, true);
    function stopTouchendPropagation(ev){
        ev.stopPropagation();
        window.removeEventListener('touchend', stopTouchendPropagation, true);
        locked = false;
    }
}