$(document).ready(function() {
	
});
class PhotoDir {
	constructor(d) {
		this.dir=d;
		this.files=[];
		this.ok=[];
		this.fail=[];
	}
	
	toHTML() {
		return '<button type="button" id="d'+this.dir+
		'" class="btn btn-lg btn-primary ph-dir" onclick="return resizeDir(this);"><span class="ph-dir-id">'+this.dir+		
		'</span> <span class="badge">'+this.files.length+'</span></button>';
	}
}

var dirs=[],dirHash=[];

function prepPhotos() {
	dirs=[];
	$("#msg").html('');
	//get files
	//render dir representation with counter in each of them
	$.getJSON( "/api.php?op=rs_rd" )
	.done(function(data){
		if(data['e']=="1") {
			reportMessage(data['m']);
		} else {
			var len=data['r'].length;
			var prevDir='';
			var f,d,oDir,dirIdx=0;			
			for(var i=0;i<len;i++) {
				f=data['r'][i];
				d=f.substr(0,2);
				if(d!=prevDir) {
					oDir = new PhotoDir(d);
					dirs.push(oDir);
					dirHash[d]=dirIdx++;
					prevDir=d;
				}
				oDir.files.push(f);
			}
			var s='';
			for(var i=0;i<dirs.length;i++) {
				s+=dirs[i].toHTML();
			}
			$('#content').html(len>0?'<h1>Push button to process directory</h1><p>'+s+'</p>':'Nothing to do');
		}
	});
	return false;
}

function reportMessage(m,t='danger') {
	var hAlert=$('<div class="alert alert-'+t+' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="msgcnt"></div>');
	$(".msgcnt",hAlert).text(m);
	$("#msg").append(hAlert);				
}

function resizeDir(el) {
	$(el).prop("disabled",true);
	//process 10 files at a time
	var procCnt=0,id=el.id.substr(1);
	resizeBatch(id,0,parseInt($('.badge',el).text()));
	return false;
}

function resizeBatch(id,cnt,maxCnt) {
	if(cnt>=maxCnt) return;
	
	var oDir=dirs[dirHash[id]],p2p=[];
	var cntNow=cnt;
	for(var i=0;cntNow<oDir.files.length && i<10;i++,cntNow++) {
		p2p.push(oDir.files[cntNow]);
	}
	$.post({ "url": "/api.php?op=rs_10","data": {"p":p2p},"dataType":"json"})
	.done(function(data){
		if(data['e']=="1") {
			reportMessage(data['m']);
		} else {
			for(var k in data['r']) {
				var v=data['r'][k];
				if(v==1) {
					oDir.ok.push(k);
				} else {
					reportMessage(v);					
					oDir.fail.push(k);
				}
			}
			$("#d"+id+" .badge").text(oDir.files.length-oDir.ok.length);
			if(oDir.fail.length>0 && $("#d"+id).hasClass('btn-primary')) {
				$("#d"+id).removeClass("btn-primary").addClass("btn-danger");
				return false; //stop processing
			}
			if(cnt<maxCnt) resizeBatch(id,cntNow,maxCnt);
			if(oDir.ok.length==oDir.files.length) {
				$("#d"+id).hide();
			}
		}		
	});	
}

function auditPhotos() {
	$("#msg").html('');
	$.getJSON( "/api.php?op=ap" )
	.done(function(data){
		if(data['e']=="1") {
			reportMessage(data['m']);
		} else {			
			var o=data['r'];
			var tk=Object.keys(o).sort();
			var s='';
			for(var i in tk) {
				var k=tk[i];
				var msgtext = null;
				var msgtype = 'danger';
				switch(o[k]) {
				case 0:
					msgtext='Good DB rec has no file, but pic was copied';
					msgtype='success';
					break;					
				case 1:
					msgtext='Hidden DB rec has file';
					msgtype='warning';
					break;					
				case 2:
					msgtext='Good DB rec has no file';
					break;
				case 3:
					msgtext='File exists, no DB record';
					s+='<div class="thumbnail"><img src="/tmb/'+k.substr(0,2)+'/'+k+'"><div class="caption"><h3>'+
						k+'</h3></div></div>';
					break;
				case 4:
					msgtext='pics size exists but could not save';
					break;
				case 5:
					msgtext='Exception when tried copying from web';
					break;
				}
				if (msgtext!=null)
					reportMessage(k+': '+msgtext,msgtype);
			}
			$('#content').html(s);
		}
	});
	return false;
}