/**
 * LazyLoad (Version 1.1)
 * @author lv ming (akm107@163.com)
 *
 * Create a LazyLoad
 * @example new LazyLoad(containers,options);
 * on Jquery
 *
 */

function LazyLoad(containers, options){
	if(options===undefined&&containers instanceof Object){
		options=containers;
		containers=document;
		}
	this.containers=[$(containers)||document];
	this.options=$.extend({
		diff:500,
		flag:'lazyload'
		},options);
	this.init();
	}
LazyLoad.prototype={
	init:function(){
		this.threshold=this._getThreshold();
		this._filter();
		this._initLoad();
		},
	_filter:function(){
		var containers=this.containers,
			i, len,
			lazyImgs=[];
		for(i=0, len=containers.length; i<len; i++){
			imgs=$('img', containers[i]);
			lazyImgs=this._filterImg(imgs);
			}
		this.imgs=lazyImgs;
		},
	_filterImg:function(imgs){
		var ret=[],
			i, len, lazySrc,
			flag=this.options.flag;
		for(i=0, len=imgs.length; i<len; i++){
			lazySrc=imgs[i].getAttribute(flag);
			if(lazySrc){
				ret.push(imgs[i]);
				}
			}
		return ret;
		},
	_initLoad:function(){
		var timer, that=this;
		$(window).bind('scroll', loader);
		$(window).bind('resize', function(){
			that.threshold=that._getThreshold();
			loader();
			});
		if(this.imgs.length){
			loadImgs(this.imgs);
			}
		function loader(){
			if(timer){return;}
			timer=setTimeout(function(){loadImgs(that.imgs); timer=null;}, 100);
			}
		function loadImgs(imgs){
			that._loadImgs(imgs);
			if(that.imgs.length===0){
				$(window).unbind('scroll', loader);
				$(window).unbind('resize', loader);
				}
			}
		},
	_loadImgs:function(imgs){
		var scrollTop=$(document).scrollTop(),
			threshold=this.threshold+scrollTop,
			flag=this.options.flag,
			ret=[],
			i, len, img, offset, dataSrc;
		for(i=0, len=imgs.length; i<len; i++){
			img=imgs[i];
			offsetTop=$(img).offset().top;
			if(offsetTop<=threshold){
				dataSrc=img.getAttribute(flag);
				if(dataSrc && img.src!=dataSrc){
					img.src=dataSrc;
					img.removeAttribute(flag);
					}
				}else{
					ret.push(img);
				}
			}
		this.imgs=ret;
		},
	_getThreshold:function(){
		var vh=$(window).height();
		return vh+this.options.diff;
		}
	};