var idx=0, maxIdx=0, timeout=3000;

function adjustSize(el) {
  var ch = $(window).height(), cw = $(window).width(), ih = el.naturalHeight, iw = el.naturalWidth;
  if (ch==0||ih==0) return;
  var cr = cw/ch;
  var ir = iw/ih;
  if (cr>ir) {
	nh=ch;
    if(ih<ch) {
		nw=Math.floor(ir*ch);
	} else {
		nw="auto";
	}
  } else {
	nw=cw;
    if(iw<cw) {
		nh=Math.floor(cw/ir);
	} else {
		nh="auto";
	}
  }
  $(el).css("height",nh).css("width",nw); 
}
function getPhotoPath(id){
	var fn=''+id, pad='0000';
	if(id<10000) fn=pad.substring(0,5-fn.length)+fn;
	return '/pics/'+fn.substring(0,2)+'/'+fn+'.jpg';
}

function photoCarousel(direction,stopMe=false) {
	if(stopMe==true) timeout=0;
	switch(direction) {
		case 'left': 
			if(idx==0) return;
			idx--;
			break;
		default:
			if(timeout==0&&direction!="right") return;
			if(idx==maxIdx) return;
			idx++;
			break;
	}
	$("#photo").remove();
	$("body").append('<img id="photo" src="'+getPhotoPath(photos[idx].i)+'" onload="adjustSize(this);" onclick="photoCarousel(\'right\',true);">');
	if(timeout>0) setTimeout(photoCarousel,timeout);
}
$(document).ready(function(){
	maxIdx=photos.length-1;
	if(maxIdx<0) return;
	$("title").text(events[0].f+" "+events[0].d);
	$("#photo").attr("src",getPhotoPath(photos[idx].i));
	$("body,#photo").keyup(function(event){
		switch(event.which) {
		case 37: //left
			event.preventDefault();
			timeout=0; //break auto-scroll
			photoCarousel('left');
			break;
		case 39: //right
			event.preventDefault();
			timeout=0; //break auto-scroll
			photoCarousel('right');
			break;
		default:
			return false;
		}
	});
	setTimeout(photoCarousel,3000);
});