EasyBlog.module("media/navigation",function(e){var t=this;EasyBlog.require().done(function(){var n,r,i;EasyBlog.Controller("Media.Navigation",{defaultOptions:{view:{item:"media/navigation.item",itemGroup:"media/navigation.itemgroup"},nestLevel:8,groupCollapseDelay:1e3,canActivate:!0,"{itemGroup}":".navigationItemGroup","{item}":".navigationItem"}},function(t){return{init:function(){n=t.media,r=n.library,i=n.options.directorySeparator,t.element.toggleClass("canActivate",t.options.canActivate)},setPathway:function(n){var n=r.getMeta(n);if(!n)return;t.currentKey=n.key;var s=r.getPlace(n.place),o=n.path,u=n.type==="folder",a=o.split(i).splice(1),f=t.options.nestLevel,l=a.length-(a.length%f||f),c;t.element.empty().toggleClass("type-folder",u),t.view.item({title:s.title||i}).addClass("base").data("key",s.id+"|"+i).appendTo(t.element);var h=s.id==="jomsocial";o!==i&&e.each(a,function(e,o){var p=!u&&e==a.length-1,d=i+a.slice(0,e+1).join(i),v=s.id+"|"+d,o=h?r.getMeta(v).title:o,m=t.view.item({title:p?n.title:o}).data("key",v).toggleClass("filename",p);e<l?(e%f==0&&(c=t.view.itemGroup().appendTo(t.element)),m.appendTo(c)):m.appendTo(t.element)})},"{itemGroup} mouseover":function(e){clearTimeout(e.data("delayCollapse")),e.addClass("expand")},"{itemGroup} mouseout":function(e){e.data("delayCollapse",setTimeout(function(){e.removeClass("expand")},t.options.groupCollapseDelay))},"{item} click":function(e){if(t.options.canActivate){var n=e.data("key");t.trigger("activate",n)}}}}),t.resolve()})});