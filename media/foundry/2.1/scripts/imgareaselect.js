!function(){var moduleFactory=function($){var module=this,jQuery=$;$.require().stylesheet("imgareaselect/default").done(function(){var exports=function(){!function($){function div(){return $("<div/>")}var abs=Math.abs,max=Math.max,min=Math.min,round=Math.round;$.imgAreaSelect=function(img,options){function viewX(x){return x+imgOfs.left-parOfs.left}function viewY(y){return y+imgOfs.top-parOfs.top}function selX(x){return x-imgOfs.left+parOfs.left}function selY(y){return y-imgOfs.top+parOfs.top}function evX(event){return event.pageX-parOfs.left
}function evY(event){return event.pageY-parOfs.top}function getSelection(noScale){var sx=noScale||scaleX,sy=noScale||scaleY;return{x1:round(selection.x1*sx),y1:round(selection.y1*sy),x2:round(selection.x2*sx),y2:round(selection.y2*sy),width:round(selection.x2*sx)-round(selection.x1*sx),height:round(selection.y2*sy)-round(selection.y1*sy)}}function setSelection(x1,y1,x2,y2,noScale){var sx=noScale||scaleX,sy=noScale||scaleY;selection={x1:round(x1/sx||0),y1:round(y1/sy||0),x2:round(x2/sx||0),y2:round(y2/sy||0)},selection.width=selection.x2-selection.x1,selection.height=selection.y2-selection.y1
}function adjust(){$img.width()&&(imgOfs={left:round($img.offset().left),top:round($img.offset().top)},imgWidth=$img.innerWidth(),imgHeight=$img.innerHeight(),imgOfs.top+=$img.outerHeight()-imgHeight>>1,imgOfs.left+=$img.outerWidth()-imgWidth>>1,minWidth=round(options.minWidth/scaleX)||0,minHeight=round(options.minHeight/scaleY)||0,maxWidth=round(min(options.maxWidth/scaleX||1<<24,imgWidth)),maxHeight=round(min(options.maxHeight/scaleY||1<<24,imgHeight)),"1.3.2"!=$().jquery||"fixed"!=position||docElem.getBoundingClientRect||(imgOfs.top+=max(document.body.scrollTop,docElem.scrollTop),imgOfs.left+=max(document.body.scrollLeft,docElem.scrollLeft)),parOfs=/absolute|relative/.test($parent.css("position"))?{left:round($parent.offset().left)-$parent.scrollLeft(),top:round($parent.offset().top)-$parent.scrollTop()}:"fixed"==position?{left:$(document).scrollLeft(),top:$(document).scrollTop()}:{left:0,top:0},left=viewX(0),top=viewY(0),(selection.x2>imgWidth||selection.y2>imgHeight)&&doResize())
}function update(resetKeyPress){if(shown){switch($box.css({left:viewX(selection.x1),top:viewY(selection.y1)}).add($area).width(w=selection.width).height(h=selection.height),$area.add($border).add($handles).css({left:0,top:0}),$border.width(max(w-$border.outerWidth()+$border.innerWidth(),0)).height(max(h-$border.outerHeight()+$border.innerHeight(),0)),$($outer[0]).css({left:left,top:top,width:selection.x1,height:imgHeight}),$($outer[1]).css({left:left+selection.x1,top:top,width:w,height:selection.y1}),$($outer[2]).css({left:left+selection.x2,top:top,width:imgWidth-selection.x2,height:imgHeight}),$($outer[3]).css({left:left+selection.x1,top:top+selection.y2,width:w,height:imgHeight-selection.y2}),w-=$handles.outerWidth(),h-=$handles.outerHeight(),$handles.length){case 8:$($handles[4]).css({left:w>>1}),$($handles[5]).css({left:w,top:h>>1}),$($handles[6]).css({left:w>>1,top:h}),$($handles[7]).css({top:h>>1});
case 4:$handles.slice(1,3).css({left:w}),$handles.slice(2,4).css({top:h})}resetKeyPress!==!1&&($.imgAreaSelect.keyPress!=docKeyPress&&$(document).unbind($.imgAreaSelect.keyPress,$.imgAreaSelect.onKeyPress),options.keys&&$(document)[$.imgAreaSelect.keyPress]($.imgAreaSelect.onKeyPress=docKeyPress)),$.browser.msie&&2==$border.outerWidth()-$border.innerWidth()&&($border.css("margin",0),setTimeout(function(){$border.css("margin","auto")},0))}}function doUpdate(resetKeyPress){adjust(),update(resetKeyPress),x1=viewX(selection.x1),y1=viewY(selection.y1),x2=viewX(selection.x2),y2=viewY(selection.y2)
}function hide($elem,fn){options.fadeSpeed?$elem.fadeOut(options.fadeSpeed,fn):$elem.hide()}function areaMouseMove(event){var x=selX(evX(event))-selection.x1,y=selY(evY(event))-selection.y1;adjusted||(adjust(),adjusted=!0,$box.one("mouseout",function(){adjusted=!1})),resize="",options.resizable&&(y<=options.resizeMargin?resize="n":y>=selection.height-options.resizeMargin&&(resize="s"),x<=options.resizeMargin?resize+="w":x>=selection.width-options.resizeMargin&&(resize+="e")),$box.css("cursor",resize?resize+"-resize":options.movable?"move":""),$areaOpera&&$areaOpera.toggle()
}function docMouseUp(){$("body").css("cursor",""),(options.autoHide||0==selection.width*selection.height)&&hide($box.add($outer),function(){$(this).hide()}),$(document).unbind("mousemove",selectingMouseMove),$box.mousemove(areaMouseMove),options.onSelectEnd(img,getSelection())}function areaMouseDown(event){return 1!=event.which?!1:(adjust(),resize?($("body").css("cursor",resize+"-resize"),x1=viewX(selection[/w/.test(resize)?"x2":"x1"]),y1=viewY(selection[/n/.test(resize)?"y2":"y1"]),$(document).mousemove(selectingMouseMove).one("mouseup",docMouseUp),$box.unbind("mousemove",areaMouseMove)):options.movable?(startX=left+selection.x1-evX(event),startY=top+selection.y1-evY(event),$box.unbind("mousemove",areaMouseMove),$(document).mousemove(movingMouseMove).one("mouseup",function(){options.onSelectEnd(img,getSelection()),$(document).unbind("mousemove",movingMouseMove),$box.mousemove(areaMouseMove)
})):$img.mousedown(event),!1)}function fixAspectRatio(xFirst){aspectRatio&&(xFirst?(x2=max(left,min(left+imgWidth,x1+abs(y2-y1)*aspectRatio*(x2>x1||-1))),y2=round(max(top,min(top+imgHeight,y1+abs(x2-x1)/aspectRatio*(y2>y1||-1)))),x2=round(x2)):(y2=max(top,min(top+imgHeight,y1+abs(x2-x1)/aspectRatio*(y2>y1||-1))),x2=round(max(left,min(left+imgWidth,x1+abs(y2-y1)*aspectRatio*(x2>x1||-1)))),y2=round(y2)))}function doResize(){x1=min(x1,left+imgWidth),y1=min(y1,top+imgHeight),abs(x2-x1)<minWidth&&(x2=x1-minWidth*(x1>x2||-1),left>x2?x1=left+minWidth:x2>left+imgWidth&&(x1=left+imgWidth-minWidth)),abs(y2-y1)<minHeight&&(y2=y1-minHeight*(y1>y2||-1),top>y2?y1=top+minHeight:y2>top+imgHeight&&(y1=top+imgHeight-minHeight)),x2=max(left,min(x2,left+imgWidth)),y2=max(top,min(y2,top+imgHeight)),fixAspectRatio(abs(x2-x1)<abs(y2-y1)*aspectRatio),abs(x2-x1)>maxWidth&&(x2=x1-maxWidth*(x1>x2||-1),fixAspectRatio()),abs(y2-y1)>maxHeight&&(y2=y1-maxHeight*(y1>y2||-1),fixAspectRatio(!0)),selection={x1:selX(min(x1,x2)),x2:selX(max(x1,x2)),y1:selY(min(y1,y2)),y2:selY(max(y1,y2)),width:abs(x2-x1),height:abs(y2-y1)},update(),options.onSelectChange(img,getSelection())
}function selectingMouseMove(event){return x2=/w|e|^$/.test(resize)||aspectRatio?evX(event):viewX(selection.x2),y2=/n|s|^$/.test(resize)||aspectRatio?evY(event):viewY(selection.y2),doResize(),!1}function doMove(newX1,newY1){x2=(x1=newX1)+selection.width,y2=(y1=newY1)+selection.height,$.extend(selection,{x1:selX(x1),y1:selY(y1),x2:selX(x2),y2:selY(y2)}),update(),options.onSelectChange(img,getSelection())}function movingMouseMove(event){return x1=max(left,min(startX+evX(event),left+imgWidth-selection.width)),y1=max(top,min(startY+evY(event),top+imgHeight-selection.height)),doMove(x1,y1),event.preventDefault(),!1
}function startSelection(){$(document).unbind("mousemove",startSelection),adjust(),x2=x1,y2=y1,doResize(),resize="",$outer.is(":visible")||$box.add($outer).hide().fadeIn(options.fadeSpeed||0),shown=!0,$(document).unbind("mouseup",cancelSelection).mousemove(selectingMouseMove).one("mouseup",docMouseUp),$box.unbind("mousemove",areaMouseMove),options.onSelectStart(img,getSelection())}function cancelSelection(){$(document).unbind("mousemove",startSelection).unbind("mouseup",cancelSelection),hide($box.add($outer)),setSelection(selX(x1),selY(y1),selX(x1),selY(y1)),this instanceof $.imgAreaSelect||(options.onSelectChange(img,getSelection()),options.onSelectEnd(img,getSelection()))
}function imgMouseDown(event){return 1!=event.which||$outer.is(":animated")?!1:(adjust(),startX=x1=evX(event),startY=y1=evY(event),$(document).mousemove(startSelection).mouseup(cancelSelection),!1)}function windowResize(){doUpdate(!1)}function imgLoad(){imgLoaded=!0,setOptions(options=$.extend({classPrefix:"imgareaselect",movable:!0,parent:"body",resizable:!0,resizeMargin:10,onInit:function(){},onSelectStart:function(){},onSelectChange:function(){},onSelectEnd:function(){}},options)),$box.add($outer).css({visibility:""}),options.show&&(shown=!0,adjust(),update(),$box.add($outer).hide().fadeIn(options.fadeSpeed||0)),setTimeout(function(){options.onInit(img,getSelection())
},0)}function styleOptions($elem,props){for(var option in props)void 0!==options[option]&&$elem.css(props[option],options[option])}function setOptions(newOptions){if(newOptions.parent&&($parent=$(newOptions.parent)).append($box.add($outer)),$.extend(options,newOptions),adjust(),null!=newOptions.handles){for($handles.remove(),$handles=$([]),i=newOptions.handles?"corners"==newOptions.handles?4:8:0;i--;)$handles=$handles.add(div());$handles.addClass(options.classPrefix+"-handle").css({position:"absolute",fontSize:0,zIndex:zIndex+1||1}),!parseInt($handles.css("width"))>=0&&$handles.width(5).height(5),(o=options.borderWidth)&&$handles.css({borderWidth:o,borderStyle:"solid"}),styleOptions($handles,{borderColor1:"border-color",borderColor2:"background-color",borderOpacity:"opacity"})
}for(scaleX=options.imageWidth/imgWidth||1,scaleY=options.imageHeight/imgHeight||1,null!=newOptions.x1&&(setSelection(newOptions.x1,newOptions.y1,newOptions.x2,newOptions.y2),newOptions.show=!newOptions.hide),newOptions.keys&&(options.keys=$.extend({shift:1,ctrl:"resize"},newOptions.keys)),$outer.addClass(options.classPrefix+"-outer"),$area.addClass(options.classPrefix+"-selection"),i=0;i++<4;)$($border[i-1]).addClass(options.classPrefix+"-border"+i);styleOptions($area,{selectionColor:"background-color",selectionOpacity:"opacity"}),styleOptions($border,{borderOpacity:"opacity",borderWidth:"border-width"}),styleOptions($outer,{outerColor:"background-color",outerOpacity:"opacity"}),(o=options.borderColor1)&&$($border[0]).css({borderStyle:"solid",borderColor:o}),(o=options.borderColor2)&&$($border[1]).css({borderStyle:"dashed",borderColor:o}),$box.append($area.add($border).add($areaOpera).add($handles)),$.browser.msie&&((o=($outer.css("filter")||"").match(/opacity=(\d+)/))&&$outer.css("opacity",o[1]/100),(o=($border.css("filter")||"").match(/opacity=(\d+)/))&&$border.css("opacity",o[1]/100)),newOptions.hide?hide($box.add($outer)):newOptions.show&&imgLoaded&&(shown=!0,$box.add($outer).fadeIn(options.fadeSpeed||0),doUpdate()),aspectRatio=(d=(options.aspectRatio||"").split(/:/))[0]/d[1],$img.add($outer).unbind("mousedown",imgMouseDown),options.disable||options.enable===!1?($box.unbind("mousemove",areaMouseMove).unbind("mousedown",areaMouseDown),$(window).unbind("resize",windowResize)):((options.enable||options.disable===!1)&&((options.resizable||options.movable)&&$box.mousemove(areaMouseMove).mousedown(areaMouseDown),$(window).resize(windowResize)),options.persistent||$img.add($outer).mousedown(imgMouseDown)),options.enable=options.disable=void 0
}var imgLoaded,$areaOpera,left,top,imgWidth,imgHeight,$parent,startX,startY,scaleX,scaleY,resize,minWidth,minHeight,maxWidth,maxHeight,aspectRatio,shown,x1,y1,x2,y2,$p,d,i,o,w,h,adjusted,$img=$(img),$box=div(),$area=div(),$border=div().add(div()).add(div()).add(div()),$outer=div().add(div()).add(div()).add(div()),$handles=$([]),imgOfs={left:0,top:0},parOfs={left:0,top:0},zIndex=0,position="absolute",selection={x1:0,y1:0,x2:0,y2:0,width:0,height:0},docElem=document.documentElement,docKeyPress=function(event){var d,t,k=options.keys,key=event.keyCode;
if(d=isNaN(k.alt)||!event.altKey&&!event.originalEvent.altKey?!isNaN(k.ctrl)&&event.ctrlKey?k.ctrl:!isNaN(k.shift)&&event.shiftKey?k.shift:isNaN(k.arrows)?10:k.arrows:k.alt,"resize"==k.arrows||"resize"==k.shift&&event.shiftKey||"resize"==k.ctrl&&event.ctrlKey||"resize"==k.alt&&(event.altKey||event.originalEvent.altKey)){switch(key){case 37:d=-d;case 39:t=max(x1,x2),x1=min(x1,x2),x2=max(t+d,x1),fixAspectRatio();break;case 38:d=-d;case 40:t=max(y1,y2),y1=min(y1,y2),y2=max(t+d,y1),fixAspectRatio(!0);break;default:return
}doResize()}else switch(x1=min(x1,x2),y1=min(y1,y2),key){case 37:doMove(max(x1-d,left),y1);break;case 38:doMove(x1,max(y1-d,top));break;case 39:doMove(x1+min(d,imgWidth-selX(x2)),y1);break;case 40:doMove(x1,y1+min(d,imgHeight-selY(y2)));break;default:return}return!1};for(this.remove=function(){setOptions({disable:!0}),$box.add($outer).remove()},this.getOptions=function(){return options},this.setOptions=setOptions,this.getSelection=getSelection,this.setSelection=setSelection,this.cancelSelection=cancelSelection,this.update=doUpdate,$p=$img;$p.length;)zIndex=max(zIndex,isNaN($p.css("z-index"))?zIndex:$p.css("z-index")),"fixed"==$p.css("position")&&(position="fixed"),$p=$p.parent(":not(body)");
zIndex=options.zIndex||zIndex,$.browser.msie&&$img.attr("unselectable","on"),$.imgAreaSelect.keyPress=$.browser.msie||$.browser.safari?"keydown":"keypress",$.browser.opera&&($areaOpera=div().css({width:"100%",height:"100%",position:"absolute",zIndex:zIndex+2||2})),$box.add($outer).css({visibility:"hidden",position:position,overflow:"hidden",zIndex:zIndex||"0"}),$box.css({zIndex:zIndex+2||2}),$area.add($border).css({position:"absolute",fontSize:0}),img.complete||"complete"==img.readyState||!$img.is("img")?imgLoad():$img.one("load",imgLoad),!imgLoaded&&$.browser.msie&&$.browser.version>=7&&(img.src=img.src)
},$.fn.imgAreaSelect=function(options){return options=options||{},this.each(function(){$(this).data("imgAreaSelect")?options.remove?($(this).data("imgAreaSelect").remove(),$(this).removeData("imgAreaSelect")):$(this).data("imgAreaSelect").setOptions(options):options.remove||(void 0===options.enable&&void 0===options.disable&&(options.enable=!0),$(this).data("imgAreaSelect",new $.imgAreaSelect(this,options)))}),options.instance?$(this).data("imgAreaSelect"):this}}(jQuery)};exports(),module.resolveWith(exports)
})};dispatch("imgareaselect").containing(moduleFactory).to("Foundry/2.1 Modules")}();