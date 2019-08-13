/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

function setSave(name,val,orgid){
	 if(name=='title'){
		 if(val==''){
			 showmessage(__lang.name_cannot_be_empty,'danger',1000,1);
			 jQuery('#title_1').focus();
			 return;
		 } 
	 }
	 jQuery.post(ajaxurl+'&do=setSave&orgid='+orgid,{name:name,val:val});
}
function setImage(img,clientWidth,clientHeight,scale){
	if(clientWidth=='100%') clientWidth=jQuery(img).parent().width();
	if(clientHeight=='100%') clientHeight=jQuery(img).parent().height();
	
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
var converting=0;
function convert_waiting(url,success,failure,delay){
	if(!delay) delay=5000;
	converting=1;
	jQuery.ajax({
				 type:'get',
				 url:url,
				 dataType:"json",
				 data:{},
				 success:function(json){
					 if(json.msg=='converting'){
						 url=url.replace(/&cronid=\d+/i,'').replace(/&t=\d+/i,'')+'&cronid='+json.cronid+'&t='+new Date().getTime();
						 window.setTimeout(function(){convert_waiting(url,success,failure,delay)},delay);
					 }else{
						 converting=0;
					 	if(typeof success=='function') success(json);
					 }
				 },
				 error:function(XMLHttpRequest, textStatus, errorThrown){
					//alert(XMLHttpRequest.status);
				   // alert(XMLHttpRequest.readyState);
					//alert(textStatus);
					converting=0;
					if(typeof failure=='function') failure();
					//showmessage('<i class="fa fa-alert"></i>转换错误！','danger',3000,1);
				 }
			 });
	
}
function publish_waiting(url,success,failure,delay){
	if(!delay) delay=3000;
	jQuery.ajax({
				 type:'get',
				 url:url,
				 dataType:"json",
				 data:{},
				 success:function(json){
					 if(json.url){
						 url=json.url+'&t='+new Date().getTime();
						 if(json.msg)	 showmessage(json.msg,'success',0,1);
						 else if(json.error) showmessage(json.error,'danger',0,1);
						 window.setTimeout(function(){publish_waiting(url,success,failure,delay)},delay);
					 }else{
						
					 	if(typeof success=='function') success(json);
					 }
				 },
				 error:function(XMLHttpRequest, textStatus, errorThrown){
					//alert(XMLHttpRequest.status);
				   // alert(XMLHttpRequest.readyState);
					//alert(textStatus);
					if(typeof failure=='function') failure();
					//showmessage('<i class="fa fa-alert"></i>转换错误！','danger',3000,1);
				 }
			 });
	
}
function waiting(url,process,success,failure,delay){
	if(!delay) delay=3000;
	jQuery.ajax({
				 type:'get',
				 url:url,
				 dataType:"json",
				 data:{},
				 success:function(json){
					 if(typeof process=='function') process(json);
					 if(json.url){
						 url=json.url+'&t='+new Date().getTime();
						 window.setTimeout(function(){waiting(url,process,success,failure,delay)},delay);
					 }else{
						 if(typeof success=='function') success(json);
					 }
				 },
				 error:function(XMLHttpRequest, textStatus, errorThrown){
					//alert(XMLHttpRequest.status);
				   // alert(XMLHttpRequest.readyState);
					//alert(textStatus);
					
					if(typeof failure=='function') failure();
					//showmessage('<i class="fa fa-alert"></i>转换错误！','danger',3000,1);
				 }
			 });
	
}