!function(){var moduleFactory=function($){var module=this,jQuery=$;$.require().script("ui/core","ui/widget").stylesheet("ui/stars/style/ui.stars").done(function(){var exports=function(){!function($){$.widget("ui.stars",{_create:function(){function fillTo(index,hover){if(-1!=index){var addClass=hover?o.starHoverClass:o.starOnClass,remClass=hover?o.starOnClass:o.starHoverClass;self.$stars.eq(index).prevAll("."+o.starClass).andSelf().removeClass(remClass).addClass(addClass),self.$stars.eq(index).nextAll("."+o.starClass).removeClass(o.starHoverClass+" "+o.starOnClass),self._showCap(o.id2title[index])
}else fillNone()}function fillNone(){self.$stars.removeClass(o.starOnClass+" "+o.starHoverClass),self._showCap("")}var self=this,o=this.options,id=0;o.isSelect="select"==o.inputType,this.$form=$(this.element).closest("form"),this.$selec=o.isSelect?$("select",this.element):null,this.$rboxs=o.isSelect?$("option",this.$selec):$(":radio",this.element),this.$stars=this.$rboxs.map(function(i){var el={value:this.value,title:(o.isSelect?this.text:this.title)||this.value,isDefault:o.isSelect&&this.defaultSelected||this.defaultChecked};
if(0==i&&(o.split="number"!=typeof o.split?0:o.split,o.val2id=[],o.id2val=[],o.id2title=[],o.name=o.isSelect?self.$selec.get(0).name:this.name,o.disabled=o.disabled||(o.isSelect?$(self.$selec).attr("disabled"):$(this).attr("disabled"))),el.value==o.cancelValue)return o.cancelTitle=el.title,null;o.val2id[el.value]=id,o.id2val[id]=el.value,o.id2title[id]=el.title,el.isDefault&&(o.checked=id,o.value=o.defaultValue=el.value,o.title=el.title);var $s=$("<div/>").addClass(o.starClass),$a=$("<a/>").attr("title",o.showTitles?el.title:"").text(el.value);
if(o.split){var oddeven=id%o.split,stwidth=Math.floor(o.starWidth/o.split);$s.width(stwidth),$a.css("margin-left","-"+oddeven*stwidth+"px")}return id++,$s.append($a).get(0)}),o.items=id,o.isSelect?this.$selec.remove():this.$rboxs.remove(),this.$cancel=$("<div/>").addClass(o.cancelClass).append($("<a/>").attr("title",o.showTitles?o.cancelTitle:"").text(o.cancelValue)),o.cancelShow&=!o.disabled&&!o.oneVoteOnly,o.cancelShow&&this.element.append(this.$cancel),this.element.append(this.$stars),void 0===o.checked&&(o.checked=-1,o.value=o.defaultValue=o.cancelValue,o.title=""),this.$value=$('<input type="hidden" name="'+o.name+'" value="'+o.value+'" />'),this.element.append(this.$value),this.$stars.bind("click.stars",function(e){if(!o.forceSelect&&o.disabled)return!1;
var i=self.$stars.index(this);o.checked=i,o.value=o.id2val[i],o.title=o.id2title[i],self.$value.attr({disabled:o.disabled?"disabled":"",value:o.value}),fillTo(i,!1),self._disableCancel(),!o.forceSelect&&self.callback(e,"star")}).bind("mouseover.stars",function(){if(o.disabled)return!1;var i=self.$stars.index(this);fillTo(i,!0)}).bind("mouseout.stars",function(){return o.disabled?!1:(fillTo(self.options.checked,!1),void 0)}),this.$cancel.bind("click.stars",function(e){return o.forceSelect||!o.disabled&&o.value!=o.cancelValue?(o.checked=-1,o.value=o.cancelValue,o.title="",self.$value.val(o.value).attr({disabled:"disabled"}),fillNone(),self._disableCancel(),!o.forceSelect&&self.callback(e,"cancel"),void 0):!1
}).bind("mouseover.stars",function(){return self._disableCancel()?!1:(self.$cancel.addClass(o.cancelHoverClass),fillNone(),self._showCap(o.cancelTitle),void 0)}).bind("mouseout.stars",function(){return self._disableCancel()?!1:(self.$cancel.removeClass(o.cancelHoverClass),self.$stars.triggerHandler("mouseout.stars"),void 0)}),this.$form.bind("reset.stars",function(){!o.disabled&&self.select(o.defaultValue)}),$(window).unload(function(){self.$cancel.unbind(".stars"),self.$stars.unbind(".stars"),self.$form.unbind(".stars"),self.$selec=self.$rboxs=self.$stars=self.$value=self.$cancel=self.$form=null
}),this.select(o.value),o.disabled&&this.disable()},_disableCancel:function(){var o=this.options,disabled=o.disabled||o.oneVoteOnly||o.value==o.cancelValue;return disabled?this.$cancel.removeClass(o.cancelHoverClass).addClass(o.cancelDisabledClass):this.$cancel.removeClass(o.cancelDisabledClass),this.$cancel.css("opacity",disabled?.5:1),disabled},_disableAll:function(){var o=this.options;this._disableCancel(),o.disabled?this.$stars.filter("div").addClass(o.starDisabledClass):this.$stars.filter("div").removeClass(o.starDisabledClass)
},_showCap:function(s){var o=this.options;o.captionEl&&o.captionEl.text(s)},value:function(){return this.options.value},select:function(val){var o=this.options,e=val==o.cancelValue?this.$cancel:this.$stars.eq(o.val2id[val]);o.forceSelect=!0,e.triggerHandler("click.stars"),o.forceSelect=!1},selectID:function(id){var o=this.options,e=-1==id?this.$cancel:this.$stars.eq(id);o.forceSelect=!0,e.triggerHandler("click.stars"),o.forceSelect=!1},enable:function(){this.options.disabled=!1,this._disableAll()},disable:function(){this.options.disabled=!0,this._disableAll()
},destroy:function(){this.options.isSelect?this.$selec.appendTo(this.element):this.$rboxs.appendTo(this.element),this.$form.unbind(".stars"),this.$cancel.unbind(".stars").remove(),this.$stars.unbind(".stars").remove(),this.$value.remove(),this.element.unbind(".stars").removeData("stars")},callback:function(e,type){var o=this.options;o.callback&&o.callback(this,type,o.value,e),o.oneVoteOnly&&!o.disabled&&this.disable()}}),$.extend($.ui.stars.prototype,{version:"2.1.1",options:{inputType:"radio",split:0,disabled:!1,cancelTitle:"Cancel Rating",cancelValue:0,cancelShow:!0,oneVoteOnly:!1,showTitles:!1,captionEl:null,callback:null,starWidth:16,cancelClass:"ui-stars-cancel",starClass:"ui-stars-star",starOnClass:"ui-stars-star-on",starHoverClass:"ui-stars-star-hover",starDisabledClass:"ui-stars-star-disabled",cancelHoverClass:"ui-stars-cancel-hover",cancelDisabledClass:"ui-stars-cancel-disabled"}})
}(jQuery)};exports(),module.resolveWith(exports)})};dispatch("ui/stars").containing(moduleFactory).to("Foundry/2.1 Modules")}();