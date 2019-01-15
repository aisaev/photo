const COOKIE_LANGUAGE = 'language';
const COOKIE_SENTIMENTAL = 'sent';
const COOKIE_FULL_THUMB = 'fullthumb';
const APP_EVENT = 0, APP_PLACE = 1, APP_PERSON = 2, APP_VIDEO = 3;
const LNG_RU = 'ru', LNG_EN = 'en';
const MAX_PHOTO_W=1280;

var localize={
		'en':{
			'bt': ['Events','Places','People','Video'],
			'lng':'English','snt':'Unfiltered','fth':'Full thumbnails','opt':'Options'},
		'ru':{
			'bt': ["События","Места","Люди","Видео"],
			'lng':'Русский','snt':'Не фильтровать','fth':'Полный размер','opt':'Настройки'}
	}
var readableMonth={'en':['january','february','march','april','may','june','july','august','september','october','novemver','december'],
				   'ru':["января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря"] };

var reloadOnFTH=true;
var isMobile=(navigator.userAgent.match(/(mobile|android)/i)!==null);//(typeof window.orientation!=='undefined');
var mode=1; //1=photoset,0=edit
var app=APP_EVENT;

class Photo {
	constructor(json)
	{
		this.id=json['i'];
		this.event=json['e'];
		this.place=json['l'];
		this.people=json['p']?json['p']:[];
		if(json['c']) {
			this.comment_en = this.comment_ru = smartText(json['c']);
		} else {
			this.comment_ru = json['cr']?smartText(json['cr']):null;
			this.comment_en = json['ce']?smartText(json['ce']):null;
		}
		this.taken_on = json['t']?json['t']:null;
		var fn=''+this.id,pad='0000';
		if(this.id<10000) fn=pad.substring(0,5-fn.length)+fn;
		this.file_name = fn;
		this.dir_name=fn.substring(0,2);
	}
	
	htmlThumbnail() {
		var html='<div class="thumb photo"><a class="pic-det lazy-loading" href="#'+this.id+'" onclick="showDetails('+this.id+');"><img class="lazy" data-src="/tmb/'+
			this.dir_name+'/'+this.file_name+'.jpg"';
		return html+'style="max-height:'+(fullThumbnails?480:320)+'px;"></div>';
	}
	
	comment() {
		var s = (lng==LNG_RU?this.comment_ru:this.comment_en);
		return s==null?'':s;
	}
}

class Event {
	
	constructor(json) {
		this.is_new = false;
		if (json) {
			var yr_curr=(new Date()).getFullYear();
			this.id = json['i'];
			this.date_from = json['f'];
			this.date_to = json['t']?json['t']:this.date_from;
			this.yr = json['f'].substring(0,4);
			if(this.yr>yr_curr) {
				this.yr='Pinned';
				this.date_from = 'Pinned';
				this.date_to = 'Pinned';
			}
			this.sentimental = !!(json['s']&&json['s']==1);
			if(json['d']) {
				this.desc_r = json['d'];
				this.desc_e = json['d'];
			} else {
				if(json['r']) this.desc_r = json['r'];
				this.desc_e = json['e']?json['e']:this.desc_r;
			}
			if(json['n']) this.is_new = true;
		} else {
			this.id=0;
			this.date_from="";
			this.date_to = "";
			this.sentimental = false;
			this.yr=0;
			this.desc_r = "";
			this.desc_e = "";
		}
	}
	
	nameToShow() {
		var rd = this.readableDates(); 
		return (rd==''?'':rd+'. ')+(lng==LNG_RU?this.desc_r:this.desc_e);
	}
	
	readableDates() {
		if (this.date_from=='Pinned') return '';
		var df=this.date_from.split('-'); //0=yyyy,1=mm,2=dd
		var dt=this.date_to.split('-'); //0=yyyy,1=mm,2=dd
		var s='';
		df[1]=parseInt(df[1])-1;
		dt[1]=parseInt(dt[1])-1;
		df[2]=parseInt(df[2]);
		dt[2]=parseInt(dt[2]);
		if(df[0]==dt[0]) {
			s=df[0];
			if(df[1]==dt[1]) {
				s=dt[2]+' '+readableMonth[lng][df[1]]+' '+s;
				if(df[2]!=dt[2]) return df[2]+'-'+s;
				else return s;
			} else {
				return df[2]+' '+readableMonth[lng][df[1]]+' - '+
					dt[2]+' '+readableMonth[lng][dt[1]]+' '+s;
			}
		} else {
			return df[2]+' '+readableMonth[lng][df[1]]+' '+df[0]+' - '+dt[2]+' '+readableMonth[lng][dt[1]]+' '+dt[0];
		}
	}
}

class Place {
	constructor(json)
	{
		this.id=(typeof json['id'] === 'undefined'?json['i']:json['id']);
		this.parent=(typeof json['p'] === 'undefined'?0:json['p']);
		this.children=false;
		if(json['d']) {
			this.descr = this.desc_en = this.desc_ru = json['d'];
		} else {
			this.desc_ru=json['dr']?json['dr']:null;
			this.desc_en=json['de']?json['de']:this.desc_ru;
			if(this.desc_ru==null) this.desc_ru = this.desc_en;
		}
		this.type=json['t'];
		this.comment_ru=json['cr']?json['cr']:null;
		this.comment_en=json['ce']?json['ce']:null;
		this.lat=json['lat']?json['lat']:null;
		this.lat=json['lon']?json['lon']:null;
		if(json['s']) this.sentimental=(json['s']==1);
		if(json['c']) this.children = json['c'];
	}
}

class Person {
	constructor(json)
	{	
		if(mode==1) {
			this.id=json['i'];
			this.name_e = this.name_r=json['n'];
			this.aka_e = this.aka_r=(json['a']?json['a']:null);
			this.country=(json['c']?json['c']:null);
		} else {
			this.id=json['id']?json['id']:json['i'];
			this.name_r=json['n'];
			this.name_e=(json['ne']?json['ne']:this.name_r);
			this.aka_r=(json['a']?json['a']:null);
			this.aka_e=(json['ae']?json['ae']:null);
			this.frequency=(json['f']?json['f']:0);
			this.country=(json['c']?json['c']:null);
		}
		this.sent=(json['s']?json['s']==1:false);
		this.no_photos = (json['p']===0);
	}
	
	countryName() {
		return (this.country==null?null:ctr_idx[this.country][lng]);
	}
	
	fullNameAndCountry() {
		return this.fullName()+(this.country==null?'':' ('+this.countryName()+')');
	}

	fullName() {
		var name=(lng==LNG_RU?this.name_r:this.name_e);
		var aka=(lng==LNG_RU?this.aka_r:this.aka_e);
		return name+(aka==null?'':' AKA '+aka);
	}
}

//Globals
var el_idx=[]; //event list, indexed
var ll_idx=[]; //place list, indexed
var ppl_idx=[]; //people list, indexed
var pl_idx=[]; //photo list, indexed
var ctr_idx=[];
var lng=LNG_RU; //current language, default is Russian, English is a second choice
var showSentimental = false, fullThumbnails=false;

function initGlobal() {
	showSentimental=(getCookie(COOKIE_SENTIMENTAL)=='true');
	fullThumbnails=(getCookie(COOKIE_FULL_THUMB)=='true');
	
	lng=getCookie(COOKIE_LANGUAGE);
	if(lng != LNG_EN && lng != LNG_RU) 
	{
		lng = LNG_RU;
		setCookie(COOKIE_LANGUAGE,lng,3000);		
	}
	
	for(var i=0;i<cfgCountries.length;i++) {
		var a=cfgCountries[i]; //[id,ru,en]
		ctr_idx[a[0]]={'ru':a[2],'en':a[1]};
	}
	setNavOptions();
	setBarTitles();
}

function setBarTitles() {
	var a=localize[lng]['bt'];
	$('#lbl-events').text(a[0]);
	$('#lbl-places').text(a[1]);
	$('#lbl-people').text(a[2]);
	$('#lbl-video').text(a[3]);
	$('title').text(a[app]);
}

function setNavOptions() {
	//languages
	$('.my-lng-curr').html("<img src='/img/"+lng+".png'> "+localize[lng]['lng']);
	var altLng=(lng==LNG_RU?LNG_EN:LNG_RU);
	$("li.my-lng .dropdown-menu a").html("<img src='/img/"+altLng+".png'> "+localize[altLng]['lng']);
	
	//options
	$('.my-opt-sel').text(localize[lng]['opt']);
	
	//sentimental
	$('.my-opt-snt a').html((showSentimental?'<i class="fas fa-check"></i> ':'')+localize[lng]['snt']);
	
	//full thumbs
	$('.my-opt-fth a').html((fullThumbnails?'<i class="fas fa-check"></i> ':'')+localize[lng]['fth']);
}

function initPhotoDetails()
{
	var detLbl={'en':['Event','Place','People','Comment','Taken on'],
			'ru':["Событие","Место","Люди","Комментарий","Снято"]};
	var a=detLbl[lng];
	//labels on details
	$('dt.evt').text(a[0]+':');
	$('dt.loc').text(a[1]+':');
	$('dt.ppl').text(a[2]+':');
	$('dt.cmt').text(a[3]+':');
	$('dt.ton').text(a[4]+':');
	
	$("#photoDetails").on('show.bs.modal',function(){
		var w=$(window).width(),h=$(window).height();
		$("#photoDetails").css('max-height',h).css('max-width',w);
		$("#largePhoto img").css('max-height',h-60);
		$("#largePhoto img").css('max-width',w/3>500?Math.floor((w-60)*0.68):w-60);
		var dw=Math.floor((w-60)*0.3);
		if((MAX_PHOTO_W+dw)<w) {
			dw = w - MAX_PHOTO_W - 80;
			$("#details").css('width',dw);
		} else $("#details").css(dw>500?'width':'min-width',dw);
	});
}

function adjustSize(el){
	/*
	var ph=el.clientHeight;
	var pw=el.clientWidth;
	if(ph==0){
		ph=el.height;
		pw=el.width;
	}
	if(ph==0){
		ph=el.naturalHeight;
		pw=el.naturalWidth;
	}
	if(ph && ph>0) {
		var pr=ph/pw;
		var w=$(window).width();
		var mh=parseInt($("#largePhoto img").css('max-height'));
		var mw=parseInt($("#largePhoto img").css('max-width'));
		var mr=mh/mw;
		var rh=0, rw=0;
		if(mh<ph) {
			if(mr<pr) {
				rh=mh;
				rw=mw*(mh/ph);
			} else {
				rh=mh*(mw/pw);
				rw=mw;
			}
		} else if(mw<pw) {
			rh=ph*(mw/pw);
			rw=mw;
		} else {
			rw=pw;
			rh=ph;
		}
		if(w-rw-80>500) $("#details").css('width',w-rw-80);
	} */
	$(el).on( "swipeleft", function(event) {
		photoCarousel('left');
	})
	.on( "swiperight", function(event) {
		photoCarousel('right');
	});

	return false;
};	

//HTTP-specific
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function getSingleParameter() {
	var s = window.location.href.split('?');
	if(s.length>1) return s[1];
	else return null;
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

//functionality-specific
function preprocessPlaces() {
	for(var i=0;i<places.length;i++) {
		var o=new Place(places[i]);
		ll_idx[o.id] = o;
	}
}

function preprocessPeople() {
	for(var i=0;i<people.length;i++) {
		var o=new Person(people[i]);
		ppl_idx[o.id] = o;
	}
}

function preprocessEvents() {
	for(var i=0;i<events.length;i++) {
		var o=new Event(events[i]);
		el_idx[o.id] = o;
	}
}

function setLanguage(el) {
	var icon=$('img',el).attr('src');
	var a=icon.split('/');
	icon=a[a.length-1];
	if(icon.length>=2) {
		switch(icon.substr(0,2)) {
		case LNG_RU: case LNG_EN:
			lng=icon.substr(0,2);
			setCookie(COOKIE_LANGUAGE,lng,3000);
			location.reload(true);	
		}		
	}
}

function buildLocDesc(id,depth=0) {
	if(depth>20) return;
	var o=ll_idx[id];
	var a=$('<div><a href="/place/'+id+'" target="PLACE"></a></div>');
	$('a',a).text(lng==LNG_RU?o.desc_ru:o.desc_en);
	var html=a.html();
	if(o.parent && o.parent>0) html+=', '+buildLocDesc(o.parent,depth+1);
	return html;
}	

function buildPplList(a) {
	if(!a || a.length==0) return '';
	var html='<ul>';
	for(var i=0;i<a.length;i++) {
		var o=ppl_idx[a[i]];		
		var li=$('<li><a href="/person/'+o.id+'" target="PERSON"></a></li>');
		$('a',li).text(o.fullName());
		html+=li[0].outerHTML;
	}
	return html+'</ul>'
}

function showDetails(id) {
	if($("#photoDetails").is(":visible")) {
		hideDetails();
	}
	$("#photoid").val(id);
	var op=pl_idx[id];
	var fname=op.dir_name+'/'+op.file_name+'.jpg';
	var img=$("<a class='img' href='/full/"+fname+"' target='_PHOTO'><img onload='adjustSize(this);'></a>");
	$("#largePhoto a.img").replaceWith(img);
	$("#largePhoto img").attr('src','/pics/'+fname);
	//carousel init
	if(typeof op.idx !== 'undefined') {
		if(op.idx==0) {
			//1st photo, hide left arrow
			$("#largePhoto .carousel-control.left").hide();
		} else {
			$("#largePhoto .carousel-control.left").show().attr("onclick","showDetails("+photos[op.idx-1].i+")");
		}
		if(op.idx>=(photos.length-1)) {
			//last photo, hide right arrow
			$("#largePhoto .carousel-control.right").hide();
		} else {
			$("#largePhoto .carousel-control.right").show().attr("onclick","showDetails("+photos[op.idx+1].i+")");
		}
	}
	var evtDescr=el_idx[op.event].nameToShow();
	if(app!=APP_EVENT) {
		evtDescr = '<a href="/event/'+op.event+'">'+evtDescr+'</a>';
	}
	$("dd.evt").html(evtDescr);
	$("dd.loc").html(buildLocDesc(op.place));
	$("dd.ppl").html(buildPplList(op.people));
	(op.people.length>0?$("#details .ppl").show():$("#details .ppl").hide());
	var cmt=op.comment();
	if(cmt=='') $("#details .cmt").hide();
	else { 
		$("dd.cmt").html(cmt); 
		$("#details .cmt").show(); 
	}	
	(op.taken_on==null?$('.ton').hide():$("dd.ton").text(op.taken_on));
	//$("#picfull").html("<a href='/full/"+fname+"' target='_PHOTO'><img src='/img/full.png'></a>");
	$("#photoDetails").height(document.documentElement.clientHeight).modal('show');	
}

function hideDetails() {
	$('#photoDetails').modal('hide');
	return false;
}

function smartText(s) {
	//process long text and try to make it HTML-friendly:
	//- line breaks replaced with <br/>
	//- href:// without preceding > replaced with proper links
	return linkify(s).replace(/\n/gim,"<br/>");	
}

function swipedetect(el, callback){
	  
    var touchsurface = el,
    swipedir,
    startX,
    startY,
    distX,
    distY,
    threshold = 150, //required min distance traveled to be considered swipe
    restraint = 100, // maximum distance allowed at the same time in perpendicular direction
    allowedTime = 2000, // maximum time allowed to travel that distance
    elapsedTime,
    startTime,
    handleswipe = callback || function(swipedir){}
  
    touchsurface.addEventListener('touchstart', function(e){
        var touchobj = e.changedTouches[0]
        swipedir = 'none'
        dist = 0
        startX = touchobj.pageX
        startY = touchobj.pageY
        startTime = new Date().getTime() // record time when finger first makes contact with surface
        //e.preventDefault()
    }, false)
  
    touchsurface.addEventListener('touchmove', function(e){
        //e.preventDefault() // prevent scrolling when inside DIV
    }, false)
  
    touchsurface.addEventListener('touchend', function(e){
        var touchobj = e.changedTouches[0]
        distX = touchobj.pageX - startX // get horizontal dist traveled by finger while in contact with surface
        distY = touchobj.pageY - startY // get vertical dist traveled by finger while in contact with surface
        elapsedTime = new Date().getTime() - startTime // get time elapsed
        if (elapsedTime <= allowedTime){ // first condition for awipe met
            if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint){ // 2nd condition for horizontal swipe met
                swipedir = (distX < 0)? 'left' : 'right' // if dist traveled is negative, it indicates left swipe
            }
            else if (Math.abs(distY) >= threshold && Math.abs(distX) <= restraint){ // 2nd condition for vertical swipe met
            	return false;
                swipedir = (distY < 0)? 'up' : 'down' // if dist traveled is negative, it indicates up swipe
            }
        } else {
        	return false;
        }
        handleswipe(swipedir)
        e.preventDefault()
    }, false)
}

function toggleSentimental() {
	showSentimental = !showSentimental;
	setCookie(COOKIE_SENTIMENTAL,showSentimental,3650);
	location.reload();	
	return false;
}

function toggleFullThumbnail() {
	fullThumbnails = !fullThumbnails;
	setCookie(COOKIE_FULL_THUMB,fullThumbnails,3650);
	if(reloadOnFTH) location.reload();	
	return false;
}

function linkify(inputText) {
	//convert URL to html link
    var replacedText, replacePattern1, replacePattern2;

    //URLs starting with http://, https://, or ftp://
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');

    return replacedText;
}

function photoCarousel(direction) {
	switch(direction) {
	case 'left': case 'right':
		var ctl_name="#largePhoto .carousel-control."+direction;
		var ctl = $(ctl_name);
		if(ctl.is(":visible")) {
			eval(ctl.attr("onclick")+';');
		}	
	}
}

$(document).ready(function() {
	initGlobal();
});

