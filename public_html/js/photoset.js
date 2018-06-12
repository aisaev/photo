var setName = false;
var id = 0;

$(document).ready(function() {
	//initGlobal();
	initPhotoDetails();
	preprocessEvents();
	preprocessPlaces();
	preprocessPeople();
	
	var s = window.location.href.split('/');
	if(s.length>2) {
		setName=s[s.length-2];
		id = s[s.length-1];
		if(id=='') id=0;
		else id = parseInt(id);
	} else {
		return false;
	}

	if (setName=='event' && events.length == 1) {
		app=APP_EVENT;
		$('#my-nav li.evt').addClass('active');
		var o = new Event(events[0]);
		$('h1').text(o.nameToShow());
		$('title').text(localize[lng]['bt'][0]+': '+o.nameToShow());
	} else if (setName =='person' && people.length >= 1) {
		app = APP_PERSON;
		$('#my-nav li.ppl').addClass('active');
		var o = ppl_idx[id];
		$('h1').text(o.fullNameAndCountry());
		$('title').text(localize[lng]['bt'][2]+': '+o.fullNameAndCountry());
	} else if (setName =='place' && places.length >= 1) {                                                                                               
		app = APP_PLACE;
		$('#my-nav li.loc').addClass('active');
		var o = ll_idx[id];
		$('h1').text(o.descr);
		$('title').text(localize[lng]['bt'][1]+': '+o.descr);
	}
	var s = '', sLoc='';
	//locations: render subnodes
	if(app == APP_PLACE) { 
		if(o.id>0) {
			sLoc+='<ol class="breadcrumb">';
			for(var pid=o.parent; pid>=0;) {
				var op=ll_idx[pid];
				sLoc+='<li><a href="/place/'+op.id+'">'+op.descr+'</a></li>';
				pid = op.parent;
				if(op.id==0) break;
			}			
			sLoc+='</ol>';
		}
		if(o.children) {
			o.children.sort(function(a,b){
				if(a.d<b.d) return -1;
				else return 1;
			});
			sLoc += '<div class="subloc"><ul>';
			for(var i=0;i<o.children.length;i++) {
				oc = o.children[i];
				sLoc+='<li><a href="/place/'+oc.i+'">'+oc.d+'</a></li>';
			}
			sLoc+='</ul></div><div class="locphotos">';
		}
	}
	//render photos
	var pad = '00000';
	for (var i = 0; i < photos.length; i++) {
		var op = new Photo(photos[i]);
		op.idx = i; //current photo index to know which one is next or previous
		switch(app) {
			case APP_EVENT: 
				op.event = id; 
				break;
			case APP_PLACE:
				op.place = id;
		}
		pl_idx[op.id] = op;
		s += op.htmlThumbnail();
	}
	if(app == APP_PLACE && sLoc!='') {
		s=sLoc+s+'</div>';
	}
	$('#root').html(s);
	$('.lazy').lazy({
		afterLoad: function(el){
			el.parent().removeClass("lazy-loading");
		}
	});
	
	$("#photoDetails").keyup(function(event){
		var ctl_name="#largePhoto .carousel-control.";
		switch(event.which) {
		case 37: //left
			ctl_name+='left';
			break;
		case 39: //right
			ctl_name+='right';
			break;
		default:
			return false;
		}
		var ctl = $(ctl_name);
		if(ctl.is(":visible")) {
			eval(ctl.attr("onclick")+';');
		}
	});
});
