!function(){var moduleFactory=function($){var module=this;$.require().script("mvc/lang.observe","mvc/event.hashchange","mvc/lang.string.deparam").done(function(){var exports=function(){var matcher=/\:([\w\.]+)/g,paramsMatcher=/^(?:&[^=]+=[^&]*)+/,makeProps=function(props){var html=[];return each(props,function(name,val){"className"===name&&(name="class"),val&&html.push(escapeHTML(name),'="',escapeHTML(val),'" ')}),html.join("")},escapeHTML=function(content){return content.replace(/"/g,"&#34;").replace(/'/g,"&#39;")
},matchesData=function(route,data){for(var count=0,i=0;i<route.names.length;i++){if(!data.hasOwnProperty(route.names[i]))return-1;count++}return count},onready=!0,location=window.location,encode=encodeURIComponent,decode=decodeURIComponent,each=$.each,extend=$.extend;$.route=function(url,defaults){var names=[],test=url.replace(matcher,function(whole,name){return names.push(name),"([^\\/\\&]*)"});return $.route.routes[url]={test:new RegExp("^"+test+"($|&)"),route:url,names:names,defaults:defaults||{},length:url.split("/").length},$.route
},extend($.route,{param:function(data){var route,matchCount,matches=0,routeName=data.route;if(delete data.route,routeName&&(route=$.route.routes[routeName])||each($.route.routes,function(name,temp){matchCount=matchesData(temp,data),matchCount>matches&&(route=temp,matches=matchCount)}),route){var after,cpy=extend({},data),res=route.route.replace(matcher,function(whole,name){return delete cpy[name],data[name]===route.defaults[name]?"":encode(data[name])});return each(route.defaults,function(name,val){cpy[name]===val&&delete cpy[name]
}),after=$.param(cpy),res+(after?"&"+after:"")}return $.isEmptyObject(data)?"":"&"+$.param(data)},deparam:function(url){var route={length:-1};if(each($.route.routes,function(name,temp){temp.test.test(url)&&temp.length>route.length&&(route=temp)}),route.length>-1){var parts=url.match(route.test),start=parts.shift(),remainder=url.substr(start.length-("&"===parts[parts.length-1]?1:0)),obj=remainder&&paramsMatcher.test(remainder)?$.String.deparam(remainder.slice(1)):{};return obj=extend(!0,{},route.defaults,obj),each(parts,function(i,part){part&&"&"!==part&&(obj[route.names[i]]=decode(part))
}),obj.route=route.route,obj}return"&"!==url.charAt(0)&&(url="&"+url),paramsMatcher.test(url)?$.String.deparam(url.slice(1)):{}},data:new $.Observe({}),routes:{},ready:function(val){return val===!1&&(onready=!1),(val===!0||onready===!0)&&setState(),$.route},url:function(options,merge){return merge?"#!"+$.route.param(extend({},curParams,options)):"#!"+$.route.param(options)},link:function(name,options,props,merge){return"<a "+makeProps(extend({href:$.route.url(options,merge)},props))+">"+name+"</a>"},current:function(options){return location.hash=="#!"+$.route.param(options)
}}),$(function(){$.route.ready()}),each(["bind","unbind","delegate","undelegate","attr","attrs","serialize","removeAttr"],function(i,name){$.route[name]=function(){return $.route.data[name].apply($.route.data,arguments)}});var curParams,throttle=function(func){var timer;return function(){var args=arguments,self=this;clearTimeout(timer),timer=setTimeout(function(){func.apply(self,args)},1)}},setState=function(){var hash="!"===location.hash.substr(1,1)?location.hash.slice(2):location.hash.slice(1);curParams=$.route.deparam(hash),$.route.attrs(curParams,!0)
};$(window).bind("hashchange",setState),$.route.bind("change",throttle(function(){location.hash="#!"+$.route.param($.route.serialize())}))};exports(),module.resolveWith(exports)})};dispatch("mvc/dom.route").containing(moduleFactory).to("Foundry/2.1 Modules")}();