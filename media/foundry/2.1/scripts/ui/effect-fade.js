!function(){var moduleFactory=function($){var module=this,jQuery=$;$.require().script("ui/effect").done(function(){var exports=function(){!function($){$.effects.effect.fade=function(o,done){var el=$(this),mode=$.effects.setMode(el,o.mode||"toggle");el.animate({opacity:mode},{queue:!1,duration:o.duration,easing:o.easing,complete:done})}}(jQuery)};exports(),module.resolveWith(exports)})};dispatch("ui/effect-fade").containing(moduleFactory).to("Foundry/2.1 Modules")}();