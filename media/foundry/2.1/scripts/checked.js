!function(){var moduleFactory=function($){var module=this,exports=function(){$.fn.checked=function(checked,unchecked){return arguments.length<1?this.is(":checked"):(this.each(function(){var input=$(this);return"boolean"==typeof checked?(input.attr("checked",checked).trigger("change"),void 0):((input.is("input[type=checkbox]")||input.is("input[type=radio]"))&&input.unbind("change.checked").bind("change.checked",function(){try{return input.is(":checked")?checked.apply(input):unchecked.apply(input)}catch(e){}}),void 0)
}),this)}};exports(),module.resolveWith(exports)};dispatch("checked").containing(moduleFactory).to("Foundry/2.1 Modules")}();